<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function http_build_query;
use function implode;
use function rawurlencode;
use const PHP_EOL;

final class AnyTLS extends Base
{
    /**
     * Build a standard `anytls://` share URI for a sort=5 node.
     *
     * @param array<string, mixed> $node_custom_config
     */
    public static function buildUri($user, $node_raw, array $node_custom_config): string
    {
        $anytls_port = (int) ($node_custom_config['offset_port_user'] ??
            ($node_custom_config['offset_port_node'] ?? 443));
        $host = (string) ($node_custom_config['host'] ?? ($node_custom_config['sni'] ?? ''));
        $allow_insecure = (bool) ($node_custom_config['allow_insecure'] ?? false);
        $fingerprint = (string) ($node_custom_config['client_fingerprint'] ??
            ($node_custom_config['client-fingerprint'] ?? ($node_custom_config['fp'] ?? '')));
        $alpn = self::parseAlpn($node_custom_config['alpn'] ?? null);

        $server_host = self::formatUriHost((string) $node_raw->server);

        $params = [];
        if ($host !== '') {
            $params['sni'] = $host;
        }
        if ($allow_insecure) {
            $params['insecure'] = '1';
        }
        if ($fingerprint !== '') {
            $params['fp'] = $fingerprint;
        }
        if ($alpn !== []) {
            $params['alpn'] = implode(',', $alpn);
        }

        $query = $params !== [] ? '?' . http_build_query($params) : '';

        return 'anytls://' . rawurlencode((string) $user->passwd) . '@' . $server_host . ':' . $anytls_port . '/' . $query . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
    }

    public function getContent($user): string
    {
        $links = '';
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = Tools::decodeCustomConfig($node_raw->custom_config);

            if ((int) $node_raw->sort === 5) {
                $links .= self::buildUri($user, $node_raw, $node_custom_config);
            }
        }

        return $links;
    }
}
