<?php

declare(strict_types=1);

use App\Utils\Tools;

beforeEach(function () {
    $this->originalEnv = $_ENV;
    $this->originalTimezone = date_default_timezone_get();
});

afterEach(function () {
    $_ENV = $this->originalEnv;
    date_default_timezone_set($this->originalTimezone);
});

describe('Tools::getIpLocation', function () {
    it('returns error message when maxmind service is not configured', function () {
        $_ENV['maxmind_license_key'] = '';
        
        $msg = Tools::getIpLocation('8.8.8.8');
        
        expect($msg)
            ->toBeString()
            ->toBe('GeoIP2 service not configured');
    });
});

describe('Tools::autoBytes', function () {
    it('converts bytes to human readable format', function () {
        $size = 1024;
        $bytes = Tools::autoBytes($size);
        
        expect($bytes)
            ->toBeString()
            ->toBe('1KB');
    });

    it('returns 0B for negative values', function () {
        $size = -1024;
        $bytes = Tools::autoBytes($size);
        
        expect($bytes)
            ->toBeString()
            ->toBe('0B');
    });
});

describe('Tools::autoBytesR', function () {
    it('converts human readable format to bytes', function () {
        expect(Tools::autoBytesR('1KB'))->toBe(1024)
            ->and(Tools::autoBytesR('1B'))->toBe(1);
    });

    it('returns -1 for invalid formats', function () {
        expect(Tools::autoBytesR('Highly illogical.'))->toBe(-1)
            ->and(Tools::autoBytesR('42069'))->toBe(-1)
            ->and(Tools::autoBytesR('1000EB'))->toBe(-1);
    });
});

describe('Tools::autoMbps', function () {
    it('converts bandwidth to human readable format', function () {
        expect(Tools::autoMbps(1))->toBe('1Mbps');
    });

    it('returns 0Bps for negative values', function () {
        expect(Tools::autoMbps(-1))->toBe('0Bps');
    });

    it('returns infinity symbol for very large values', function () {
        expect(Tools::autoMbps(1000000001))->toBe('∞');
    });
});

describe('Tools::mbToB', function () {
    it('converts megabytes to bytes', function () {
        $traffic = 1;
        $mb = 1048576;
        $result = Tools::mbToB($traffic);
        
        expect($result)
            ->toBeInt()
            ->toBe($traffic * $mb);
    });

    it('returns 0 for negative values', function () {
        expect(Tools::mbToB(-1))->toBe(0);
    });
});

describe('Tools::gbToB', function () {
    it('converts gigabytes to bytes', function () {
        $traffic = 1;
        $gb = 1048576 * 1024;
        $result = Tools::gbToB($traffic);
        
        expect($result)
            ->toBeInt()
            ->toBe($traffic * $gb);
    });

    it('returns 0 for negative values', function () {
        expect(Tools::gbToB(-1))->toBe(0);
    });
});

describe('Tools::bToGB', function () {
    it('converts bytes to gigabytes', function () {
        $traffic = 1048576 * 1024; // 1 GB in bytes
        $gb = 1048576 * 1024;
        $result = Tools::bToGB($traffic);
        
        expect($result)
            ->toBeFloat()
            ->toBe(1.0);
    });

    it('returns 0 for negative values', function () {
        expect(Tools::bToGB(-1))->toBe(0.0);
    });
});

describe('Tools::bToMB', function () {
    it('converts bytes to megabytes', function () {
        $traffic = 1048576; // 1 MB in bytes
        $mb = 1048576;
        $result = Tools::bToMB($traffic);
        
        expect($result)
            ->toBeFloat()
            ->toBe(1.0);
    });

    it('returns 0 for negative values', function () {
        expect(Tools::bToMB(-1))->toBe(0.0);
    });
});

describe('Tools::genSubToken', function () {
    it('generates token with specified length', function () {
        $_ENV['sub_token_len'] = 10;
        
        $token = Tools::genSubToken();
        
        expect(strlen($token))->toBe(10);
    });

    it('uses default length when ENV is 0 or negative', function () {
        $_ENV['sub_token_len'] = 0;
        expect(strlen(Tools::genSubToken()))->toBe(8);
        
        $_ENV['sub_token_len'] = -5;
        expect(strlen(Tools::genSubToken()))->toBe(8);
    });
});

describe('Tools::genRandomChar', function () {
    it('generates random string with default length', function () {
        $randomString = Tools::genRandomChar();
        
        expect($randomString)
            ->toBeString()
            ->and(strlen($randomString))->toBe(8);
    });

    it('generates random string with specified length', function () {
        $length = 10;
        $randomString = Tools::genRandomChar($length);
        
        expect($randomString)
            ->toBeString()
            ->and(strlen($randomString))->toBe($length);
    });

    it('enforces minimum length of 2', function () {
        $randomString = Tools::genRandomChar(1);
        
        expect($randomString)
            ->toBeString()
            ->and(strlen($randomString))->toBe(2);
    });

    it('generates lowercase alphanumeric string', function () {
        $randomString = Tools::genRandomChar(32);
        
        expect($randomString)
            ->toBeString()
            ->and(ctype_lower(preg_replace('/[0-9]/', '', $randomString)))->toBeTrue();
    });
});

describe('Tools::genPasswd', function () {
    it('generates random password with default length 16', function () {
        $passwd = Tools::genPasswd();
        
        expect($passwd)
            ->toBeString()
            ->and(strlen($passwd))->toBe(16);
    });

    it('generates random password with specified length', function () {
        $passwd = Tools::genPasswd(24);
        
        expect($passwd)
            ->toBeString()
            ->and(strlen($passwd))->toBe(24);
    });
});

describe('Tools::genRandomNum', function () {
    it('generates pure numeric code with default length 6', function () {
        $code = Tools::genRandomNum();
        
        expect($code)
            ->toBeString()
            ->and(strlen($code))->toBe(6)
            ->and(ctype_digit($code))->toBeTrue();
    });

    it('generates pure numeric code with specified length', function () {
        $code = Tools::genRandomNum(8);
        
        expect($code)
            ->toBeString()
            ->and(strlen($code))->toBe(8)
            ->and(ctype_digit($code))->toBeTrue();
    });
});

describe('Tools::genSs2022UserPk', function () {
    it('generates correct private key for AES-128', function () {
        $passwd = 'password';
        $method = '2022-blake3-aes-128-gcm';
        
        $pk = Tools::genSs2022UserPk($passwd, $method);
        
        expect($pk)
            ->toBeString()
            ->toBe('YzAwNjdkNGFmNGU4N2YwMA==');
    });

    it('generates correct private key for AES-256', function () {
        $passwd = 'password';
        $method = '2022-blake3-aes-256-gcm';
        
        $pk = Tools::genSs2022UserPk($passwd, $method);
        
        expect($pk)
            ->toBeString()
            ->toBe('YzAwNjdkNGFmNGU4N2YwMGRiYWM2M2I2MTU2ODI4MjM=');
    });

    it('returns false for invalid method', function () {
        $pk = Tools::genSs2022UserPk('password', 'bomb_three_gorges_dam');
        
        expect($pk)->toBeFalse();
    });
});

describe('Tools::toDateTime', function () {
    it('converts timestamp to datetime string', function () {
        expect(date_default_timezone_set('Etc/GMT-8'))->toBeTrue();
        
        $time = 612907200; // 1989-06-04 04:00:00 UTC+8
        $result = Tools::toDateTime($time);
        expect($result)
            ->toBeString()
            ->toBe('1989-06-04 04:00:00');
        
        $time = -1830412800; // 1912-01-01 00:00:00 UTC+8
        $result = Tools::toDateTime($time);
        expect($result)
            ->toBeString()
            ->toBe('1912-01-01 00:00:00');
    });
});

describe('Tools::getDir', function () {
    it('returns directory contents', function () {
        $dir1 = 'tests/testDir';
        
        $result1 = Tools::getDir($dir1);
        
        // Just check that it returns an array (directory structure may vary)
        expect($result1)->toBeArray();
    });

    it('returns .gitkeep for empty directory', function () {
        $dir2 = 'tests/testDir/emptyDir';
        
        $result2 = Tools::getDir($dir2);
        
        expect($result2)->toMatchArray(['.gitkeep']);
    });
});

describe('Tools::isParamValidate', function () {
    it('validates encryption parameters correctly', function () {
        expect(Tools::isParamValidate('default', 'aes-128-gcm'))->toBeTrue()
            ->and(Tools::isParamValidate('default', 'rc4-md5'))->toBeFalse();
    });
});

describe('Tools::getSsMethod', function () {
    it('returns ss_obfs methods', function () {
        $expected = [
            'simple_obfs_http',
            'simple_obfs_http_compatible',
            'simple_obfs_tls',
            'simple_obfs_tls_compatible',
        ];
        
        $result = Tools::getSsMethod('ss_obfs');
        
        expect($result)->toBe($expected);
    });

    it('returns default methods for default/random/empty string', function () {
        $expected = [
            'aes-128-gcm',
            'aes-192-gcm',
            'aes-256-gcm',
            'chacha20-ietf-poly1305',
            'xchacha20-ietf-poly1305',
        ];
        
        expect(Tools::getSsMethod('default'))->toBe($expected)
            ->and(Tools::getSsMethod('randomString'))->toBe($expected)
            ->and(Tools::getSsMethod())->toBe($expected);
    });
});

describe('Tools validation methods', function () {
    test('isEmail validates email addresses', function () {
        expect(Tools::isEmail('test@example.com'))->toBeTrue()
            ->and(Tools::isEmail('test@example'))->toBeFalse();
    });

    test('isIPv4 validates IPv4 addresses', function () {
        expect(Tools::isIPv4('192.168.0.1'))->toBeTrue()
            ->and(Tools::isIPv4('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->toBeFalse()
            ->and(Tools::isIPv4('UwU'))->toBeFalse();
    });

    test('isIPv6 validates IPv6 addresses', function () {
        expect(Tools::isIPv6('2001:0db8:85a3:0000:0000:8a2e:0370:7334'))->toBeTrue()
            ->and(Tools::isIPv6('192.168.0.1'))->toBeFalse()
            ->and(Tools::isIPv6('hmm'))->toBeFalse();
    });

    test('isInt validates integers', function () {
        expect(Tools::isInt(123))->toBeTrue()
            ->and(Tools::isInt('abc'))->toBeFalse();
    });

    test('isJson validates JSON objects', function () {
        expect(Tools::isJson('{}'))->toBeTrue()
            ->and(Tools::isJson('[]'))->toBeFalse()
            ->and(Tools::isJson('what the'))->toBeFalse();
    });

    test('toOptionalDateTime formats positive timestamps and falls back when zero', function () {
        expect(Tools::toOptionalDateTime(0))->toBe('-')
            ->and(Tools::toOptionalDateTime(0, '—'))->toBe('—')
            ->and(Tools::toOptionalDateTime(1700000000))->toBe(Tools::toDateTime(1700000000));
    });
});

describe('Tools::ipLimitKey', function () {
    test('keeps distinct public IPv4 and IPv6 addresses separate without subnet aggregation', function () {
        expect(Tools::ipLimitKey('223.104.79.66'))->toBe('223.104.79.66')
            ->and(Tools::ipLimitKey('223.104.79.87'))->toBe('223.104.79.87')
            ->and(Tools::ipLimitKey('240e:3a8:4caf:6de0::1'))->toBe('240e:3a8:4caf:6de0::1')
            ->and(Tools::ipLimitKey('240e:3a8:4caf:6de0:0000:0000:0000:0002'))->toBe('240e:3a8:4caf:6de0::2');
    });

    test('unwraps the IPv4-mapped form stored in online_log', function () {
        expect(Tools::ipLimitKey('::ffff:223.104.79.66'))->toBe('223.104.79.66');
    });

    test('drops non-globally-routable addresses such as loopback, private, and CGNAT', function () {
        foreach (['127.0.0.1', '::ffff:127.0.0.1', '10.1.0.5', '172.26.6.228', '192.168.1.1', '100.64.1.1', '::1', 'fe80::1', 'fd00::1'] as $ip) {
            expect(Tools::ipLimitKey($ip))->toBeNull();
        }
    });

    test('drops exact ignored relay exit IPs while keeping neighbors', function () {
        $ignored = ['103.169.217.55', '2a0b:9900::1'];

        expect(Tools::ipLimitKey('103.169.217.55', $ignored))->toBeNull()
            ->and(Tools::ipLimitKey('::ffff:103.169.217.55', $ignored))->toBeNull()
            ->and(Tools::ipLimitKey('2a0b:9900:0:0::1', $ignored))->toBeNull()
            ->and(Tools::ipLimitKey('103.169.217.56', $ignored))->toBe('103.169.217.56');
    });

    test('drops invalid IP strings', function () {
        expect(Tools::ipLimitKey(''))->toBeNull()
            ->and(Tools::ipLimitKey('UwU'))->toBeNull();
    });
});

describe('Tools::decodeCustomConfig', function () {
    test('decodes valid JSON objects into associative arrays', function () {
        expect(Tools::decodeCustomConfig('{"offset_port_user":443,"host":"example.com"}'))
            ->toBe(['offset_port_user' => 443, 'host' => 'example.com'])
            ->and(Tools::decodeCustomConfig(['already' => 'array']))
            ->toBe(['already' => 'array']);
    });

    test('degrades top-level JSON scalars, null, empty, and malformed JSON to empty array', function (mixed $raw) {
        expect(Tools::decodeCustomConfig($raw))->toBe([]);
    })->with([
        'integer scalar' => ['123'],
        'string scalar' => ['"hello"'],
        'boolean scalar' => ['true'],
        'json null' => ['null'],
        'empty string' => [''],
        'malformed json' => ['{"broken":'],
        'php null' => [null],
    ]);
});
