<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Config;
use App\Models\User;
use App\Services\Cache;
use App\Services\I18n;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Psr\Http\Message\ResponseInterface;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception as SmartyException;
use function hash;
use function hash_hmac;
use function implode;
use function is_array;
use function json_decode;
use function ltrim;
use function sort;
use function strcmp;
use function time;

final class OAuthController extends BaseController
{
    /**
     * @throws SmartyException
     * @throws GuzzleException
     * @throws RedisException
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return match ($args['type']) {
            'slack' => $this->slack($request, $response, $args),
            'discord' => $this->discord($request, $response, $args),
            'telegram' => $this->telegram($request, $response, $args),
            default => $response->withStatus(404)->write($this->view()->fetch('404.tpl')),
        };
    }

    /**
     * @throws GuzzleException
     * @throws RedisException
     * @throws Exception
     */
    public function slack(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $locale = I18n::getCurrentLocale($user);
        $redis = (new Cache())->initRedis();

        if ($request->getParam('code') === null) {
            $state = Tools::genRandomChar(16);
            $redis->setex('slack_state:' . $user->id, 300, $state);
            $client_id = Config::obtain('slack_client_id');
            $team_id = Config::obtain('slack_team_id');
            $redirect_uri = $_ENV['baseUrl'] . '/oauth/slack';

            return $response->withJson([
                'ret' => 1,
                'redir' => 'https://slack.com/openid/connect/authorize?response_type=code&scope=openid profile&client_id='
                    . $client_id . '&state=' . $state . '&team=' . $team_id .
                    '&nonce=' . $state . '&redirect_uri=' . $redirect_uri,
            ]);
        }

        $code = $request->getParam('code');
        $state = $request->getParam('state');

        if ($state !== $redis->get('slack_state:' . $user->id)) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $client = new Client();
        $slack_api_url = 'https://slack.com/api/openid.connect.token';

        $code_headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $code_body = [
            'client_id' => Config::obtain('slack_client_id'),
            'client_secret' => Config::obtain('slack_client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $_ENV['baseUrl'] . '/oauth/slack',
        ];

        $code_response = $client->post($slack_api_url, [
            'headers' => $code_headers,
            'form_params' => $code_body,
            'timeout' => 3,
        ]);

        if (! json_decode($code_response->getBody()->__toString())->ok) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $parser = new Parser(new JoseEncoder());
        $id_token = $parser->parse(json_decode($code_response->getBody()->__toString())->id_token);

        $slack_user_id = $id_token->claims()->get('https://slack.com/user_id');

        if ((new User())->where('im_type', 1)->where('im_value', $slack_user_id)->first() !== null ||
            ($user->im_type === 1 && $user->im_value === $slack_user_id)) {
            return ResponseHelper::error($response, I18n::trans('auth.slack_already_bound', $locale));
        }

        $user->im_type = 1;
        $user->im_value = $slack_user_id;
        $user->save();

        return $response->withRedirect($_ENV['baseUrl'] . '/user/edit');
    }

    /**
     * @throws GuzzleException
     * @throws RedisException
     */
    public function discord(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $locale = I18n::getCurrentLocale($user);
        $redis = (new Cache())->initRedis();

        if ($request->getParam('code') === null) {
            $state = Tools::genRandomChar(16);
            $redis->setex('discord_state:' . $user->id, 300, $state);
            $client_id = Config::obtain('discord_client_id');
            $redirect_uri = $_ENV['baseUrl'] . '/oauth/discord';

            return $response->withJson([
                'ret' => 1,
                'redir' => 'https://discord.com/api/oauth2/authorize?client_id=' . $client_id .
                    '&redirect_uri=' . $redirect_uri .
                    '&response_type=code&scope=guilds.join identify&state=' . $state,
            ]);
        }

        $code = $request->getParam('code');
        $state = $request->getParam('state');

        if ($state !== $redis->get('discord_state:' . $user->id)) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $client = new Client();
        $discord_api_url = 'https://discord.com/api/oauth2/token';

        $code_headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $code_body = [
            'client_id' => Config::obtain('discord_client_id'),
            'client_secret' => Config::obtain('discord_client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $_ENV['baseUrl'] . '/oauth/discord',
        ];

        $code_response = $client->post($discord_api_url, [
            'headers' => $code_headers,
            'form_params' => $code_body,
            'timeout' => 3,
        ]);

        if ($code_response->getStatusCode() !== 200) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $access_token = json_decode($code_response->getBody()->getContents())->access_token;
        $discord_user_url = 'https://discord.com/api/users/@me';

        $user_headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer ' . $access_token,
        ];

        $user_response = $client->get($discord_user_url, [
            'headers' => $user_headers,
        ]);

        if ($user_response->getStatusCode() !== 200) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $discord_user_id = json_decode($user_response->getBody()->getContents(), true)['id'];

        if ((new User())->where('im_type', 2)->where('im_value', $discord_user_id)->first() !== null ||
            ($user->im_type === 2 && $user->im_value === $discord_user_id)) {
            return ResponseHelper::error($response, I18n::trans('auth.discord_already_bound', $locale));
        }

        $user->im_type = 2;
        $user->im_value = $discord_user_id;
        $user->save();

        if (Config::obtain('discord_guild_id') !== 0) {
            $discord_guild_url = 'https://discord.com/api/guilds/' . Config::obtain('discord_guild_id') .
                '/members/' . $user->im_value;

            $guild_headers = [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bot ' . Config::obtain('discord_bot_token'),
            ];

            $guild_body = [
                'access_token' => $access_token,
            ];

            $client->put($discord_guild_url, [
                'headers' => $guild_headers,
                'json' => $guild_body,
            ]);
        }

        return $response->withRedirect($_ENV['baseUrl'] . '/user/edit');
    }

    public function telegram(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $action = $request->getParam('action');
        $user_param = $request->getParam('user');

        if ($action === 'check') {
            if (! $this->user->isLogin) {
                return ResponseHelper::error($response, I18n::trans('auth.please_login_first', $locale));
            }

            $user = (new User())->find($this->user->id) ?? $this->user;

            return $response->withJson([
                'ret' => 1,
                'bound' => ($user->im_type === 4 && $user->im_value !== ''),
                'im_value' => (string) $user->im_value,
            ]);
        }

        if ($action === 'get_token' || ($user_param === null && $action === null)) {
            if (! $this->user->isLogin) {
                return ResponseHelper::error($response, I18n::trans('auth.please_login_first', $locale));
            }

            if ($this->user->im_type === 4 && $this->user->im_value !== '') {
                return ResponseHelper::error($response, I18n::trans('auth.telegram_already_bound_unbind_first', $locale));
            }

            $redis = (new Cache())->initRedis();

            $old_token = $redis->get('telegram_bind_user:' . $this->user->id);
            if ($old_token) {
                $redis->del('telegram_bind_token:' . $old_token);
            }

            $token = (string) Tools::genRandomChar(16);
            $redis->setex('telegram_bind_token:' . $token, 600, (string) $this->user->id);
            $redis->setex('telegram_bind_user:' . $this->user->id, 600, $token);

            $bot_username = ltrim((string) Config::obtain('telegram_bot'), '@');
            $bind_url = 'https://t.me/' . $bot_username . '?start=' . $token;

            return $response->withJson([
                'ret' => 1,
                'token' => $token,
                'bot_username' => $bot_username,
                'bind_url' => $bind_url,
            ]);
        }

        $user_auth = json_decode((string) $user_param, true);
        if (! is_array($user_auth) || ! isset($user_auth['hash'], $user_auth['auth_date'], $user_auth['id'])) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $check_hash = $user_auth['hash'];
        unset($user_auth['hash']);
        $data_check_arr = [];

        foreach ($user_auth as $key => $value) {
            $data_check_arr[] = $key . '=' . $value;
        }

        sort($data_check_arr);
        $data_check_string = implode("\n", $data_check_arr);
        $secret_key = hash('sha256', Config::obtain('telegram_token'), true);
        $hash = hash_hmac('sha256', $data_check_string, $secret_key);

        if (strcmp($hash, $check_hash) !== 0 || (time() - $user_auth['auth_date']) > 86400) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $telegram_id = $this->antiXss->xss_clean($user_auth['id']);
        $user = $this->user;

        if ((new User())->where('im_type', 4)->where('im_value', $telegram_id)->first() !== null ||
            ($user->im_type === 4 && $user->im_value === $telegram_id)) {
            return ResponseHelper::error($response, I18n::trans('auth.telegram_already_bound', $locale));
        }

        $user->im_type = 4;
        $user->im_value = $telegram_id;

        $user->save();

        return ResponseHelper::success($response, I18n::trans('auth.bind_success', $locale));
    }
}
