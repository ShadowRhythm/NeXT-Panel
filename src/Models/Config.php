<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use function array_key_exists;
use function array_values;
use function defined;
use function dirname;
use function file_exists;
use function file_get_contents;
use function in_array;
use function is_array;
use function json_decode;
use function json_encode;
use function preg_match;
use function preg_split;
use function trim;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @property int    $id
 * @property string $item
 * @property string $value
 * @property string $class
 * @property string $is_public
 * @property string $type
 * @property string $default
 * @property string $mark
 *
 * @mixin Builder
 */
final class Config extends Model
{
    protected $connection = 'default';
    protected $table = 'config';

    private static array $cache = [];
    private static ?array $settingsJsonCache = null;

    public static function parseArrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null || $value === '') {
            return [];
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '[]') {
            return [];
        }

        $decoded = json_decode($str, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }

        if (preg_match('/^array\s*\((.*)\)$/is', $str, $matches)) {
            $str = $matches[1];
        }

        $parts = preg_split('/[,，\r\n]+/u', $str);
        if ($parts === false) {
            return [];
        }

        $result = [];
        foreach ($parts as $part) {
            $cleaned = trim(trim($part), "'\"");
            if ($cleaned !== '') {
                $result[] = $cleaned;
            }
        }

        return $result;
    }

    private static function getJsonSettings(): array
    {
        if (self::$settingsJsonCache !== null) {
            return self::$settingsJsonCache;
        }

        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $filePath = $basePath . '/config/settings.json';
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $decoded = json_decode((string) $content, true);
            if (is_array($decoded)) {
                return self::$settingsJsonCache = $decoded;
            }
        }

        return self::$settingsJsonCache = [];
    }

    private static function findDefaultConfig(string $item): ?array
    {
        foreach (self::getJsonSettings() as $setting) {
            if (($setting['item'] ?? '') === $item) {
                return $setting;
            }
        }

        return null;
    }

    public static function obtain($item): bool|int|array|string
    {
        if (array_key_exists($item, self::$cache)) {
            return self::$cache[$item];
        }

        $cacheKey = 'config_' . $item;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            self::$cache[$item] = $cached;
            return $cached;
        }

        $config = (new Config())->where('item', $item)->first();

        if ($config === null) {
            $default = self::findDefaultConfig($item);
            if ($default !== null) {
                $type = $default['type'] ?? 'string';
                $defaultVal = $default['default'] ?? $default['value'] ?? '';
                $val = match ($type) {
                    'bool' => (bool) $defaultVal,
                    'int' => (int) $defaultVal,
                    'array' => self::parseArrayValue($defaultVal),
                    default => (string) $defaultVal,
                };
            } else {
                $val = '';
            }

            self::$cache[$item] = $val;
            Cache::set($cacheKey, $val, 300);
            return $val;
        }

        $val = match ($config->type) {
            'bool' => (bool) $config->value,
            'int' => (int) $config->value,
            'array' => self::parseArrayValue($config->value),
            default => (string) ($config->value ?? ''),
        };

        self::$cache[$item] = $val;
        Cache::set($cacheKey, $val, 300);

        return $val;
    }

    public static function getClass($class): array
    {
        $configs = [];
        $all_configs = (new Config())->where('class', $class)->get();

        foreach ($all_configs as $config) {
            $configs[$config->item] = match ($config->type) {
                'bool' => (bool) $config->value,
                'int' => (int) $config->value,
                'array' => self::parseArrayValue($config->value),
                default => (string) ($config->value ?? ''),
            };
        }

        foreach (self::getJsonSettings() as $setting) {
            $item = $setting['item'] ?? '';
            if (($setting['class'] ?? '') === $class && $item !== '' && ! array_key_exists($item, $configs)) {
                $type = $setting['type'] ?? 'string';
                $default = $setting['default'] ?? $setting['value'] ?? '';
                $configs[$item] = match ($type) {
                    'bool' => (bool) $default,
                    'int' => (int) $default,
                    'array' => self::parseArrayValue($default),
                    default => (string) $default,
                };
            }
        }

        return $configs;
    }

    public static function getItemListByClass($class): array
    {
        $items = [];
        $all_configs = (new Config())->where('class', $class)->get();

        foreach ($all_configs as $config) {
            $items[] = $config->item;
        }

        foreach (self::getJsonSettings() as $setting) {
            if (($setting['class'] ?? '') === $class && ! in_array($setting['item'], $items, true)) {
                $items[] = $setting['item'];
            }
        }

        return $items;
    }

    public static function getPublicConfig(): array
    {
        $configs = [];
        $all_configs = (new Config())->where('is_public', '1')->get();

        foreach ($all_configs as $config) {
            $configs[$config->item] = match ($config->type) {
                'bool' => (bool) $config->value,
                'int' => (int) $config->value,
                'array' => self::parseArrayValue($config->value),
                default => (string) ($config->value ?? ''),
            };
        }

        return $configs;
    }

    public static function set(string $item, mixed $value): bool
    {
        $config = (new Config())->where('item', $item)->first();

        if ($config === null) {
            $default = self::findDefaultConfig($item);
            $config = new Config();
            $config->item = $item;
            $config->class = (string) ($default['class'] ?? 'system');
            $config->is_public = (int) ($default['is_public'] ?? 0);
            $config->type = (string) ($default['type'] ?? (is_array($value) ? 'array' : 'string'));
            $config->default = (string) ($default['default'] ?? '');
            $config->mark = (string) ($default['mark'] ?? '');
        }

        if ($config->type === 'array') {
            $parsed = self::parseArrayValue($value);
            $config->value = json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($config->type === 'bool') {
            $config->value = (bool) $value ? '1' : '0';
        } elseif ($config->type === 'int') {
            $config->value = (string) (int) $value;
        } else {
            $config->value = (string) ($value ?? '');
        }

        try {
            $config->save();
            unset(self::$cache[$item]);
            Cache::forget('config_' . $item);
        } catch (QueryException $e) {
            return false;
        }

        return true;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
        self::$settingsJsonCache = null;
    }
}
