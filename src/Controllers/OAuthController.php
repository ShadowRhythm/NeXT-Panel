<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Config;
use App\Models\User;
use App\Services\Cache;
use App\Services\Filter;
use App\Services\I18n;
use App\Utils\Cookie;
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
use Throwable;
use function hash;
use function hash_hmac;
use function http_build_query;
use function implode;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function ltrim;
use function rtrim;
use function sort;
use function str_starts_with;
use function strcmp;
use function strtolower;
use function time;
use function trim;
use function urlencode;

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
            'google' => $this->handleAccountOAuth($request, $response, 'google'),
            'github' => $this->handleAccountOAuth($request, $response, 'github'),
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

    /**
     * Unified Google / GitHub OAuth flow supporting login, auto-registration,
     * verified-email auto-merge, invite-only enforcement, MFA, and account binding.
     *
     * @throws RedisException
     */
    private function handleAccountOAuth(ServerRequest $request, Response $response, string $provider): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $config = $this->getAccountProviderConfig($provider);
        $isLoggedIn = $this->user->isLogin;

        if (! $config['enabled'] || $config['client_id'] === '' || $config['client_secret'] === '') {
            return $this->respondOAuthError(
                $request,
                $response,
                $isLoggedIn,
                'oauth_disabled',
                I18n::trans('auth.oauth_disabled', $locale)
            );
        }

        $redirectUri = rtrim((string) $_ENV['baseUrl'], '/') . '/oauth/' . $provider;
        $redis = (new Cache())->initRedis();

        if ($request->getParam('error') !== null) {
            return $this->respondOAuthError(
                $request,
                $response,
                $isLoggedIn,
                'request_failed',
                I18n::trans('auth.oauth_request_failed', $locale)
            );
        }

        $code = $request->getParam('code');
        if ($code === null || $code === '') {
            $state = (string) Tools::genRandomChar(32);
            $inviteCode = trim((string) $this->antiXss->xss_clean(
                (string) ($request->getParam('invite_code') ?? Cookie::get('invite_code') ?? '')
            ));
            if ($inviteCode !== '') {
                Cookie::set(['invite_code' => $inviteCode], time() + 86400);
            }

            $statePayload = [
                'mode' => $isLoggedIn ? 'bind' : 'login',
                'user_id' => $isLoggedIn ? (int) $this->user->id : 0,
                'invite_code' => $inviteCode,
                'remember_me' => $request->getParam('remember_me') === '0' ? 0 : 1,
            ];
            $redis->setex('oauth_' . $provider . '_state:' . $state, 300, json_encode($statePayload));

            $authParams = [
                'client_id' => $config['client_id'],
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => $config['scope'],
                'state' => $state,
            ];
            if ($provider === 'google') {
                $authParams['prompt'] = 'select_account';
            }

            $authUrl = $config['auth_url'] . '?' . http_build_query($authParams);

            if ($request->getMethod() === 'POST') {
                return $response->withJson([
                    'ret' => 1,
                    'redir' => $authUrl,
                ]);
            }

            return $response->withStatus(302)->withHeader('Location', $authUrl);
        }

        $state = trim((string) $request->getParam('state'));
        $stateKey = 'oauth_' . $provider . '_state:' . $state;
        $rawState = $state !== '' ? $redis->get($stateKey) : false;
        if ($rawState === false) {
            return $this->respondOAuthError(
                $request,
                $response,
                $isLoggedIn,
                'request_failed',
                I18n::trans('auth.oauth_request_failed', $locale)
            );
        }
        $redis->del($stateKey);

        $stateData = json_decode((string) $rawState, true);
        if (! is_array($stateData)) {
            return $this->respondOAuthError(
                $request,
                $response,
                $isLoggedIn,
                'request_failed',
                I18n::trans('auth.oauth_request_failed', $locale)
            );
        }

        $mode = (string) ($stateData['mode'] ?? 'login');

        try {
            $profile = $this->exchangeAccountOAuthProfile($provider, (string) $code, $redirectUri, $config);
        } catch (Throwable $e) {
            error_log('OAuth profile exchange error (' . $provider . '): ' . $e->getMessage());
            return $this->respondOAuthError(
                $request,
                $response,
                $mode === 'bind',
                'request_failed',
                I18n::trans('auth.oauth_request_failed', $locale)
            );
        }

        $providerUserId = $profile['id'];
        $column = $config['column'];

        if ($mode === 'bind') {
            if (! $this->user->isLogin || (int) $this->user->id !== (int) ($stateData['user_id'] ?? 0)) {
                return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=please_login');
            }

            $occupied = (new User())->where($column, $providerUserId)->first();
            if ($occupied !== null && (int) $occupied->id !== (int) $this->user->id) {
                return $response->withStatus(302)->withHeader(
                    'Location',
                    '/user/edit?oauth_error=already_bound#login_security'
                );
            }

            $this->user->$column = $providerUserId;
            $this->user->save();

            return $response->withStatus(302)->withHeader(
                'Location',
                '/user/edit?oauth_msg=bind_success#login_security'
            );
        }

        // Login / auto-merge / auto-register flow
        $user = (new User())->where($column, $providerUserId)->first();

        if ($user === null && $profile['email'] !== '' && $profile['email_verified']) {
            $emailUser = (new User())->where('email', $profile['email'])->first();
            if ($emailUser !== null) {
                $emailUser->$column = $providerUserId;
                $emailUser->save();
                $user = $emailUser;
            }
        }

        if ($user === null) {
            if ($profile['email'] === '' || ! $profile['email_verified']) {
                return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=email_unverified');
            }

            if (! Filter::checkEmailFilter($profile['email'])) {
                return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=invalid_email');
            }

            $regMode = (string) Config::obtain('reg_mode');
            if ($regMode === 'close') {
                return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=reg_closed');
            }

            $inviteCode = trim((string) ($stateData['invite_code'] ?? Cookie::get('invite_code') ?? ''));

            if ($regMode === 'invite' && AuthController::resolveValidInviteUserId($inviteCode) === null) {
                $pendingToken = (string) Tools::genRandomChar(32);
                $redis->setex('oauth_pending:' . $pendingToken, 600, json_encode([
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                    'email' => $profile['email'],
                    'name' => $profile['name'],
                ]));

                return $response->withStatus(302)->withHeader(
                    'Location',
                    '/auth/oauth/invite?token=' . urlencode($pendingToken)
                );
            }

            try {
                $user = AuthController::createUserAccount(
                    $profile['name'],
                    $profile['email'],
                    (string) Tools::genRandomChar(32),
                    $inviteCode,
                    0,
                    '',
                    0.0,
                    true,
                    [$column => $providerUserId]
                );
            } catch (Throwable $e) {
                error_log('OAuth auto register error (' . $provider . '): ' . $e->getMessage());
                return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=request_failed');
            }
        }

        Cookie::set(['invite_code' => ''], time() - 3600);

        $redirCookie = Cookie::get('redir');
        $redir = ($redirCookie !== null && $redirCookie !== '') ? $this->antiXss->xss_clean($redirCookie) : '/user';
        if ($redirCookie !== null && $redirCookie !== '') {
            Cookie::set(['redir' => ''], time() - 3600);
        }
        if (! str_starts_with($redir, '/') || str_starts_with($redir, '//')) {
            $redir = '/user';
        }

        $rememberMe = (int) ($stateData['remember_me'] ?? 1);
        $loginResult = AuthController::completeLogin($user, $rememberMe, $redir);

        return $response->withStatus(302)->withHeader('Location', $loginResult['redir']);
    }

    /**
     * @return array{enabled: bool, client_id: string, client_secret: string, column: string, auth_url: string, scope: string}
     */
    private function getAccountProviderConfig(string $provider): array
    {
        return match ($provider) {
            'google' => [
                'enabled' => (bool) Config::obtain('enable_google_oauth'),
                'client_id' => trim((string) Config::obtain('google_client_id')),
                'client_secret' => trim((string) Config::obtain('google_client_secret')),
                'column' => 'google_id',
                'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
                'scope' => 'openid email profile',
            ],
            'github' => [
                'enabled' => (bool) Config::obtain('enable_github_oauth'),
                'client_id' => trim((string) Config::obtain('github_client_id')),
                'client_secret' => trim((string) Config::obtain('github_client_secret')),
                'column' => 'github_id',
                'auth_url' => 'https://github.com/login/oauth/authorize',
                'scope' => 'read:user user:email',
            ],
            default => throw new Exception('Unsupported OAuth provider'),
        };
    }

    /**
     * @param array{client_id: string, client_secret: string} $config
     *
     * @return array{id: string, email: string, email_verified: bool, name: string}
     *
     * @throws GuzzleException
     */
    private function exchangeAccountOAuthProfile(
        string $provider,
        string $code,
        string $redirectUri,
        array $config
    ): array {
        $client = new Client(['timeout' => 10]);

        if ($provider === 'google') {
            $tokenResp = $client->post('https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'code' => $code,
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);
            $tokenData = json_decode($tokenResp->getBody()->getContents(), true);
            $accessToken = (string) ($tokenData['access_token'] ?? '');
            if ($accessToken === '') {
                throw new Exception('Missing Google access token');
            }

            $userResp = $client->get('https://openidconnect.googleapis.com/v1/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]);
            $userData = json_decode($userResp->getBody()->getContents(), true);
            $sub = trim((string) ($userData['sub'] ?? ''));
            if ($sub === '') {
                throw new Exception('Missing Google subject ID');
            }

            return [
                'id' => $sub,
                'email' => strtolower(trim((string) ($userData['email'] ?? ''))),
                'email_verified' => (bool) ($userData['email_verified'] ?? false),
                'name' => trim((string) ($userData['name'] ?? '')),
            ];
        }

        $tokenResp = $client->post('https://github.com/login/oauth/access_token', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'form_params' => [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ],
        ]);
        $tokenData = json_decode($tokenResp->getBody()->getContents(), true);
        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            throw new Exception('Missing GitHub access token');
        }

        $apiHeaders = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'SSPanel-UIM',
        ];

        $userResp = $client->get('https://api.github.com/user', [
            'headers' => $apiHeaders,
        ]);
        $userData = json_decode($userResp->getBody()->getContents(), true);
        $githubId = trim((string) ($userData['id'] ?? ''));
        if ($githubId === '') {
            throw new Exception('Missing GitHub user ID');
        }

        $emailsResp = $client->get('https://api.github.com/user/emails', [
            'headers' => $apiHeaders,
        ]);
        $emails = json_decode($emailsResp->getBody()->getContents(), true);

        $chosenEmail = '';
        $emailVerified = false;
        if (is_array($emails)) {
            foreach ($emails as $entry) {
                if (! empty($entry['primary']) && ! empty($entry['verified']) && ! empty($entry['email'])) {
                    $chosenEmail = strtolower(trim((string) $entry['email']));
                    $emailVerified = true;
                    break;
                }
            }
            if ($chosenEmail === '') {
                foreach ($emails as $entry) {
                    if (! empty($entry['verified']) && ! empty($entry['email'])) {
                        $chosenEmail = strtolower(trim((string) $entry['email']));
                        $emailVerified = true;
                        break;
                    }
                }
            }
        }

        return [
            'id' => $githubId,
            'email' => $chosenEmail,
            'email_verified' => $emailVerified,
            'name' => trim((string) ($userData['name'] ?? $userData['login'] ?? '')),
        ];
    }

    private function respondOAuthError(
        ServerRequest $request,
        Response $response,
        bool $isBindFlow,
        string $errorCode,
        string $message
    ): ResponseInterface {
        if ($request->getMethod() === 'POST') {
            return ResponseHelper::error($response, $message);
        }

        if ($isBindFlow) {
            return $response->withStatus(302)->withHeader(
                'Location',
                '/user/edit?oauth_error=' . urlencode($errorCode) . '#login_security'
            );
        }

        return $response->withStatus(302)->withHeader(
            'Location',
            '/auth/login?oauth_error=' . urlencode($errorCode)
        );
    }
}
