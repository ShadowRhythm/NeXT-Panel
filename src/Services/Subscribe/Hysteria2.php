<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function filter_var;
use function http_build_query;
use function is_numeric;
use function rawurlencode;
use function trim;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const PHP_EOL;

final class Hysteria2 extends Base
{
    public static function buildUri($user, $node_raw, array $node_custom_config): string
    {
        $hy2_port = (int) ($node_custom_config['offset_port_user'] ??
            ($node_custom_config['offset_port_node'] ?? 443));
        $host = $node_custom_config['host'] ?? ($node_custom_config['sni'] ?? '');
        $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
        $obfs_type = $node_custom_config['obfs'] ?? ($node_custom_config['obfs_type'] ?? '');
        $obfs_password = $node_custom_config['obfs_password'] ?? ($node_custom_config['obfs-password'] ?? '');
        $ports = (string) ($node_custom_config['ports'] ?? ($node_custom_config['server_ports'] ?? ''));
        $pin_sha256 = $node_custom_config['pin_sha256'] ?? ($node_custom_config['pinSHA256'] ?? '');

        $raw_server = trim((string) $node_raw->server);
        $unbracketed = trim($raw_server, '[]');

        if (filter_var($unbracketed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $server_host = '[' . $unbracketed . ']';
        } else {
            $server_host = $raw_server;
        }

        $params = [];
        if ($host !== '') {
            $params['sni'] = $host;
        }
        if ($allow_insecure) {
            $params['insecure'] = '1';
        }
        if ($obfs_type !== '' && $obfs_password !== '') {
            $params['obfs'] = $obfs_type;
            $params['obfs-password'] = $obfs_password;
        }
        if ($pin_sha256 !== '') {
            $params['pinSHA256'] = $pin_sha256;
        }

        if ($ports !== '') {
            if (is_numeric($ports)) {
                $port_part = (string) $ports;
            } else {
                $port_part = (string) $hy2_port;
                $params['mport'] = $ports;
                $params['ports'] = $ports;
            }
        } else {
            $port_part = (string) $hy2_port;
        }

        $query = $params !== [] ? '?' . http_build_query($params) : '';

        return 'hysteria2://' . rawurlencode((string) $user->passwd) . '@' . $server_host . ':' . $port_part . '/' . $query . '#' . rawurlencode((string) $node_raw->name) . PHP_EOL;
    }

    public function getContent($user): string
    {
        $links = '';
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = Tools::decodeCustomConfig($node_raw->custom_config);

            if ((int) $node_raw->sort === 4) {
                $links .= self::buildUri($user, $node_raw, $node_custom_config);
            }
        }

        return $links;
    }
}
