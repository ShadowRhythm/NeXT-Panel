<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Config;
use App\Models\InviteCode;
use App\Models\LoginIp;
use App\Models\User;
use App\Services\Auth;
use App\Services\Cache;
use App\Services\Captcha;
use App\Services\Filter;
use App\Services\I18n;
use App\Services\Mail;
use App\Services\MFA\FIDO;
use App\Services\MFA\TOTP;
use App\Services\MFA\WebAuthn;
use App\Services\RateLimit;
use App\Services\Reward;
use App\Utils\Cookie;
use App\Utils\Hash;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Throwable;
use function array_rand;
use function date;
use function explode;
use function mb_strlen;
use function str_starts_with;
use function strlen;
use function strtolower;
use function time;
use function trim;

final class AuthController extends BaseController
{
    /**
     * @throws Exception
     */
    public function login(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $captcha = [];

        if (Config::obtain('enable_login_captcha')) {
            $captcha = Captcha::generate();
        }

        return $response->write($this->view()
            ->assign('base_url', $_ENV['baseUrl'])
            ->assign('captcha', $captcha)
            ->fetch('auth/login.tpl'));
    }

    public function loginHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();

        if (Config::obtain('enable_rate_limit') &&
            ! (new RateLimit())->checkRateLimit('login_ip', $request->getServerParam('REMOTE_ADDR') ?? '')
        ) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.request_too_frequent', $locale),
            ]);
        }

        if (Config::obtain('enable_login_captcha') && ! Captcha::verify($request->getParams())) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.captcha_failed', $locale),
            ]);
        }

        $password = $request->getParam('password');
        $rememberMe = $request->getParam('remember_me') === 'true' ? 1 : 0;
        $email = strtolower(trim($this->antiXss->xss_clean($request->getParam('email'))));
        $redir_cookie = Cookie::get('redir');
        $redir = ($redir_cookie !== null && $redir_cookie !== '') ? $this->antiXss->xss_clean($redir_cookie) : '/user';

        if (! str_starts_with($redir, '/') || str_starts_with($redir, '//')) {
            $redir = '/user';
        }
        $user = (new User())->where('email', $email)->first();
        $loginIp = new LoginIp();

        if ($user === null) {
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 1);

            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.invalid_email_or_password', $locale),
            ]);
        }

        if (! Hash::checkPassword($user->pass, $password)) {
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 1, $user->id);

            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.invalid_email_or_password', $locale),
            ]);
        }

        $mfaStatus = $user->checkMfaStatus();
        if ($mfaStatus['require']) {
            $redis = (new Cache())->initRedis();
            $redis->setex('mfa_login_' . session_id(), 300, json_encode([
                'userid' => $user->id,
                'method' => $mfaStatus,
                'redir' => $redir,
                'remember_me' => $rememberMe,
            ]));

            return $response
                ->withHeader('HX-Redirect', '/auth/mfa')
                ->withJson([
                    'ret' => 1,
                    'msg' => I18n::trans('user.mfa_required', $locale),
                ]);
        }

        $time = $rememberMe ? 86400 * (int) (Config::obtain('rememberMeDuration') ?? 7) : 3600; // Cookie 过期时间

        Auth::login($user->id, $time);
        if (! empty($user->locale) && I18n::isValidLocale($user->locale)) {
            Cookie::set(['locale' => $user->locale], time() + 86400 * 365);
        }
        // 记录登录成功
        $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
        $user->last_login_time = time();
        $user->save();

        return $response->withHeader('HX-Redirect', $redir);
    }

    public function mfaPage(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $redis = (new Cache())->initRedis();
        $mfa_session = $redis->get('mfa_login_' . session_id());
        if ($mfa_session === false) {
            return $response->withStatus(302)->withHeader('Location', '/auth/login');
        }
        $mfa_session = json_decode($mfa_session, true);
        return $response->write(
            $this->view()
                ->assign('base_url', $_ENV['baseUrl'])
                ->assign('method', $mfa_session['method'])
                ->fetch('auth/mfa.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function register(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $captcha = [];

        if (Config::obtain('enable_reg_captcha')) {
            $captcha = Captcha::generate();
        }

        $invite_code = $this->antiXss->xss_clean($request->getParam('code'));

        return $response->write(
            $this->view()
                ->assign('invite_code', $invite_code)
                ->assign('base_url', $_ENV['baseUrl'])
                ->assign('captcha', $captcha)
                ->fetch('auth/register.tpl')
        );
    }

    /**
     * @throws RedisException
     */
    public function sendVerify(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();

        if (Config::obtain('reg_email_verify')) {
            $email = strtolower(trim($this->antiXss->xss_clean($request->getParam('email'))));

            if ($email === '') {
                return ResponseHelper::error($response, I18n::trans('user.email_empty', $locale));
            }

            // check email format
            $email_check = Filter::checkEmailFilter($email);

            if (! $email_check) {
                return ResponseHelper::error($response, I18n::trans('user.invalid_email', $locale));
            }

            if (! (new RateLimit())->checkRateLimit('email_request_ip', $request->getServerParam('REMOTE_ADDR')) ||
                ! (new RateLimit())->checkRateLimit('email_request_address', $email)
            ) {
                return ResponseHelper::error($response, I18n::trans('user.request_too_frequent', $locale));
            }

            $user = (new User())->where('email', $email)->first();

            if ($user !== null) {
                return ResponseHelper::error($response, I18n::trans('user.email_already_registered', $locale));
            }

            $email_code = Tools::genRandomNum(6);
            $redis = (new Cache())->initRedis();
            $redis->setex('email_verify:' . $email_code, Config::obtain('email_verify_code_ttl'), $email);

            try {
                Mail::send(
                    $email,
                    I18n::trans('email.verify_email_subject', $locale, ['%app_name%' => $_ENV['appName']]),
                    'verify_code.tpl',
                    [
                        'code' => $email_code,
                        'expire' => date('Y-m-d H:i:s', time() + Config::obtain('email_verify_code_ttl')),
                    ]
                );
            } catch (Exception|ClientExceptionInterface) {
                return ResponseHelper::error($response, I18n::trans('user.email_send_failed_contact_admin', $locale));
            }

            return ResponseHelper::success($response, I18n::trans('user.verify_code_sent', $locale));
        }

        return ResponseHelper::error($response, I18n::trans('user.reg_email_verify_disabled', $locale));
    }

    /**
     * @throws Exception
     */
    public function registerHelper(
        Response $response,
        $name,
        $email,
        $password,
        $invite_code,
        $imtype,
        $imvalue,
        $money,
        $is_admin_reg
    ): ResponseInterface {
        $redir_cookie = Cookie::get('redir');
        $redir = ($redir_cookie !== null && $redir_cookie !== '') ? $this->antiXss->xss_clean($redir_cookie) : '/user';

        if (! str_starts_with($redir, '/') || str_starts_with($redir, '//')) {
            $redir = '/user';
        }
        $configs = Config::getClass('reg');
        // do reg user
        $user = new User();

        $user->user_name = $name;
        $user->email = $email;
        $user->remark = '';
        $user->pass = Hash::passwordHash($password);
        $user->passwd = Tools::genPasswd(16);
        $user->uuid = (string) Uuid::uuid4();
        $user->api_token = Tools::genRandomChar(32);
        $user->port = Tools::getSsPort();
        $user->u = 0;
        $user->d = 0;
        $user->method = (string) ($configs['reg_method'] ?? 'aes-128-gcm');
        $user->im_type = (int) $imtype;
        $user->im_value = (string) $imvalue;
        $user->transfer_enable = Tools::gbToB((int) ($configs['reg_traffic'] ?? 0));
        $user->auto_reset_day = (int) (Config::obtain('free_user_reset_day') ?? 0);
        $user->auto_reset_bandwidth = (float) (Config::obtain('free_user_reset_bandwidth') ?? 0);
        $user->daily_mail_enable = (int) ($configs['reg_daily_report'] ?? 0);

        if ($money > 0) {
            $user->money = (float) $money;
        } else {
            $user->money = 0.0;
        }

        $user->ref_by = 0;

        if ($invite_code !== '') {
            $invite = (new InviteCode())->where('code', $invite_code)->first();

            if ($invite !== null) {
                $user->ref_by = (int) $invite->user_id;
            }
        }

        $user->class = (int) ($configs['reg_class'] ?? 0);
        $user->class_expire = date('Y-m-d H:i:s', time() + (int) ($configs['reg_class_time'] ?? 0) * 86400);
        $user->node_iplimit = (int) ($configs['reg_ip_limit'] ?? 0);
        $user->node_speedlimit = (int) ($configs['reg_speed_limit'] ?? 0);
        $user->reg_date = date('Y-m-d H:i:s');
        $user->reg_ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $user->theme = (string) ($_ENV['theme'] ?? 'tabler');
        $user->locale = (string) I18n::getCurrentLocale();
        $random_group = Config::obtain('random_group');

        if ($random_group === '' || $random_group === null) {
            $user->node_group = 0;
        } else {
            $groups = explode(',', (string) $random_group);
            $user->node_group = (int) trim($groups[array_rand($groups)]);
        }

        $user->last_login_time = time();

        try {
            $saved = $user->save();
        } catch (Throwable $e) {
            error_log('User registration database save error: ' . $e->getMessage());
            $errorMsg = I18n::trans('user.unknown_error', $locale);
            if (! empty($_ENV['debug'])) {
                $errorMsg .= ': ' . $e->getMessage();
            }
            return ResponseHelper::error($response, $errorMsg);
        }

        if ($saved) {
            if ($is_admin_reg) {
                return ResponseHelper::success($response, 'ok');
            }

            if ($user->ref_by !== 0) {
                try {
                    Reward::issueRegReward($user->id, $user->ref_by);
                } catch (Throwable $e) {
                    error_log('Issue reg reward error: ' . $e->getMessage());
                }
            }

            try {
                Auth::login($user->id, 3600);
                Cookie::set(['locale' => $user->locale], time() + 86400 * 365);
                (new LoginIp())->collectLoginIP($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 0, $user->id);
            } catch (Throwable $e) {
                error_log('Post-reg login/cookie error: ' . $e->getMessage());
            }

            return $response->withHeader('HX-Redirect', $redir)->withJson([
                'ret' => 1,
                'msg' => 'ok',
            ]);
        }

        $locale = I18n::getCurrentLocale();

        return ResponseHelper::error($response, I18n::trans('user.unknown_error', $locale));
    }

    /**
     * @throws RedisException
     * @throws Exception
     */
    public function registerHandle(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();

        if (Config::obtain('reg_mode') === 'close') {
            return ResponseHelper::error($response, I18n::trans('user.reg_closed', $locale));
        }

        try {
            if (Config::obtain('enable_rate_limit') &&
                ! (new RateLimit())->checkRateLimit('register_ip', $request->getServerParam('REMOTE_ADDR') ?? '')
            ) {
                return ResponseHelper::error($response, I18n::trans('user.request_too_frequent', $locale));
            }
        } catch (Throwable) {
            // Fail open on rate limiter error
        }

        if (Config::obtain('enable_reg_captcha') && ! Captcha::verify($request->getParams())) {
            return ResponseHelper::error($response, I18n::trans('user.captcha_failed', $locale));
        }

        $tos = $request->getParam('tos') === 'true' ? 1 : 0;
        $email = strtolower(trim($this->antiXss->xss_clean($request->getParam('email'))));
        $name = trim($this->antiXss->xss_clean((string) $request->getParam('name')));
        $password = $request->getParam('password');
        $confirm_password = $request->getParam('confirm_password');
        $invite_code = $this->antiXss->xss_clean(trim($request->getParam('invite_code')));

        if (! $tos) {
            return ResponseHelper::error($response, I18n::trans('user.tos_not_agreed', $locale));
        }

        if ($name === '') {
            $name = explode('@', $email)[0];
        }

        if (mb_strlen($name) > 32) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_input', $locale));
        }

        if (strlen($password) < 8) {
            return ResponseHelper::error($response, I18n::trans('user.password_length_min_8', $locale));
        }

        if ($password !== $confirm_password) {
            return ResponseHelper::error($response, I18n::trans('user.password_not_match', $locale));
        }

        if ($invite_code === '' && Config::obtain('reg_mode') === 'invite') {
            return ResponseHelper::error($response, I18n::trans('user.invite_code_empty', $locale));
        }

        if ($invite_code !== '') {
            $invite = (new InviteCode())->where('code', $invite_code)->first();

            if ($invite === null) {
                return ResponseHelper::error($response, I18n::trans('user.invalid_invite_code', $locale));
            }

            $ref_user = (new User())->where('id', $invite->user_id)->first();

            if ($ref_user === null) {
                return ResponseHelper::error($response, I18n::trans('user.invalid_invite_code', $locale));
            }
        }

        $imtype = 0;
        $imvalue = '';

        // check email format
        $email_check = Filter::checkEmailFilter($email);

        if (! $email_check) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_email', $locale));
        }
        // check email
        $user = (new User())->where('email', $email)->first();

        if ($user !== null) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_email', $locale));
        }

        if (Config::obtain('reg_email_verify')) {
            $redis = (new Cache())->initRedis();
            $email_verify_code = trim($this->antiXss->xss_clean($request->getParam('emailcode')));
            $email_verify = $redis->get('email_verify:' . $email_verify_code);

            if (! $email_verify || $email_verify !== $email) {
                return ResponseHelper::error($response, I18n::trans('user.email_verify_code_incorrect', $locale));
            }

            $redis->del('email_verify:' . $email_verify_code);
        }

        return $this->registerHelper($response, $name, $email, $password, $invite_code, $imtype, $imvalue, 0, 0);
    }

    public function logout(ServerRequest $request, Response $response, $next): Response
    {
        Auth::logout();

        return $response->withStatus(302)->withHeader('Location', '/auth/login');
    }

    public function webauthnRequest(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        return $response->withJson(WebAuthn::assertRequest());
    }

    public function webauthnHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();
        $data = $this->antiXss->xss_clean((array) $request->getParsedBody());
        $redir_cookie = Cookie::get('redir');
        $redir = ($redir_cookie !== null && $redir_cookie !== '') ? $this->antiXss->xss_clean($redir_cookie) : '/user';

        if (! str_starts_with($redir, '/') || str_starts_with($redir, '//')) {
            $redir = '/user';
        }
        $result = WebAuthn::assertHandle($data);
        if ($result['ret'] === 1) {
            $user = $result['user'];
            if ($user === null) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.user_not_found', $locale),
                ]);
            }
            $rememberMe = $request->getParam('remember_me') === 'true';
            $time = $rememberMe ? 86400 * (int) (Config::obtain('rememberMeDuration') ?? 7) : 3600;
            Auth::login($user->id, $time);
            $loginIp = new LoginIp();
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
            $user->last_login_time = time();
            $user->save();
            return $response->withJson([
                'ret' => 1,
                'msg' => I18n::trans('user.login_success', $locale),
                'redir' => $redir,
            ]);
        }
        return $response->withJson($result);
    }

    public function totpHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();
        $redis = (new Cache())->initRedis();
        $login_session = $redis->get('mfa_login_' . session_id());
        if ($login_session === false) {
            return $response->withJson(['ret' => 0, 'msg' => I18n::trans('user.login_session_expired', $locale)])->withHeader('HX-Redirect', '/auth/login');
        }
        $login_session = json_decode($login_session, true);
        $code = $this->antiXss->xss_clean($request->getParam('code'));
        $user = (new User())->where('id', $login_session['userid'])->first();
        if ($user === null) {
            return $response->withJson(['ret' => 0, 'msg' => I18n::trans('user.user_not_found', $locale)])->withHeader('HX-Redirect', '/auth/login');
        }
        $result = TOTP::assertHandle($user, $code);
        if ($result['ret'] === 1) {
            $redis->del('mfa_login_' . session_id());
            $rememberMe = $login_session['remember_me'];
            $time = $rememberMe ? 86400 * (int) (Config::obtain('rememberMeDuration') ?? 7) : 3600;
            Auth::login($user->id, $time);
            $loginIp = new LoginIp();
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
            $user->last_login_time = time();
            $user->save();
            return $response
                ->withHeader('HX-Redirect', $login_session['redir'])
                ->withJson(['ret' => 1, 'msg' => I18n::trans('user.login_success', $locale)]);
        }
        return $response->withJson($result);
    }

    public function fidoRequest(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();
        $redis = (new Cache())->initRedis();
        $login_session = $redis->get('mfa_login_' . session_id());
        if ($login_session === false) {
            return $response->withJson(['ret' => 0, 'msg' => I18n::trans('user.login_session_expired', $locale)])->withHeader('HX-Redirect', '/auth/login');
        }
        $login_session = json_decode($login_session, true);
        $user = (new User())->where('id', $login_session['userid'])->first();
        if ($user === null) {
            return $response->withJson(['ret' => 0, 'msg' => I18n::trans('user.user_not_found', $locale)])->withHeader('HX-Redirect', '/auth/login');
        }
        return $response->withJson(FIDO::assertRequest($user));
    }

    public function fidoHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();
        $redis = (new Cache())->initRedis();
        $login_session = $redis->get('mfa_login_' . session_id());
        if ($login_session === false) {
            return $response->withJson(['ret' => 0, 'msg' => I18n::trans('user.login_session_expired', $locale)])->withHeader('HX-Redirect', '/auth/login');
        }
        $login_session = json_decode($login_session, true);
        $data = $this->antiXss->xss_clean((array) $request->getParsedBody());
        $user = (new User())->where('id', $login_session['userid'])->first();
        if ($user === null) {
            return $response->withJson(['ret' => 0, 'msg' => I18n::trans('user.user_not_found', $locale)])->withHeader('HX-Redirect', '/auth/login');
        }
        $result = FIDO::assertHandle($user, $data);
        if ($result['ret'] === 1) {
            $redis->del('mfa_login_' . session_id());
            $rememberMe = $login_session['remember_me'];
            $time = $rememberMe ? 86400 * (int) (Config::obtain('rememberMeDuration') ?? 7) : 3600;
            Auth::login($user->id, $time);
            $loginIp = new LoginIp();
            $loginIp->collectLoginIP($_SERVER['REMOTE_ADDR'], 0, $user->id);
            $user->last_login_time = time();
            $user->save();
            return $response->withJson(['ret' => 1, 'msg' => I18n::trans('user.login_success', $locale), 'redir' => $login_session['redir']]);
        }
        return $response->withJson($result);
    }
}
