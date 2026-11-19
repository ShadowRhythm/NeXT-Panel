<?php

declare(strict_types=1);

namespace App\Utils;

use App\Models\Config;
use App\Models\User;
use App\Services\GeoIP2;
use App\Services\QQWry;
use App\Services\QQWryIpdb;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Str;
use MaxMind\Db\Reader\InvalidDatabaseException;
use function array_diff;
use function array_flip;
use function base64_encode;
use function closedir;
use function count;
use function date;
use function filter_var;
use function floor;
use function hash;
use function in_array;
use function is_numeric;
use function json_decode;
use function log;
use function max;
use function mb_strcut;
use function opendir;
use function pow;
use function random_int;
use function range;
use function readdir;
use function round;
use function shuffle;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_IP;
use const PHP_INT_MAX;

final class Tools
{
    /**
     * Get IP location
     */
    public static function getIpLocation(string $ip): string
    {
        $data = 'Unknown';

        // 1. 先用 MaxMind 判断国家（如果已配置）
        $country = null;
        $state   = null;
        $city    = null;

        if (Config::obtain('maxmind_license_key') !== '') {
            try {
                $geoip = new GeoIP2();
            } catch (InvalidDatabaseException) {
                $geoip = null;
            }

            if ($geoip !== null) {
                try {
                    $country = $geoip->getCountry($ip);
                } catch (AddressNotFoundException|InvalidDatabaseException) {
                    $country = null;
                }
                try {
                    $state = $geoip->getState($ip);
                } catch (AddressNotFoundException|InvalidDatabaseException) {
                    $state = null;
                }
                try {
                    $city = $geoip->getCity($ip);
                } catch (AddressNotFoundException|InvalidDatabaseException) {
                    $city = null;
                }
            }
        }

        // 2. 拼装 MaxMind 结果作为备用
        if ($country !== null) {
            $data = $country;
        }
        if ($state !== null) {
            $data = $state . ', ' . $country;
        }
        if ($city !== null) {
            $data = $state !== null
                ? $city . ', ' . $state . ', ' . $country
                : $city . ', ' . $country;
        }

        // 3. 判断是否中国 IP
        //    - MaxMind 已识别为 China → 走 qqwry
        //    - MaxMind 未配置/未识别 → 也尝试 qqwry（让 qqwry 自己决定能否命中）
        $isChina = ($country === 'China');
        $mmNotConfigured = (Config::obtain('maxmind_license_key') === '');

        // 4. qqwry.ipdb 优先处理中国 IP（含 IPv6）
        //    或 MaxMind 未配置时兜底全部 IP
        if ($isChina || $mmNotConfigured) {
            $ipdb = QQWryIpdb::getInstance();
            if ($ipdb !== null) {
                $map = $ipdb->findMap($ip, 'CN');
                if ($map !== null) {
                    $parts = array_filter([
                        $map['country_name']  ?? '',
                        $map['region_name']   ?? '',
                        $map['city_name']     ?? '',
                        $map['district_name'] ?? '',
                        $map['owner_domain']  ?? '',
                        $map['isp_domain']    ?? '',
                    ]);
                    $parts = array_unique($parts);
                    if ($parts !== []) {
                        return implode(' ', $parts);
                    }
                }
            }

            // qqwry.ipdb 未命中，降级到旧 qqwry.dat（仅 IPv4）
            if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                $iplocation = new QQWry();
                $location   = $iplocation->getlocation($ip);
                if ($location !== null && isset($location['country'], $location['area'])) {
                    $converted = @iconv('gbk', 'utf-8//IGNORE', $location['country'] . $location['area']);
                    if ($converted !== false && $converted !== '') {
                        return $converted;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Auto convert bytes to human readable format
     */
    public static function autoBytes($size, int $precision = 2): string
    {
        if ($size <= 0) {
            return '0B';
        }

        if ($size > PHP_INT_MAX) {
            return '∞';
        }

        $base = log((float) $size, 1024);
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        return round(pow(1024, $base - floor($base)), $precision) . $units[floor($base)];
    }

    /**
     * Convert human readable format to bytes
     */
    public static function autoBytesR(string $size): int
    {
        // Handle the special case where autoBytes(0) returns '0B',
        // which cannot be parsed by the double-char suffix logic below.
        if ($size === '0B') {
            return 0;
        }

        $suffix_single = substr($size, -1);

        if (is_numeric(substr($size, 0, -1)) && $suffix_single === 'B') {
            return (int) substr($size, 0, -1);
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        $suffix_double = substr($size, -2);

        if (! in_array($suffix_double, $units)) {
            return -1;
        }

        $base = substr($size, 0, strlen($size) - 2);

        if (! is_numeric($base) || ($base > 999 && $suffix_double === 'EB')) {
            return -1;
        }

        return (int) ($base * pow(1024, array_flip($units)[$suffix_double]));
    }

    /**
     * Auto convert bandwidth to human readable format
     */
    public static function autoMbps($size, int $precision = 2): string
    {
        if ($size <= 0) {
            return '0Bps';
        }

        if ($size > 1000000000) {
            return '∞';
        }

        $base = log((float) $size, 1000);
        $units = ['Mbps', 'Gbps', 'Tbps'];

        return round(pow(1000, $base - floor($base)), $precision) . $units[floor($base)];
    }

    public static function mbToB($traffic): int
    {
        if ($traffic <= 0 || $traffic > PHP_INT_MAX) {
            return 0;
        }

        return (int) $traffic * 1048576;
    }

    public static function gbToB($traffic): int
    {
        if ($traffic <= 0 || $traffic > PHP_INT_MAX) {
            return 0;
        }

        return (int) $traffic * 1073741824;
    }

    public static function bToMB($traffic): float
    {
        if ($traffic <= 0 || $traffic > PHP_INT_MAX) {
            return 0;
        }

        return round($traffic / 1048576, 2);
    }

    public static function bToGB($traffic): float
    {
        if ($traffic <= 0 || $traffic > PHP_INT_MAX) {
            return 0;
        }

        return round($traffic / 1073741824, 2);
    }

    public static function genPasswd(int $length = 16): string
    {
        return Str::random(max($length, 2));
    }

    public static function genRandomNum(int $length = 6): string
    {
        $length = max($length, 1);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= (string) random_int(0, 9);
        }

        return $result;
    }

    public static function genSubToken(): string
    {
        return self::genRandomChar(max((int) Config::obtain('sub_token_len'), 8));
    }

    public static function genRandomChar(int $length = 8): string
    {
        return strtolower(Str::random(max($length, 2)));
    }

    public static function genSs2022UserPk(string $passwd, string $method): string|false
    {
        $ss2022_methods = [
            '2022-blake3-aes-128-gcm',
            '2022-blake3-aes-256-gcm',
            '2022-blake3-chacha8-poly1305',
            '2022-blake3-chacha12-poly1305',
            '2022-blake3-chacha20-poly1305',
        ];

        if (! in_array($method, $ss2022_methods)) {
            return false;
        }

        $passwd_hash = hash('sha3-256', $passwd);

        $pk = match ($method) {
            '2022-blake3-aes-128-gcm' => mb_strcut($passwd_hash, 0, 16),
            default => mb_strcut($passwd_hash, 0, 32),
        };

        return base64_encode($pk);
    }

    public static function toDateTime(int $time): string
    {
        return date('Y-m-d H:i:s', $time);
    }

    public static function getSsPort(): int
    {
        $max_port = (int) Config::obtain('max_port');
        $min_port = (int) Config::obtain('min_port');

        if ($min_port >= 65535
            || $min_port <= 0
            || $max_port > 65535
            || $max_port <= 0
            || $min_port > $max_port
            || User::count() >= $max_port - $min_port + 1
        ) {
            return 0;
        }

        $det = (new User())->pluck('port')->toArray();
        $port = array_diff(range($min_port, $max_port), $det);
        if (empty($port)) {
            return 0;
        }
        shuffle($port);

        return $port[0];
    }

    /**
     * @param $dir
     *
     * @return array
     */
    public static function getDir($dir): array
    {
        $dirArray = [];
        $handle = opendir($dir);

        if ($handle !== false) {
            $i = 0;

            while (($file = readdir($handle)) !== false) {
                if ($file !== '.' && $file !== '..' && ! strpos($file, '.')) {
                    $dirArray[$i] = $file;
                    $i++;
                }
            }

            closedir($handle);
        }

        return $dirArray;
    }

    public static function isParamValidate($type, $str): bool
    {
        $list = self::getSsMethod($type);

        if (in_array($str, $list)) {
            return true;
        }

        return false;
    }

    public static function getSsMethod(string $type = ''): array
    {
        return match ($type) {
            'ss_obfs' => [
                'simple_obfs_http',
                'simple_obfs_http_compatible',
                'simple_obfs_tls',
                'simple_obfs_tls_compatible',
            ],
            default => [
                'aes-128-gcm',
                'aes-192-gcm',
                'aes-256-gcm',
                'chacha20-ietf-poly1305',
                'xchacha20-ietf-poly1305',
            ],
        };
    }

    public static function isEmail($input): bool
    {
        if (! filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    public static function isIPv4($input): bool
    {
        if (! filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        return true;
    }

    public static function isIPv6($input): bool
    {
        if (! filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }

        return true;
    }

    public static function isInt($input): bool
    {
        if (! filter_var($input, FILTER_VALIDATE_INT)) {
            return false;
        }

        return true;
    }

    /**
     * Check if string is valid JSON
     * TODO: Remove this function when PHP 8.3 is minimum requirement and replace it with native function
     */
    public static function isJson(string $string): bool
    {
        if (! json_decode($string)) {
            return false;
        }

        return true;
    }

    /**
     * Get site domain
     */
    public static function getSiteDomain(): string
    {
        return parse_url($_ENV['baseUrl'], PHP_URL_HOST);
    }
}
