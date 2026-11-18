<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Services\Subscribe;
use App\Utils\Tools;
use function array_filter;
use function array_merge;
use function count;
use function is_array;
use function json_decode;
use function json_encode;

final class SingBox extends Base
{
    public function getContent($user): string
    {
        $nodes = [];
        $singbox_config = $_ENV['SingBox_Config'];
        $singbox_group_indexes = $_ENV['SingBox_Group_Indexes'] ?? [0, 1];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = json_decode($node_raw->custom_config, true);

            switch ((int) $node_raw->sort) {
                case 0:
                    $node = [
                        'type' => 'shadowsocks',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $user->port,
                        'method' => $user->method,
                        'password' => $user->passwd,
                    ];

                    break;
                case 1:
                    $ss_2022_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $method = $node_custom_config['method'] ?? '2022-blake3-aes-128-gcm';
                    $user_pk = Tools::genSs2022UserPk($user->passwd, $method);
                    $uot = $node_custom_config['uot'] ?? false;

                    if (! $user_pk) {
                        $node = [];
                        break;
                    }

                    $server_key = $node_custom_config['server_key'] ?? '';

                    $node = [
                        'type' => 'shadowsocks',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $ss_2022_port,
                        'method' => $method,
                        'password' => $server_key === '' ? $user_pk : $server_key . ':' .$user_pk,
                        'udp_over_tcp' => (bool) $uot,
                    ];

                    break;
                case 2:
                    $tuic_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? ($node_custom_config['sni'] ?? '');
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $congestion_control = $node_custom_config['congestion_control'] ?? 'bbr';

                    $node = [
                        'type' => 'tuic',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $tuic_port,
                        'uuid' => $user->uuid,
                        'password' => $user->passwd,
                        'congestion_control' => $congestion_control,
                        'zero_rtt_handshake' => true,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ],
                    ];

                    $node['tls'] = array_filter($node['tls']);

                    break;
                case 4:
                    $hy2_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? ($node_custom_config['sni'] ?? '');
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $up_mbps = (int) ($node_custom_config['up_mbps'] ?? ($node_custom_config['up'] ?? 0));
                    $down_mbps = (int) ($node_custom_config['down_mbps'] ?? ($node_custom_config['down'] ?? 0));
                    $obfs_type = $node_custom_config['obfs'] ?? ($node_custom_config['obfs_type'] ?? '');
                    $obfs_password = $node_custom_config['obfs_password'] ?? ($node_custom_config['obfs-password'] ?? '');
                    $server_ports = $node_custom_config['server_ports'] ?? ($node_custom_config['ports'] ?? '');

                    $node = [
                        'type' => 'hysteria2',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $hy2_port,
                        'password' => $user->passwd,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ],
                    ];

                    if ($server_ports !== '') {
                        $node['server_ports'] = (string) $server_ports;
                    }
                    if ($up_mbps > 0) {
                        $node['up_mbps'] = $up_mbps;
                    }
                    if ($down_mbps > 0) {
                        $node['down_mbps'] = $down_mbps;
                    }
                    if ($obfs_type !== '' && $obfs_password !== '') {
                        $node['obfs'] = [
                            'type' => $obfs_type,
                            'password' => $obfs_password,
                        ];
                    }

                    $node['tls'] = array_filter($node['tls']);

                    break;
                case 5:
                    $anytls_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $host = $node_custom_config['host'] ?? ($node_custom_config['sni'] ?? '');
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;

                    $node = [
                        'type' => 'anytls',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $anytls_port,
                        'password' => $user->passwd,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ],
                    ];

                    $node['tls'] = array_filter($node['tls']);

                    break;
                case 11:
                    $v2_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $security = $node_custom_config['security'] ?? 'none';
                    $network = $node_custom_config['network'] ?? '';
                    $transport = $network === 'tcp' ? '' : $network;
                    if ($transport === 'httpupgrade') {
                        $transport = 'ws';
                    }

                    $ws_opts = $node_custom_config['ws-opts'] ?? ($node_custom_config['ws_opts'] ?? []);
                    $ws_headers = is_array($ws_opts) ? ($ws_opts['headers'] ?? []) : [];

                    $host = $node_custom_config['header']['request']['headers']['Host'][0] ??
                        ($ws_headers['Host'] ?? ($ws_headers['host'] ?? ($node_custom_config['host'] ?? '')));
                    $path = $node_custom_config['header']['request']['path'][0] ??
                        ($ws_opts['path'] ?? ($node_custom_config['path'] ?? ''));

                    $headers = $node_custom_config['header']['request']['headers'] ?? $ws_headers;
                    if (! is_array($headers)) {
                        $headers = [];
                    }
                    if ($host !== '' && ! isset($headers['Host']) && ! isset($headers['host'])) {
                        $headers['Host'] = $host;
                    }
                    $clean_headers = [];
                    foreach ($headers as $hk => $hv) {
                        $clean_headers[(string) $hk] = is_array($hv) ? (string) ($hv[0] ?? '') : (string) $hv;
                    }
                    $headers = $clean_headers;

                    $service_name = $node_custom_config['servicename'] ??
                        ($node_custom_config['grpc-opts']['grpc_service_name'] ??
                        ($node_custom_config['grpc_opts']['grpc_service_name'] ?? ''));
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;
                    $tls_enabled = $security === 'tls' ||
                        (isset($node_custom_config['tls']) && (bool) $node_custom_config['tls']) ||
                        $host !== '';
                    $utls = $node_custom_config['utls'] ?? false;
                    $method = $node_custom_config['method'] ?? '';
                    $max_early_data = $node_custom_config['max_early_data'] ??
                        ($ws_opts['max_early_data'] ?? '');
                    $early_data_header_name = $node_custom_config['early_data_header_name'] ??
                        ($ws_opts['early_data_header_name'] ?? '');

                    $node = [
                        'type' => 'vmess',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $v2_port,
                        'uuid' => $user->uuid,
                        'security' => 'auto',
                        'alter_id' => 0,
                        'packet_encoding' => 'xudp',
                        'global_padding' => true,
                        'authenticated_length' => true,
                    ];

                    if ($tls_enabled) {
                        $tls = [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ];
                        if ($utls) {
                            $tls['utls'] = [
                                'enabled' => true,
                                'fingerprint' => 'chrome',
                            ];
                        }
                        $node['tls'] = array_filter($tls);
                    }

                    if ($transport !== '') {
                        $transport_config = [
                            'type' => $transport,
                            'path' => $path,
                            'method' => $method,
                            'headers' => $headers,
                            'service_name' => $service_name,
                            'max_early_data' => (int) $max_early_data,
                            'early_data_header_name' => $early_data_header_name,
                        ];
                        $node['transport'] = array_filter($transport_config);
                    }

                    break;
                case 14:
                    $trojan_port = $node_custom_config['offset_port_user'] ??
                        ($node_custom_config['offset_port_node'] ?? 443);
                    $network = $node_custom_config['network'] ?? 'tcp';
                    $transport = $network === 'tcp' ? '' : $network;
                    if ($transport === 'httpupgrade') {
                        $transport = 'ws';
                    }

                    $ws_opts = $node_custom_config['ws-opts'] ?? ($node_custom_config['ws_opts'] ?? []);
                    $ws_headers = is_array($ws_opts) ? ($ws_opts['headers'] ?? []) : [];

                    $host = $node_custom_config['header']['request']['headers']['Host'][0] ??
                        ($ws_headers['Host'] ??
                        ($ws_headers['host'] ??
                        ($node_custom_config['host'] ??
                        ($node_custom_config['sni'] ?? ''))));
                    $path = $node_custom_config['header']['request']['path'][0] ??
                        ($ws_opts['path'] ?? ($node_custom_config['path'] ?? ''));

                    $headers = $node_custom_config['header']['request']['headers'] ?? $ws_headers;
                    if (! is_array($headers)) {
                        $headers = [];
                    }
                    if ($host !== '' && ! isset($headers['Host']) && ! isset($headers['host'])) {
                        $headers['Host'] = $host;
                    }
                    $clean_headers = [];
                    foreach ($headers as $hk => $hv) {
                        $clean_headers[(string) $hk] = is_array($hv) ? (string) ($hv[0] ?? '') : (string) $hv;
                    }
                    $headers = $clean_headers;

                    $service_name = $node_custom_config['servicename'] ??
                        ($node_custom_config['grpc-opts']['grpc_service_name'] ??
                        ($node_custom_config['grpc_opts']['grpc_service_name'] ?? ''));
                    $allow_insecure = $node_custom_config['allow_insecure'] ?? false;

                    $node = [
                        'type' => 'trojan',
                        'tag' => $node_raw->name,
                        'server' => trim((string) $node_raw->server, '[]'),
                        'server_port' => (int) $trojan_port,
                        'password' => $user->uuid,
                        'tls' => [
                            'enabled' => true,
                            'server_name' => $host,
                            'insecure' => (bool) $allow_insecure,
                        ],
                    ];

                    if ($transport !== '') {
                        $transport_config = [
                            'type' => $transport,
                            'path' => $path,
                            'headers' => $headers,
                            'service_name' => $service_name,
                        ];
                        $node['transport'] = array_filter($transport_config);
                    }

                    $node['tls'] = array_filter($node['tls']);

                    break;
                default:
                    $node = [];
                    break;
            }

            if ($node === []) {
                continue;
            }

            $nodes[] = $node;

            foreach ($singbox_group_indexes as $index) {
                if (isset($singbox_config['outbounds'][$index])) {
                    $singbox_config['outbounds'][$index]['outbounds'][] = $node_raw->name;
                }
            }
        }

        if ($nodes === []) {
            foreach ($singbox_config['outbounds'] as $key => $outbound) {
                if (($outbound['type'] ?? '') === 'urltest') {
                    unset($singbox_config['outbounds'][$key]);
                } elseif (($outbound['type'] ?? '') === 'selector' && empty($outbound['outbounds'])) {
                    $singbox_config['outbounds'][$key]['outbounds'] = ['🎯 Direct'];
                }
            }
            $singbox_config['outbounds'] = array_values($singbox_config['outbounds']);
        } else {
            $singbox_config['outbounds'] = array_merge($singbox_config['outbounds'], $nodes);
        }

        $singbox_config['experimental']['cache_file']['cache_id'] = $_ENV['appName'];

        return json_encode($singbox_config);
    }
}
