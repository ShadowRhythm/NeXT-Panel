<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ClientDetector;
use PHPUnit\Framework\TestCase;

final class ClientDetectorTest extends TestCase
{
    public function testParsesProxyClientsAndOperatingSystems(): void
    {
        $shadowrocket = ClientDetector::parseProxyClient('Shadowrocket/2100 CFNetwork/1496.0.7 Darwin/23.5.0', 'ss');
        self::assertSame('Shadowrocket', $shadowrocket['client']);
        self::assertSame('2100', $shadowrocket['version']);
        self::assertSame('iOS', $shadowrocket['os']);
        self::assertSame('mobile', $shadowrocket['device_type']);

        $verge = ClientDetector::parseProxyClient('clash-verge/v2.0.3 (Windows NT 10.0; Win64)', 'clash');
        self::assertSame('Clash Verge', $verge['client']);
        self::assertSame('v2.0.3', $verge['version']);
        self::assertSame('Windows', $verge['os']);
        self::assertSame('desktop', $verge['device_type']);

        $flclash = ClientDetector::parseProxyClient('FlClash/v0.8.70 darwin', 'clash');
        self::assertSame('FlClash', $flclash['client']);
        self::assertSame('v0.8.70', $flclash['version']);
        self::assertSame('macOS', $flclash['os']);
        self::assertSame('desktop', $flclash['device_type']);

        $v2rayng = ClientDetector::parseProxyClient('v2rayNG/1.9.10', 'v2ray');
        self::assertSame('v2rayNG', $v2rayng['client']);
        self::assertSame('Android', $v2rayng['os']);
        self::assertSame('mobile', $v2rayng['device_type']);
    }

    public function testParsesWebUserAgents(): void
    {
        $chromeMac = ClientDetector::parseWebAgent(
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
        );
        self::assertSame('Chrome', $chromeMac['browser']);
        self::assertSame('macOS', $chromeMac['os']);
        self::assertSame('macOS · Chrome', $chromeMac['device_name']);
        self::assertSame('desktop', $chromeMac['device_type']);

        $safariIos = ClientDetector::parseWebAgent(
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'
        );
        self::assertSame('Safari', $safariIos['browser']);
        self::assertSame('iOS', $safariIos['os']);
        self::assertSame('iOS · Safari', $safariIos['device_name']);
        self::assertSame('mobile', $safariIos['device_type']);
    }
}
