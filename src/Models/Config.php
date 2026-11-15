<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Cache;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use function array_key_exists;
use function is_array;
use function json_decode;
use function json_encode;

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
            self::$cache[$item] = '';
            Cache::set($cacheKey, '', 300);
            return '';
        }

        $val = match ($config->type) {
            'bool' => (bool) $config->value,
            'int' => (int) $config->value,
            'array' => json_decode($config->value),
            default => (string) $config->value,
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
                'array' => json_decode($config->value),
                default => (string) $config->value,
            };
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
                'array' => json_decode($config->value),
                default => (string) $config->value,
            };
        }

        return $configs;
    }

    public static function set(string $item, mixed $value): bool
    {
        if ($value === null) {
            $value = '';
        }

        $value = is_array($value) ? json_encode($value) : $value;

        try {
            (new Config())->where('item', $item)->update(['value' => $value]);
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
    }
}
