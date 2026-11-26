<?php

declare(strict_types=1);

namespace App\Services\Config;

use App\Services\I18n;

final class ClientConfig
{
    private const CLIENT_DESC_KEYS = [
        'Clash Verge Rev' => 'user.client_desc_clash_verge_rev',
        'Clash Nyanpasu' => 'user.client_desc_clash_nyanpasu',
        'FlClash' => 'user.client_desc_flclash',
        'Hiddify' => 'user.client_desc_hiddify',
        'SFM' => 'user.client_desc_sfm',
        'SFA' => 'user.client_desc_sfa',
        'sing-box MT' => 'user.client_desc_sing_box_mt',
        'CMFA' => 'user.client_desc_cmfa',
        'ClashMi' => 'user.client_desc_clashmi',
        'V2RayNG' => 'user.client_desc_v2rayng',
        'V2RayN' => 'user.client_desc_v2rayn',
        'Shadowrocket' => 'user.client_desc_shadowrocket',
        'Quantumult X' => 'user.client_desc_quantumult_x',
    ];

    private static ?array $config = null;

    public static function getClients(string $sub, string $name, bool $r2): array
    {
        if (self::$config === null) {
            $file = BASE_PATH . '/config/client_display.json';
            if (! is_readable($file)) {
                throw new \RuntimeException("Client config file not found: {$file}");
            }

            $content = file_get_contents($file);
            if ($content === false) {
                throw new \RuntimeException("Failed to read client config file: {$file}");
            }

            try {
                self::$config = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException('Invalid JSON in client config file: ' . $e->getMessage());
            }
        }

        $result = [];
        foreach (self::$config['clients'] as $client) {
            foreach ($client['platforms'] as $platform => $data) {
                $subUrlForClient = $sub . ($client['format'] !== '' ? '/' . $client['format'] : '');
                $defaultDescription = $data['desc'] ?? $client['description'];
                $descKey = self::CLIENT_DESC_KEYS[$client['name']] ?? null;
                $translated = ! isset($data['desc']) && $descKey !== null ? I18n::trans($descKey) : $defaultDescription;
                $description = $translated !== $descKey ? $translated : $defaultDescription;
                $result[$platform][] = [
                    'name' => $client['name'],
                    'description' => $description,
                    'format' => $client['format'],
                    'importUrl' => str_replace(
                        ['{sub}', '{name}', '{sub_base64}'],
                        [$sub, rawurlencode($name), base64_encode($subUrlForClient)],
                        $data['importUrl'] ?? $client['importUrl'] ?? ''
                    ),
                    'downloadUrl' => $data['storeUrl'] ??
                        (isset($data['ext']) ? ($r2 ? '/user' : '') . '/clients/' . ($data['file'] ?? str_replace(' ', '.', $client['name'])) . ".{$data['ext']}" : ($data['url'] ?? '')),
                    'isAppStore' => isset($data['storeUrl']),
                ];
            }
        }

        return ['clients' => $result, 'icons' => self::$config['icons']];
    }
}
