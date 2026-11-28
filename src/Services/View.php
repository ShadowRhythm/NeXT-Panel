<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use App\Models\User;
use App\Utils\Tools;
use Illuminate\Database\DatabaseManager;
use Smarty\Smarty;
use function in_array;
use function is_dir;
use function is_string;
use const BASE_PATH;

final class View
{
    public static DatabaseManager $connection;
    public static float $beginTime;

    public static function getSmarty(): Smarty
    {
        $smarty = new Smarty(); //实例化smarty
        $user = Auth::getUser();
        $currentLocale = I18n::getCurrentLocale($user);

        $smarty->setTemplateDir(BASE_PATH . '/resources/views/' . self::getTheme($user) . '/'); //设置模板文件存放目录
        $smarty->setCompileDir(BASE_PATH . '/storage/framework/smarty/compile/'); //设置生成文件存放目录
        $smarty->setCacheDir(BASE_PATH . '/storage/framework/smarty/cache/'); //设置缓存文件存放目录
        // add config
        $smarty->assign('config', self::getConfig($user));
        $smarty->assign('public_setting', Config::getPublicConfig());
        $smarty->assign('user', $user);
        $smarty->assign('is_impersonating', Auth::isImpersonating());
        $smarty->assign('current_locale', $currentLocale);
        $smarty->assign('is_rtl', I18n::isRtlLocale($currentLocale));

        self::registerSmartyI18n($smarty, $currentLocale);

        return $smarty;
    }

    public static function registerSmartyI18n(Smarty $smarty, string $currentLocale): void
    {
        // Smarty 5 i18n function plugin: {trans key='auth.login_title' escape='js'}
        $smarty->registerPlugin('function', 'trans', static function (array $params) use ($currentLocale): string {
            $key = $params['key'] ?? '';
            $lang = $params['lang'] ?? $currentLocale;
            $escape = $params['escape'] ?? null;
            unset($params['key'], $params['lang'], $params['escape']);

            $result = I18n::trans($key, $lang, $params);
            if ($escape === 'javascript' || $escape === 'js') {
                return strtr($result, [
                    '\\' => '\\\\',
                    "'" => "\\'",
                    '"' => '\\"',
                    "\r" => '\\r',
                    "\n" => '\\n',
                    '</' => '<\/',
                ]);
            }
            if ($escape === 'html') {
                return htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            return $result;
        });

        // Smarty 5 i18n modifier plugin: {'auth.login_title'|trans:'en_US':[]:'js'}
        $smarty->registerPlugin('modifier', 'trans', static function (string $key, ?string $lang = null, array $params = [], ?string $escape = null) use ($currentLocale): string {
            $result = I18n::trans($key, $lang ?? $currentLocale, $params);
            if ($escape === 'javascript' || $escape === 'js') {
                return strtr($result, [
                    '\\' => '\\\\',
                    "'" => "\\'",
                    '"' => '\\"',
                    "\r" => '\\r',
                    "\n" => '\\n',
                    '</' => '<\/',
                ]);
            }
            if ($escape === 'html') {
                return htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            return $result;
        });
    }

    public static function getTheme($user): string
    {
        $allowed = Tools::getDir(BASE_PATH . '/resources/views');
        $theme = $user->isLogin ? $user->theme : ($_ENV['theme'] ?? 'tabler');

        if (! is_string($theme) || ! in_array($theme, $allowed, true) || ! is_dir(BASE_PATH . '/resources/views/' . $theme)) {
            $fallback = $_ENV['theme'] ?? 'tabler';

            return is_string($fallback) && in_array($fallback, $allowed, true) && is_dir(BASE_PATH . '/resources/views/' . $fallback)
                ? $fallback
                : 'tabler';
        }

        return $theme;
    }

    public static function getConfig(?User $user = null): array
    {
        $currentLocale = I18n::getCurrentLocale($user);

        return [
            'appName' => $_ENV['appName'],
            'baseUrl' => $_ENV['baseUrl'],
            'jump_delay' => $_ENV['jump_delay'],
            'enable_kill' => Config::obtain('enable_kill'),
            'enable_change_email' => Config::obtain('enable_change_email'),
            'enable_r2_client_download' => Config::obtain('enable_r2_client_download'),
            'jsdelivr_url' => Config::obtain('jsdelivr_url'),
            'enable_telemetry' => $_ENV['enable_telemetry'] ?? true,
            // site default language
            'locale' => $currentLocale,
            'current_locale' => $currentLocale,
            'is_rtl' => I18n::isRtlLocale($currentLocale),
        ];
    }
}
