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
use function headers_sent;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function mb_strlen;
use function mb_substr;
use function session_id;
use function session_start;
use function session_status;
use function str_starts_with;
use function strlen;
use function strtolower;
use function time;
use function trim;
use const PHP_SESSION_NONE;

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

        $locale = I18n::getCurrentLocale();
        $oauthErrorCode = (string) ($request->getQueryParams()['oauth_error'] ?? '');
        $oauthError = match ($oauthErrorCode) {
            'oauth_disabled' => I18n::trans('auth.oauth_disabled', $locale),
            'email_unverified' => I18n::trans('auth.oauth_email_unverified', $locale),
            'invalid_email' => I18n::trans('user.invalid_email', $locale),
            'reg_closed' => I18n::trans('user.reg_closed', $locale),
            'session_expired' => I18n::trans('user.login_session_expired', $locale),
            'please_login' => I18n::trans('auth.please_login_first', $locale),
            'request_failed' => I18n::trans('auth.oauth_request_failed', $locale),
            default => '',
        };

        return $response->write($this->view()
            ->assign('base_url', $_ENV['baseUrl'])
            ->assign('captcha', $captcha)
            ->assign('oauth_error', $oauthError)
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
            self::startSessionIfNeeded();
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

        self::performLogin($user, $rememberMe);

        return $response->withHeader('HX-Redirect', $redir);
    }

    public static function startSessionIfNeeded(): void
    {
        if (session_status() === PHP_SESSION_NONE && ! headers_sent()) {
            session_start();
        }
    }

    public static function performLogin(User $user, int $rememberMe = 0): void
    {
        $time = $rememberMe ? 86400 * (int) (Config::obtain('rememberMeDuration') ?? 7) : 3600;

        Auth::login($user->id, $time);
        if (! empty($user->locale) && I18n::isValidLocale($user->locale)) {
            Cookie::set(['locale' => $user->locale], time() + 86400 * 365);
        }
        (new LoginIp())->collectLoginIP($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 0, $user->id);
        $user->last_login_time = time();
        $user->is_inactive = 0;
        $user->save();
    }

    /**
     * Complete login or trigger site MFA if enabled for the user.
     *
     * @return array{mfa: bool, redir: string}
     */
    public static function completeLogin(User $user, int $rememberMe = 0, string $redir = '/user'): array
    {
        $mfaStatus = $user->checkMfaStatus();
        if ($mfaStatus['require']) {
            self::startSessionIfNeeded();
            $redis = (new Cache())->initRedis();
            $redis->setex('mfa_login_' . session_id(), 300, json_encode([
                'userid' => $user->id,
                'method' => $mfaStatus,
                'redir' => $redir,
                'remember_me' => $rememberMe,
            ]));

            return [
                'mfa' => true,
                'redir' => '/auth/mfa',
            ];
        }

        self::performLogin($user, $rememberMe);

        return [
            'mfa' => false,
            'redir' => $redir,
        ];
    }

    public function mfaPage(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        self::startSessionIfNeeded();
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

        $invite_code = trim((string) $this->antiXss->xss_clean($request->getParam('code')));
        if ($invite_code !== '') {
            Cookie::set(['invite_code' => $invite_code], time() + 86400);
        } else {
            $cookieInvite = trim((string) $this->antiXss->xss_clean((string) (Cookie::get('invite_code') ?? '')));
            if ($cookieInvite !== '') {
                $invite_code = $cookieInvite;
            }
        }

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

            $remoteAddr = (string) ($request->getServerParam('REMOTE_ADDR') ?? '');
            try {
                if (! (new RateLimit())->checkRateLimit('email_request_ip', $remoteAddr) ||
                    ! (new RateLimit())->checkRateLimit('email_request_address', $email)
                ) {
                    return ResponseHelper::error($response, I18n::trans('user.request_too_frequent', $locale));
                }
            } catch (Throwable) {
                // Ignore rate limit failure
            }

            $user = (new User())->where('email', $email)->first();

            if ($user !== null) {
                return ResponseHelper::error($response, I18n::trans('user.email_already_registered', $locale));
            }

            $email_code = Tools::genRandomNum(6);
            $ttl = (int) (Config::obtain('email_verify_code_ttl') ?: 3600);
            if ($ttl <= 0) {
                $ttl = 3600;
            }

            try {
                $redis = (new Cache())->initRedis();
                $redis->setex('email_verify:' . $email_code, $ttl, $email);
            } catch (Throwable) {
                return ResponseHelper::error($response, I18n::trans('user.email_send_failed_contact_admin', $locale));
            }

            try {
                Mail::send(
                    $email,
                    I18n::trans('email.verify_email_subject', $locale, ['%app_name%' => $_ENV['appName']]),
                    'verify_code.tpl',
                    [
                        'code' => $email_code,
                        'expire' => date('Y-m-d H:i:s', time() + $ttl),
                    ]
                );
            } catch (Throwable) {
                return ResponseHelper::error($response, I18n::trans('user.email_send_failed_contact_admin', $locale));
            }

            return ResponseHelper::success($response, I18n::trans('user.verify_code_sent', $locale));
        }

        return ResponseHelper::error($response, I18n::trans('user.reg_email_verify_disabled', $locale));
    }

    /**
     * Validate an invite code against existing inviter records.
     */
    public static function resolveValidInviteUserId(string $inviteCode): ?int
    {
        $inviteCode = trim($inviteCode);
        if ($inviteCode === '') {
            return null;
        }

        $invite = (new InviteCode())->where('code', $inviteCode)->first();
        if ($invite === null) {
            return null;
        }

        $refUser = (new User())->where('id', $invite->user_id)->first();
        if ($refUser === null || ! $refUser->getInviteEligibility()['allowed']) {
            return null;
        }

        return (int) $invite->user_id;
    }

    /**
     * Create and persist a new User record with site registration defaults.
     *
     * @param array<string, mixed> $extraAttributes
     */
    public static function createUserAccount(
        string $name,
        string $email,
        string $password,
        string $invite_code = '',
        int $imtype = 0,
        string $imvalue = '',
        float $money = 0.0,
        bool $issueReward = true,
        array $extraAttributes = []
    ): User {
        $configs = Config::getClass('reg');
        $user = new User();

        $cleanName = trim($name);
        if ($cleanName === '') {
            $cleanName = explode('@', $email)[0];
        }
        $user->user_name = mb_substr($cleanName, 0, 32);
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
        $user->im_type = $imtype;
        $user->im_value = $imvalue;
        $user->transfer_enable = Tools::gbToB((int) ($configs['reg_traffic'] ?? 0));
        $user->auto_reset_day = (int) (Config::obtain('free_user_reset_day') ?? 0);
        $user->auto_reset_bandwidth = (float) (Config::obtain('free_user_reset_bandwidth') ?? 0);
        $user->daily_mail_enable = (int) ($configs['reg_daily_report'] ?? 0);
        $user->money = $money > 0 ? $money : 0.0;
        $user->ref_by = self::resolveValidInviteUserId($invite_code) ?? 0;
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

        foreach ($extraAttributes as $attr => $val) {
            $user->$attr = $val;
        }

        $user->save();

        if ($issueReward && $user->ref_by !== 0) {
            try {
                Reward::issueRegReward($user->id, $user->ref_by);
            } catch (Throwable $e) {
                error_log('Issue reg reward error: ' . $e->getMessage());
            }
        }

        return $user;
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
        $locale = (string) I18n::getCurrentLocale();
        $redir_cookie = Cookie::get('redir');
        $redir = ($redir_cookie !== null && $redir_cookie !== '') ? $this->antiXss->xss_clean($redir_cookie) : '/user';

        if (! str_starts_with($redir, '/') || str_starts_with($redir, '//')) {
            $redir = '/user';
        }

        try {
            $user = self::createUserAccount(
                (string) $name,
                (string) $email,
                (string) $password,
                (string) $invite_code,
                (int) $imtype,
                (string) $imvalue,
                (float) $money,
                ! $is_admin_reg
            );
        } catch (Throwable $e) {
            error_log('User registration database save error: ' . $e->getMessage());
            $errorMsg = I18n::trans('user.unknown_error', $locale);
            if (! empty($_ENV['debug'])) {
                $errorMsg .= ': ' . $e->getMessage();
            }
            return ResponseHelper::error($response, $errorMsg);
        }

        if ($is_admin_reg) {
            return ResponseHelper::success($response, 'ok');
        }

        try {
            Auth::login($user->id, 3600);
            Cookie::set(['locale' => $user->locale], time() + 86400 * 365);
            Cookie::set(['invite_code' => ''], time() - 3600);
            (new LoginIp())->collectLoginIP($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 0, $user->id);
        } catch (Throwable $e) {
            error_log('Post-reg login/cookie error: ' . $e->getMessage());
        }

        return $response->withHeader('HX-Redirect', $redir)->withJson([
            'ret' => 1,
            'msg' => 'ok',
        ]);
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

        if ($invite_code !== '' && self::resolveValidInviteUserId($invite_code) === null) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_invite_code', $locale));
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
            $user->is_inactive = 0;
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
        self::startSessionIfNeeded();
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
            $user->is_inactive = 0;
            $user->save();
            return $response
                ->withHeader('HX-Redirect', $login_session['redir'])
                ->withJson(['ret' => 1, 'msg' => I18n::trans('user.login_success', $locale)]);
        }
        return $response->withJson($result);
    }

    public function fidoRequest(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        self::startSessionIfNeeded();
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
        self::startSessionIfNeeded();
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
            $user->is_inactive = 0;
            $user->save();
            return $response->withJson(['ret' => 1, 'msg' => I18n::trans('user.login_success', $locale), 'redir' => $login_session['redir']]);
        }
        return $response->withJson($result);
    }

    /**
     * Render invite-code completion form after OAuth authorization when reg_mode is invite.
     *
     * @throws Exception
     */
    public function oauthInvitePage(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $token = trim((string) $this->antiXss->xss_clean((string) ($request->getParam('token') ?? '')));
        if ($token === '' || Config::obtain('reg_mode') !== 'invite') {
            return $response->withStatus(302)->withHeader('Location', '/auth/login');
        }

        $redis = (new Cache())->initRedis();
        $raw = $redis->get('oauth_pending:' . $token);
        if ($raw === false) {
            return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=session_expired');
        }

        $pending = json_decode((string) $raw, true);
        if (! is_array($pending) || empty($pending['email'])) {
            return $response->withStatus(302)->withHeader('Location', '/auth/login?oauth_error=request_failed');
        }

        $inviteCode = trim((string) $this->antiXss->xss_clean((string) (Cookie::get('invite_code') ?? '')));

        return $response->write(
            $this->view()
                ->assign('token', $token)
                ->assign('provider', (string) ($pending['provider'] ?? ''))
                ->assign('oauth_email', (string) ($pending['email'] ?? ''))
                ->assign('oauth_name', (string) ($pending['name'] ?? ''))
                ->assign('invite_code', $inviteCode)
                ->fetch('auth/oauth_invite.tpl')
        );
    }

    /**
     * Complete OAuth user creation after verifying an invite code.
     *
     * @throws RedisException
     */
    public function oauthInviteHandle(ServerRequest $request, Response $response, $next): ResponseInterface
    {
        $locale = I18n::getCurrentLocale();

        if (Config::obtain('reg_mode') === 'close') {
            return ResponseHelper::error($response, I18n::trans('user.reg_closed', $locale));
        }

        $token = trim((string) $this->antiXss->xss_clean((string) $request->getParam('token')));
        $inviteCode = trim((string) $this->antiXss->xss_clean((string) $request->getParam('invite_code')));
        $tos = $request->getParam('tos') === 'true' || $request->getParam('tos') === '1';

        if (! $tos) {
            return ResponseHelper::error($response, I18n::trans('user.tos_not_agreed', $locale));
        }

        if ($inviteCode === '') {
            return ResponseHelper::error($response, I18n::trans('user.invite_code_empty', $locale));
        }

        if (self::resolveValidInviteUserId($inviteCode) === null) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_invite_code', $locale));
        }

        $redis = (new Cache())->initRedis();
        $raw = $token !== '' ? $redis->get('oauth_pending:' . $token) : false;
        if ($raw === false) {
            return ResponseHelper::error($response, I18n::trans('user.login_session_expired', $locale));
        }

        $pending = json_decode((string) $raw, true);
        if (! is_array($pending) || empty($pending['provider']) || empty($pending['provider_user_id']) || empty($pending['email'])) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $provider = (string) $pending['provider'];
        $providerUserId = (string) $pending['provider_user_id'];
        $email = strtolower(trim((string) $pending['email']));
        $name = (string) ($pending['name'] ?? '');
        $label = mb_substr(trim((string) ($pending['label'] ?? '')), 0, 191);
        $column = $provider . '_id';
        $labelColumn = $provider . '_label';

        if (! in_array($provider, ['google', 'github'], true)) {
            return ResponseHelper::error($response, I18n::trans('auth.oauth_request_failed', $locale));
        }

        $existing = (new User())->where($column, $providerUserId)->orWhere('email', $email)->first();
        if ($existing !== null) {
            $redis->del('oauth_pending:' . $token);
            $dirty = false;
            if (empty($existing->$column)) {
                $existing->$column = $providerUserId;
                $dirty = true;
            }
            if ($label !== '' && $existing->$labelColumn !== $label) {
                $existing->$labelColumn = $label;
                $dirty = true;
            }
            if ($dirty) {
                $existing->save();
            }
            $loginResult = self::completeLogin($existing, 1, '/user');
            Cookie::set(['invite_code' => ''], time() - 3600);

            return $response->withHeader('HX-Redirect', $loginResult['redir'])->withJson([
                'ret' => 1,
                'msg' => I18n::trans('user.login_success', $locale),
            ]);
        }

        try {
            $user = self::createUserAccount(
                $name,
                $email,
                Tools::genRandomChar(32),
                $inviteCode,
                0,
                '',
                0.0,
                true,
                [
                    $column => $providerUserId,
                    $labelColumn => ($label !== '' ? $label : null),
                ]
            );
        } catch (Throwable $e) {
            error_log('OAuth invite registration error: ' . $e->getMessage());
            return ResponseHelper::error($response, I18n::trans('user.unknown_error', $locale));
        }

        $redis->del('oauth_pending:' . $token);
        Cookie::set(['invite_code' => ''], time() - 3600);

        $loginResult = self::completeLogin($user, 1, '/user');

        return $response->withHeader('HX-Redirect', $loginResult['redir'])->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.login_success', $locale),
        ]);
    }
}
