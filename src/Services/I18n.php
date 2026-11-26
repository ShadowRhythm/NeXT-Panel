<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Translator;
use function basename;
use function class_exists;
use function explode;
use function file_exists;
use function glob;
use function in_array;
use function is_dir;
use function sort;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function trim;
use function usort;
use const BASE_PATH;
use const GLOB_ONLYDIR;

final class I18n
{
    private static array $translators = [];

    // trans() right is human right 🏳️‍⚧️
    public static function trans(string $key, ?string $lang = null, array $params = []): string
    {
        $lang = $lang ?? self::getCurrentLocale();
        $translator = self::getTranslator($lang);
        $catalogue = $translator->getCatalogue($lang);

        $normalizedParams = [];
        foreach ($params as $k => $v) {
            $strK = (string) $k;
            if (str_starts_with($strK, '%') && str_ends_with($strK, '%')) {
                $normalizedParams[$strK] = $v;
            } else {
                $normalizedParams['%' . $strK . '%'] = $v;
            }
        }

        if (str_contains($key, '.')) {
            [$domain, $subKey] = explode('.', $key, 2);
            if ($catalogue->has($subKey, $domain)) {
                return $translator->trans($subKey, $normalizedParams, $domain, $lang);
            }
        }

        if ($catalogue->has($key, 'messages')) {
            return $translator->trans($key, $normalizedParams, 'messages', $lang);
        }

        return $translator->trans($key, $normalizedParams, 'messages', $lang);
    }

    public static function getLocaleList(): array
    {
        $locales = [];
        $files = glob(BASE_PATH . '/resources/locale/*.php');

        if ($files !== false) {
            foreach ($files as $file) {
                $locales[] = basename($file, '.php');
            }
        }

        $dirs = glob(BASE_PATH . '/resources/locale/*', GLOB_ONLYDIR);
        if ($dirs !== false) {
            foreach ($dirs as $dir) {
                $localeName = basename($dir);
                if (! in_array($localeName, $locales, true)) {
                    $locales[] = $localeName;
                }
            }
        }

        sort($locales);

        return $locales;
    }

    public static function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::getLocaleList(), true);
    }

    public static function isRtlLocale(?string $locale = null): bool
    {
        $locale = $locale ?? self::getCurrentLocale();
        $normalized = str_replace('_', '-', strtolower(trim($locale)));

        return str_starts_with($normalized, 'fa')
            || str_starts_with($normalized, 'ar')
            || str_starts_with($normalized, 'he')
            || str_starts_with($normalized, 'ur');
    }

    public static function getCurrentLocale(?User $user = null): string
    {
        $explicitUser = $user !== null;

        if (! $explicitUser && class_exists(Auth::class)) {
            try {
                $authUser = Auth::getUser();
                if ($authUser->isLogin) {
                    $user = $authUser;
                }
            } catch (\Throwable) {
                // ignore
            }
        }

        // 1. Explicit target user or logged-in session user preference
        if ($user !== null && ($explicitUser || $user->isLogin) && ! empty($user->locale) && self::isValidLocale($user->locale)) {
            return $user->locale;
        }

        // 2. Cookie preference
        if (! empty($_COOKIE['locale']) && self::isValidLocale($_COOKIE['locale'])) {
            return $_COOKIE['locale'];
        }

        // 3. Browser Accept-Language header
        $autoDetect = isset($_ENV['locale_auto_detect']) ? (bool) $_ENV['locale_auto_detect'] : true;
        if ($autoDetect && ! empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLocale = self::parseAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($browserLocale !== null && self::isValidLocale($browserLocale)) {
                return $browserLocale;
            }
        }

        // 4. Default locale from environment
        $defaultLocale = $_ENV['locale'] ?? 'en_US';

        return self::isValidLocale($defaultLocale) ? $defaultLocale : 'en_US';
    }

    public static function parseAcceptLanguage(string $header): ?string
    {
        if (trim($header) === '') {
            return null;
        }

        $languages = [];
        foreach (explode(',', $header) as $entry) {
            $parts = explode(';', trim($entry));
            $tag = trim($parts[0]);
            if ($tag === '') {
                continue;
            }
            $q = 1.0;
            if (isset($parts[1])) {
                $qParts = explode('=', trim($parts[1]));
                if (isset($qParts[0], $qParts[1]) && trim($qParts[0]) === 'q') {
                    $q = (float) trim($qParts[1]);
                }
            }
            $languages[] = ['tag' => $tag, 'q' => $q];
        }

        usort($languages, static fn ($a, $b) => $b['q'] <=> $a['q']);

        foreach ($languages as $lang) {
            $mapped = self::mapLanguageTag($lang['tag']);
            if ($mapped !== null && self::isValidLocale($mapped)) {
                return $mapped;
            }
        }

        return null;
    }

    public static function mapLanguageTag(string $tag): ?string
    {
        $normalized = str_replace('_', '-', strtolower(trim($tag)));

        // Simplified Chinese subtags: zh-CN, zh-Hans, zh-SG
        if (
            $normalized === 'zh-cn' ||
            $normalized === 'zh-hans' ||
            $normalized === 'zh-sg' ||
            str_starts_with($normalized, 'zh-hans-') ||
            str_starts_with($normalized, 'zh-cn-')
        ) {
            return 'zh_CN';
        }

        // Traditional Chinese subtags: zh-TW, zh-Hant, zh-HK, zh-MO
        if (
            $normalized === 'zh-tw' ||
            $normalized === 'zh-hant' ||
            $normalized === 'zh-hk' ||
            $normalized === 'zh-mo' ||
            str_starts_with($normalized, 'zh-hant-') ||
            str_starts_with($normalized, 'zh-tw-') ||
            str_starts_with($normalized, 'zh-hk-') ||
            str_starts_with($normalized, 'zh-mo-')
        ) {
            return 'zh_TW';
        }

        // zh default
        if ($normalized === 'zh') {
            return 'zh_CN';
        }

        // Japanese subtags: ja*
        if (str_starts_with($normalized, 'ja')) {
            return 'ja_JP';
        }

        // English subtags: en*
        if (str_starts_with($normalized, 'en')) {
            return 'en_US';
        }

        // Russian subtags: ru*
        if (str_starts_with($normalized, 'ru')) {
            return 'ru_RU';
        }

        // French subtags: fr*
        if (str_starts_with($normalized, 'fr')) {
            return 'fr_FR';
        }

        // Portuguese subtags: pt*
        if (str_starts_with($normalized, 'pt')) {
            return 'pt_BR';
        }

        // Spanish subtags: es*
        if (str_starts_with($normalized, 'es')) {
            return 'es_ES';
        }

        // Vietnamese subtags: vi*
        if (str_starts_with($normalized, 'vi')) {
            return 'vi_VN';
        }

        // German subtags: de*
        if (str_starts_with($normalized, 'de')) {
            return 'de_DE';
        }

        // Persian/Farsi subtags: fa*
        if (str_starts_with($normalized, 'fa')) {
            return 'fa_IR';
        }

        // Turkish subtags: tr*
        if (str_starts_with($normalized, 'tr')) {
            return 'tr_TR';
        }

        // Arabic subtags: ar*
        if (str_starts_with($normalized, 'ar')) {
            return 'ar_SA';
        }

        // Indonesian subtags: id* (and legacy in*)
        if (str_starts_with($normalized, 'id') || $normalized === 'in' || str_starts_with($normalized, 'in-')) {
            return 'id_ID';
        }

        // Korean subtags: ko*
        if (str_starts_with($normalized, 'ko')) {
            return 'ko_KR';
        }

        $standardized = str_replace('-', '_', $tag);
        if (self::isValidLocale($standardized)) {
            return $standardized;
        }

        // Fallback: match primary language subtag (e.g. tr -> tr_TR, ko -> ko_KR) against available locales
        $primary = explode('-', $normalized)[0];
        if ($primary !== '') {
            foreach (self::getLocaleList() as $available) {
                $availPrimary = strtolower(explode('_', $available)[0]);
                if ($availPrimary === $primary) {
                    return $available;
                }
            }
        }

        return null;
    }

    public static function getTranslator(string $lang = 'en_US'): Translator
    {
        if (isset(self::$translators[$lang])) {
            return self::$translators[$lang];
        }

        $translator = new Translator($lang);
        $translator->addLoader('php', new PhpFileLoader());

        // 1. Flat file as messages domain
        $flatFile = BASE_PATH . '/resources/locale/' . $lang . '.php';
        if (file_exists($flatFile)) {
            $translator->addResource(
                'php',
                $flatFile,
                $lang,
                'messages'
            );
        }

        // 2. Modular domain files: resources/locale/{locale}/{domain}.php
        $domainDir = BASE_PATH . '/resources/locale/' . $lang;
        if (is_dir($domainDir)) {
            $files = glob($domainDir . '/*.php');
            if ($files !== false) {
                foreach ($files as $file) {
                    $domain = basename($file, '.php');
                    $translator->addResource(
                        'php',
                        $file,
                        $lang,
                        $domain
                    );
                }
            }
        }

        self::$translators[$lang] = $translator;

        return $translator;
    }

    public static function resetTranslators(): void
    {
        self::$translators = [];
    }
}

