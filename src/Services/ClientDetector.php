<?php

declare(strict_types=1);

namespace App\Services;

use function preg_match;
use function rawurldecode;
use function str_contains;
use function strtolower;
use function strtoupper;
use function trim;

final class ClientDetector
{
    /**
     * Parse proxy subscription User-Agent into structured client and OS metadata.
     *
     * @return array{client: string, version: string, os: string, device_type: string, icon: string}
     */
    public static function parseProxyClient(string $userAgent, string $subType = ''): array
    {
        $decoded = trim(rawurldecode($userAgent));
        $lower = strtolower($decoded);

        $client = 'Unknown Client';
        $version = '';

        $patterns = [
            '/FlClash\/([^\s;\/]+)/i' => 'FlClash',
            '/clash-verge(?:-rev)?\/([^\s;\/]+)/i' => 'Clash Verge',
            '/clash-nyanpasu\/([^\s;\/]+)/i' => 'Clash Nyanpasu',
            '/ClashMi\/([^\s;\/]+)/i' => 'ClashMi',
            '/ClashMetaForAndroid\/([^\s;\/]+)/i' => 'Clash Meta for Android',
            '/ClashForAndroid\/([^\s;\/]+)/i' => 'Clash for Android',
            '/ClashX(?:\s+Pro)?\/([^\s;\/]+)/i' => 'ClashX',
            '/Shadowrocket\/([^\s;\/]+)/i' => 'Shadowrocket',
            '/Quantumult(?:\s|%20)?X\/([^\s;\/]+)/i' => 'Quantumult X',
            '/Stash\/([^\s;\/]+)/i' => 'Stash',
            '/Surge(?:\s+iOS|\s+Mac)?\/([^\s;\/]+)/i' => 'Surge',
            '/Loon\/([^\s;\/]+)/i' => 'Loon',
            '/Hiddify(?:Next)?\/([^\s;\/]+)/i' => 'Hiddify',
            '/v2rayNG\/([^\s;\/]+)/i' => 'v2rayNG',
            '/v2rayN\/([^\s;\/]+)/i' => 'v2rayN',
            '/Karing\/([^\s;\/]+)/i' => 'Karing',
            '/NekoBox\/([^\s;\/]+)/i' => 'NekoBox',
            '/NekoRay\/([^\s;\/]+)/i' => 'NekoRay',
            '/Streisand\/([^\s;\/]+)/i' => 'Streisand',
            '/V2Box\/([^\s;\/]+)/i' => 'V2Box',
            '/FoXray\/([^\s;\/]+)/i' => 'FoXray',
            '/sing-box\/([^\s;\/]+)/i' => 'sing-box',
            '/SFM\/([^\s;\/]+)/i' => 'sing-box (macOS)',
            '/SFA\/([^\s;\/]+)/i' => 'sing-box (Android)',
            '/SFI\/([^\s;\/]+)/i' => 'sing-box (iOS)',
            '/mihomo\/([^\s;\/]+)/i' => 'Mihomo',
            '/Clash\.Meta\/([^\s;\/]+)/i' => 'Clash.Meta',
            '/Clash\/([^\s;\/]+)/i' => 'Clash',
        ];

        foreach ($patterns as $regex => $label) {
            if (preg_match($regex, $decoded, $matches)) {
                $client = $label;
                $version = $matches[1] ?? '';
                break;
            }
        }

        if ($client === 'Unknown Client') {
            if (str_contains($lower, 'flclash')) {
                $client = 'FlClash';
            } elseif (str_contains($lower, 'clash-verge')) {
                $client = 'Clash Verge';
            } elseif (str_contains($lower, 'shadowrocket')) {
                $client = 'Shadowrocket';
            } elseif (str_contains($lower, 'quantumult')) {
                $client = 'Quantumult X';
            } elseif (str_contains($lower, 'sing-box') || str_contains($lower, 'singbox')) {
                $client = 'sing-box';
            } elseif (str_contains($lower, 'hiddify')) {
                $client = 'Hiddify';
            } elseif (str_contains($lower, 'v2rayng')) {
                $client = 'v2rayNG';
            } elseif (str_contains($lower, 'v2rayn')) {
                $client = 'v2rayN';
            } elseif (str_contains($lower, 'mihomo') || str_contains($lower, 'clash')) {
                $client = 'Clash';
            } elseif ($subType !== '') {
                $client = strtoupper($subType);
            }
        }

        $os = self::detectOs($decoded, $client);
        $deviceType = self::classifyDeviceType($os);

        return [
            'client' => $client,
            'version' => $version,
            'os' => $os,
            'device_type' => $deviceType,
            'icon' => self::deviceIcon($deviceType),
        ];
    }

    /**
     * Parse web browser User-Agent into human-readable browser/OS session summary.
     *
     * @return array{browser: string, os: string, device_type: string, device_name: string, icon: string}
     */
    public static function parseWebAgent(string $userAgent): array
    {
        $ua = trim($userAgent);
        $os = self::detectOs($ua);
        $deviceType = self::classifyDeviceType($os);

        $browser = 'Browser';
        if (str_contains($ua, 'Edg/') || str_contains($ua, 'Edge/')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera')) {
            $browser = 'Opera';
        } elseif (str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'Safari/')) {
            $browser = 'Safari';
        } elseif (str_contains($ua, 'curl/')) {
            $browser = 'curl';
        }

        $deviceName = $os !== 'Unknown' ? ($os . ' · ' . $browser) : $browser;

        return [
            'browser' => $browser,
            'os' => $os,
            'device_type' => $deviceType,
            'device_name' => $deviceName,
            'icon' => self::deviceIcon($deviceType),
        ];
    }

    private static function detectOs(string $ua, string $client = ''): string
    {
        $lower = strtolower($ua);
        $clientLower = strtolower($client);

        if (str_contains($lower, 'ipad') || str_contains($lower, 'ipados')) {
            return 'iPadOS';
        }
        if (str_contains($lower, 'iphone') || str_contains($lower, 'ios') || str_contains($lower, 'cfnetwork') || str_contains($lower, 'darwin') && str_contains($lower, 'mobile')) {
            return 'iOS';
        }
        if (str_contains($lower, 'android') || $clientLower === 'v2rayng' || str_contains($clientLower, 'for android')) {
            return 'Android';
        }
        if (str_contains($lower, 'windows') || str_contains($lower, 'win64') || str_contains($lower, 'win32') || $clientLower === 'v2rayn') {
            return 'Windows';
        }
        if (str_contains($lower, 'macintosh') || str_contains($lower, 'mac os') || str_contains($lower, 'macos') || str_contains($lower, 'darwin') || $clientLower === 'clashx') {
            return 'macOS';
        }
        if (str_contains($lower, 'openwrt') || str_contains($lower, 'router')) {
            return 'Router';
        }
        if (str_contains($lower, 'linux')) {
            return 'Linux';
        }

        if ($clientLower === 'shadowrocket' || $clientLower === 'quantumult x' || $clientLower === 'loon' || $clientLower === 'streisand' || $clientLower === 'foxray' || $clientLower === 'sing-box (ios)') {
            return 'iOS';
        }
        if ($clientLower === 'sing-box (macos)') {
            return 'macOS';
        }
        if ($clientLower === 'sing-box (android)') {
            return 'Android';
        }

        return 'Unknown';
    }

    private static function classifyDeviceType(string $os): string
    {
        return match ($os) {
            'iOS', 'Android' => 'mobile',
            'iPadOS' => 'tablet',
            'Router' => 'router',
            'Windows', 'macOS', 'Linux' => 'desktop',
            default => 'unknown',
        };
    }

    private static function deviceIcon(string $deviceType): string
    {
        return match ($deviceType) {
            'mobile' => 'ti-device-mobile',
            'tablet' => 'ti-device-tablet',
            'router' => 'ti-router',
            'desktop' => 'ti-device-laptop',
            default => 'ti-devices',
        };
    }
}
