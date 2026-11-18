<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\MFADevice;
use App\Models\User;
use App\Services\Auth;
use App\Services\Cache;
use App\Services\Filter;
use App\Services\I18n;
use App\Utils\Hash;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use RedisException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;
use function strlen;
use function strtolower;
use const BASE_PATH;

final class InfoController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $themes = Tools::getDir(BASE_PATH . '/resources/views');
        $methods = Tools::getSsMethod();
        $webauthnDevices = array_map(static fn ($item) => (object) $item, (new MFADevice())->where('userid', $this->user->id)->where('type', 'passkey')->get()->toArray());
        $totpDevices = array_map(static fn ($item) => (object) $item, (new MFADevice())->where('userid', $this->user->id)->where('type', 'totp')->get()->toArray());
        $fidoDevices = array_map(static fn ($item) => (object) $item, (new MFADevice())->where('userid', $this->user->id)->where('type', 'fido')->get()->toArray());

        return $response->write(
            $this->view()
                ->assign('user', $this->user)
                ->assign('themes', $themes)
                ->assign('methods', $methods)
                ->assign('webauthnDevices', $webauthnDevices)
                ->assign('totpDevices', $totpDevices)
                ->assign('fidoDevices', $fidoDevices)
                ->fetch('user/edit.tpl')
        );
    }

    /**
     * @throws RedisException
     */
    public function updateEmail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $new_email = $this->antiXss->xss_clean($request->getParam('newemail'));
        $user = $this->user;
        $old_email = $user->email;

        if (! Config::obtain('enable_change_email') || $user->is_shadow_banned) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        if ($new_email === '') {
            return ResponseHelper::error($response, I18n::trans('user.email_empty', $locale));
        }

        if (! Filter::checkEmailFilter($new_email)) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_email', $locale));
        }

        if ($new_email === $old_email) {
            return ResponseHelper::error($response, I18n::trans('user.new_email_same_as_old', $locale));
        }

        if ((new User())->where('email', $new_email)->first() !== null) {
            return ResponseHelper::error($response, I18n::trans('user.email_already_used', $locale));
        }

        if (Config::obtain('reg_email_verify')) {
            $redis = (new Cache())->initRedis();
            $email_verify_code = $request->getParam('emailcode');
            $email_verify = $redis->get('email_verify:' . $email_verify_code);

            if (! $email_verify) {
                return ResponseHelper::error($response, I18n::trans('user.email_verify_code_incorrect', $locale));
            }

            $redis->del('email_verify:' . $email_verify_code);
        }

        $user->email = $new_email;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.update_success', $locale),
            'data' => [
                'email' => $user->email,
            ],
        ]);
    }

    public function updateUsername(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $newusername = $this->antiXss->xss_clean($request->getParam('newusername'));
        $user = $this->user;

        if ($user->is_shadow_banned) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        $user->user_name = $newusername;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.update_success', $locale),
            'data' => [
                'username' => $user->user_name,
            ],
        ]);
    }

    public function unbindIm(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);

        if (! $this->user->unbindIM()) {
            return ResponseHelper::error($response, I18n::trans('user.unbind_failed', $locale));
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function updatePassword(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $password = $request->getParam('password');
        $new_password = $request->getParam('new_password');
        $confirm_new_password = $request->getParam('confirm_new_password');
        $user = $this->user;

        if ($password === '' || $new_password === '' || $confirm_new_password === '') {
            return ResponseHelper::error($response, I18n::trans('user.password_empty', $locale));
        }

        if (! Hash::checkPassword($user->pass, $password)) {
            return ResponseHelper::error($response, I18n::trans('user.wrong_old_password', $locale));
        }

        if ($new_password !== $confirm_new_password) {
            return ResponseHelper::error($response, I18n::trans('user.password_not_match', $locale));
        }

        if (strlen($new_password) < 8) {
            return ResponseHelper::error($response, I18n::trans('user.password_too_short', $locale));
        }

        $user->pass = Hash::passwordHash($new_password);

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        if (Config::obtain('enable_forced_replacement')) {
            $user->removeLink();
        }

        return ResponseHelper::success($response, I18n::trans('user.update_success', $locale));
    }

    public function resetPasswd(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $user = $this->user;
        $user->passwd = Tools::genPasswd(16);
        $user->uuid = (string) Uuid::uuid4();

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.reset_failed', $locale));
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.reset_success', $locale),
            'data' => [
                'passwd' => $user->passwd,
                'uuid' => $user->uuid,
            ],
        ]);
    }

    public function resetApiToken(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $user = $this->user;
        $user->api_token = Tools::genRandomChar(32);

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.reset_failed', $locale));
        }

        return ResponseHelper::success($response, I18n::trans('user.reset_success', $locale));
    }

    public function updateMethod(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $user = $this->user;
        $method = strtolower($this->antiXss->xss_clean($request->getParam('method')));

        if ($method === '') {
            return ResponseHelper::error($response, I18n::trans('user.invalid_input', $locale));
        }

        if (! Tools::isParamValidate('method', $method)) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_encryption_method', $locale));
        }

        $user->method = $method;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        return ResponseHelper::success($response, I18n::trans('user.update_success', $locale));
    }

    public function resetUrl(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $this->user->removeLink();

        return ResponseHelper::success($response, I18n::trans('user.reset_success', $locale));
    }

    public function updateDailyMail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $value = (int) $request->getParam('mail');

        if (! in_array($value, [0, 1, 2])) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_parameter', $locale));
        }

        $user = $this->user;
        $user->daily_mail_enable = $value;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        return ResponseHelper::success($response, I18n::trans('user.update_success', $locale));
    }

    public function updateContactMethod(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $value = (int) $request->getParam('contact');

        if (! in_array($value, [1, 2])) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_parameter', $locale));
        }

        $user = $this->user;
        $user->contact_method = $value;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        return ResponseHelper::success($response, I18n::trans('user.update_success', $locale));
    }

    public function updateTheme(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $theme = $this->antiXss->xss_clean($request->getParam('theme'));
        $user = $this->user;

        if ($theme === '') {
            return ResponseHelper::error($response, I18n::trans('user.theme_empty', $locale));
        }

        $user->theme = $theme;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.update_failed', $locale));
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function updateThemeMode(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $theme_mode = (int) $this->antiXss->xss_clean($request->getParam('theme_mode'));
        $user = $this->user;

        $user->is_dark_mode = in_array($theme_mode, [0, 1, 2]) ? $theme_mode : 0;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.switch_failed', $locale));
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function switchThemeMode(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $user = $this->user;
        $user->is_dark_mode = $user->is_dark_mode === 1 ? 0 : 1;

        if (! $user->save()) {
            return ResponseHelper::error($response, I18n::trans('user.switch_failed', $locale));
        }

        return $response->withHeader('HX-Refresh', 'true');
    }

    public function sendToGulag(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $user = $this->user;
        $password = $request->getParam('password');

        if ($password === '' || ! Hash::checkPassword($user->pass, $password)) {
            return ResponseHelper::error($response, I18n::trans('user.wrong_password', $locale));
        }

        if (Config::obtain('enable_kill')) {
            Auth::logout();
            $user->kill();

            return $response->withHeader('HX-Redirect', '/auth/login');
        }

        return ResponseHelper::error($response, I18n::trans('user.self_delete_disabled', $locale));
    }
}
