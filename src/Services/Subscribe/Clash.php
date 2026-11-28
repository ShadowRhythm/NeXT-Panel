<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Config;
use App\Services\Subscribe;
use App\Utils\Tools;
use function array_merge;
use function is_array;
use function yaml_emit;
use const YAML_UTF8_ENCODING;

final class Clash extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $clash_config = $_ENV['Clash_Config'];

        $tcpConcurrent = Config::obtain('tcp_concurrent');
        if ($tcpConcurrent !== '') {
            $clash_config['tcp-concurrent'] = (bool) $tcpConcurrent;
        }
        $clash_group_indexes = $_ENV['Clash_Group_Indexes'];
        $clash_group_config = $_ENV['Clash_Group_Config'];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = Tools::decodeCustomConfig($node_raw->custom_config);

            switch ((int) $node_raw->sort) {
                case 0:
                    $plugin = $node_custom_config['plugin'] ?? '';
                    $plugin_option = $node_custom_config['plugin_option'] ?? null;
                    // Clash 特定配置
                    $udp = $node_custom_config['udp'] ?? true;

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'ss',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'port' => (int) $user->port,
                        'password' => $user->passwd,
                        'cipher' => $user->method,
                        'udp' => (bool) $udp,
                        'plugin' => $plugin,
                        'plugin-opts' => $plugin_option,
                    ];

                    break;
                case 1:
                    $ss_2022_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $method = $node_custom_config['method'] ?? '2022-blake3-aes-128-gcm';
                    $user_pk = Tools::genSs2022UserPk($user->passwd, $method);

                    if (! $user_pk) {
                        $node = [];
                        break;
                    }

                    // Clash 特定配置
                    $udp = $node_custom_config['udp'] ?? true;
                    $server_key = $node_custom_config['server_key'] ?? '';
                    $uot = $node_custom_config['uot'] ?? false;

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'ss',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'port' => (int) $ss_2022_port,
                        'password' => $server_key === '' ? $user_pk : $server_key . ':' .$user_pk,
                        'cipher' => $method,
                        'udp' => (bool) $udp,
                        'udp_over_tcp' => (bool) $uot,
                    ];

                    break;
                case 2:
                    $tuic_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? '';
                    $congestion_control = $node_custom_config['congestion_control'] ?? 'bbr';
                    // Only Clash.Meta core has TUIC support
                    // Tuic V5 Only
                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'tuic',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'port' => (int) $tuic_port,
                        'password' => $user->passwd,
                        'uuid' => $user->uuid,
                        'sni' => $host,
                        'congestion-controller' => $congestion_control,
                        'reduce-rtt' => true,
                    ];

                    break;
                case 4:
                    $hy2_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? ($node_custom_config['sni'] ?? '');
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $up_mbps = $node_custom_config['up_mbps'] ?? ($node_custom_config['up'] ?? 0);
                    $down_mbps = $node_custom_config['down_mbps'] ?? ($node_custom_config['down'] ?? 0);
                    $obfs_type = $node_custom_config['obfs'] ?? ($node_custom_config['obfs_type'] ?? '');
                    $obfs_password = $node_custom_config['obfs_password'] ?? ($node_custom_config['obfs-password'] ?? '');
                    $ports = $node_custom_config['ports'] ?? ($node_custom_config['server_ports'] ?? '');

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'hysteria2',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'port' => (int) $hy2_port,
                        'password' => $user->passwd,
                        'sni' => $host,
                        'skip-cert-verify' => (bool) $allow_insecure,
                        'alpn' => ['h3'],
                    ];

                    if ($ports !== '') {
                        $node['ports'] = (string) $ports;
                    }
                    if ($up_mbps > 0) {
                        $node['up'] = is_numeric($up_mbps) ? $up_mbps . ' Mbps' : (string) $up_mbps;
                    }
                    if ($down_mbps > 0) {
                        $node['down'] = is_numeric($down_mbps) ? $down_mbps . ' Mbps' : (string) $down_mbps;
                    }
                    if ($obfs_type !== '' && $obfs_password !== '') {
                        $node['obfs'] = $obfs_type;
                        $node['obfs-password'] = $obfs_password;
                    }

                    break;
                case 5:
                    $anytls_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = (string) ($node_custom_config['host'] ?? ($node_custom_config['sni'] ?? ''));
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $udp = $node_custom_config['udp'] ?? true;
                    $fingerprint = (string) ($node_custom_config['client_fingerprint'] ??
                        ($node_custom_config['client-fingerprint'] ?? ($node_custom_config['fp'] ?? '')));
                    $alpn = self::parseAlpn($node_custom_config['alpn'] ?? null);

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'anytls',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'port' => (int) $anytls_port,
                        'password' => $user->passwd,
                        'skip-cert-verify' => (bool) $allow_insecure,
                        'udp' => (bool) $udp,
                    ];

                    if ($host !== '') {
                        $node['sni'] = $host;
                    }
                    if ($fingerprint !== '') {
                        $node['client-fingerprint'] = $fingerprint;
                    }
                    if ($alpn !== []) {
                        $node['alpn'] = $alpn;
                    }

                    break;
                case 11:
                    $v2_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $security = $node_custom_config['security'] ?? 'none';
                    $encryption = $node_custom_config['encryption'] ?? 'auto';
                    $network = $node_custom_config['network'] ?? '';
                    $host = $node_custom_config['header']['request']['headers']['Host'][0] ??
                        $node_custom_config['host'] ?? '';
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $tls = $security === 'tls';
                    // Clash 特定配置
                    $udp = $node_custom_config['udp'] ?? true;
                    $ws_opts = $node_custom_config['ws-opts'] ?? $node_custom_config['ws_opts'] ?? null;
                    $h2_opts = $node_custom_config['h2-opts'] ?? $node_custom_config['h2_opts'] ?? null;
                    $http_opts = $node_custom_config['http-opts'] ?? $node_custom_config['http_opts'] ?? null;
                    $grpc_opts = $node_custom_config['grpc-opts'] ?? $node_custom_config['grpc_opts'] ?? null;
                    if ($network === 'ws' || $network === 'httpupgrade') {
                        $ws_opts = is_array($ws_opts) ? $ws_opts : [];
                        $ws_opts += [
                            'path' => $node_custom_config['header']['request']['path'][0] ?? $node_custom_config['path'] ?? '/',
                        ];
                        if ($host !== '') {
                            $ws_opts['headers'] = (is_array($ws_opts['headers'] ?? null) ? $ws_opts['headers'] : []) + ['Host' => $host];
                        }
                    }
                    // HTTPUpgrade 在 Clash.Meta 内核中属于 ws 类型
                    if ($network === 'httpupgrade') {
                        $network = 'ws';
                        $ws_opts += ['v2ray-http-upgrade' => true];
                    }
                    if ($network === 'grpc') {
                        $grpc_opts = (is_array($grpc_opts) ? $grpc_opts : []) + [
                            'grpc-service-name' => $node_custom_config['servicename'] ?? '',
                        ];
                    }

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'vmess',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'port' => (int) $v2_port,
                        'uuid' => $user->uuid,
                        'alterId' => 0,
                        'cipher' => $encryption,
                        'udp' => (bool) $udp,
                        'tls' => $tls,
                        'skip-cert-verify' => (bool) $allow_insecure,
                        'servername' => $host,
                        'network' => $network,
                        'ws-opts' => $ws_opts,
                        'h2-opts' => $h2_opts,
                        'http-opts' => $http_opts,
                        'grpc-opts' => $grpc_opts,
                    ];

                    break;
                case 14:
                    $trojan_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $network = $node_custom_config['header']['type'] ?? $node_custom_config['network'] ?? 'tcp';
                    $host = $node_custom_config['host'] ?? '';
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    // Clash 特定配置
                    $udp = $node_custom_config['udp'] ?? true;
                    $ws_opts = $node_custom_config['ws-opts'] ?? $node_custom_config['ws_opts'] ?? null;
                    $grpc_opts = $node_custom_config['grpc-opts'] ?? $node_custom_config['grpc_opts'] ?? null;
                    // HTTPUpgrade 在 Clash.Meta 内核中属于 ws 类型
                    if ($network === 'httpupgrade') {
                        $network = 'ws';
                    }

                    $node = [
                        'name' => $node_raw->name,
                        'type' => 'trojan',
                        'server' => trim((string) $node_raw->server, '[]'),
                        'sni' => $host,
                        'port' => (int) $trojan_port,
                        'password' => $user->uuid,
                        'network' => $network,
                        'udp' => (bool) $udp,
                        'skip-cert-verify' => (bool) $allow_insecure,
                        'ws-opts' => $ws_opts,
                        'grpc-opts' => $grpc_opts,
                    ];

                    break;
                default:
                    $node = [];
                    break;
            }

            if ($node === []) {
                continue;
            }

            $nodes[] = $node;

            foreach ($clash_group_indexes as $index) {
                if (isset($clash_group_config['proxy-groups'][$index])) {
                    $clash_group_config['proxy-groups'][$index]['proxies'][] = $node_raw->name;
                }
            }
        }

        $clash_nodes = [
            'proxies' => $nodes,
        ];

        foreach ($clash_group_indexes as $index) {
            if (isset($clash_group_config['proxy-groups'][$index]) && empty($clash_group_config['proxy-groups'][$index]['proxies'])) {
                $clash_group_config['proxy-groups'][$index]['proxies'] = ['DIRECT'];
            }
        }

        return yaml_emit(
            array_merge($clash_config, $clash_nodes, $clash_group_config),
            YAML_UTF8_ENCODING
        );
    }
}
