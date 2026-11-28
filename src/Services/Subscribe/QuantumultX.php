<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use App\Models\Link;
use App\Services\Subscribe;
use App\Utils\Tools;
use function base64_encode;
use function implode;
use function is_array;
use const PHP_EOL;

/**
 * Quantumult X 原生订阅格式生成器
 *
 * 支持 Shadowsocks、VMess（TCP/WS+TLS）、Trojan（TLS/WS+TLS）、AnyTLS 节点
 * 输出每行一条 QX 原生格式的纯文本，内容经过 Base64 编码
 *
 * 格式参考：
 *   shadowsocks=server:port, method=..., password=..., [obfs=...], tag=name
 *   vmess=server:port, method=aes-128-gcm, password=uuid, [obfs=...], tls-verification=true, tag=name
 *   trojan=server:port, password=uuid, over-tls=true, tls-host=..., tag=name
 *   anytls=server:port, password=..., over-tls=true, tls-host=..., tls-verification=true, udp-relay=true, tag=name
 */
final class QuantumultX extends Base
{
    public function getContent($user): string
    {
        if (isset($_GET['type']) && ($_GET['type'] === 'conf' || $_GET['type'] === 'profile') ||
            isset($_GET['profile']) && $_GET['profile'] === '1'
        ) {
            return $this->getProfileContent($user);
        }

        $lines = [];
        $nodes_raw = Subscribe::getUserNodes($user);

        foreach ($nodes_raw as $node_raw) {
            $node_custom_config = Tools::decodeCustomConfig($node_raw->custom_config);
            $name = $node_raw->name;
            $server = $node_raw->server;

            $line = match ((int) $node_raw->sort) {
                // Shadowsocks (Legacy)
                0 => $this->buildShadowsocks(
                    $server,
                    (int) $user->port,
                    $user->method,
                    $user->passwd,
                    $name,
                    $node_custom_config
                ),
                // Shadowsocks 2022
                1 => $this->buildShadowsocks2022(
                    $server,
                    $user->passwd,
                    $name,
                    $node_custom_config
                ),
                // AnyTLS
                5 => $this->buildAnyTLS(
                    $server,
                    $user->passwd,
                    $name,
                    $node_custom_config
                ),
                // VMess
                11 => $this->buildVMess(
                    $server,
                    $user->uuid,
                    $name,
                    $node_custom_config
                ),
                // Trojan
                14 => $this->buildTrojan(
                    $server,
                    $user->uuid,
                    $name,
                    $node_custom_config
                ),
                default => '',
            };

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return base64_encode(implode(PHP_EOL, $lines));
    }

    /**
     * 构建 Shadowsocks（Legacy）节点行
     */
    private function buildShadowsocks(
        string $server,
        int $port,
        string $method,
        string $password,
        string $name,
        array $config
    ): string {
        $plugin = $config['plugin'] ?? '';

        // QX 支持的混淆：tls / http / ws / wss
        // 普通 SS 暂不添加 obfs，可按需扩展
        $parts = [
            "shadowsocks={$server}:{$port}",
            "method={$method}",
            "password={$password}",
            'udp-relay=true',
            "tag={$name}",
        ];

        return implode(', ', $parts);
    }

    /**
     * 构建 Shadowsocks 2022 节点行（QX 支持有限，输出 ss2022 格式）
     */
    private function buildShadowsocks2022(
        string $server,
        string $passwd,
        string $name,
        array $config
    ): string {
        $port = $config['offset_port_user'] ?? ($config['offset_port_node'] ?? 443);
        $method = $config['method'] ?? '2022-blake3-aes-128-gcm';
        $server_key = $config['server_key'] ?? '';
        $user_pk = Tools::genSs2022UserPk($passwd, $method);

        if (! $user_pk) {
            return '';
        }

        $password = $server_key !== '' ? "{$server_key}:{$user_pk}" : $user_pk;

        $parts = [
            "shadowsocks={$server}:{$port}",
            "method={$method}",
            "password={$password}",
            'udp-relay=true',
            "tag={$name}",
        ];

        return implode(', ', $parts);
    }

    /**
     * 构建 VMess 节点行
     *
     * 传输层:
     *   - TCP (无 obfs): vmess=server:port, method=aes-128-gcm, password=uuid, tag=name
     *   - TCP + TLS:      追加 tls-verification=true, tls-host=sni
     *   - WS + TLS(WSS): obfs=wss, obfs-host=host, obfs-uri=/path
     *   - WS (无 TLS):   obfs=ws,  obfs-host=host, obfs-uri=/path
     */
    private function buildVMess(
        string $server,
        string $uuid,
        string $name,
        array $config
    ): string {
        $port = $config['offset_port_user'] ?? ($config['offset_port_node'] ?? 443);
        $security = $config['security'] ?? 'none';
        $network = $config['network'] ?? 'tcp';
        $allow_insecure = (bool) ($config['allow_insecure'] ?? false);

        // 从多种来源读取 host/sni 和 path
        $ws_opts = is_array($config['ws-opts'] ?? null) ? $config['ws-opts'] : (is_array($config['ws_opts'] ?? null) ? $config['ws_opts'] : []);
        $ws_headers = is_array($ws_opts['headers'] ?? null) ? $ws_opts['headers'] : [];

        $host = $config['header']['request']['headers']['Host'][0]
            ?? $ws_headers['Host']
            ?? $ws_headers['host']
            ?? $config['host']
            ?? '';

        $path = $config['header']['request']['path'][0]
            ?? $ws_opts['path']
            ?? $config['path']
            ?? '';

        $tls_enabled = $security === 'tls';

        // QX VMess method: 只支持 aes-128-gcm / chacha20-poly1305 / none
        $method = 'aes-128-gcm';

        $parts = [
            "vmess={$server}:{$port}",
            "method={$method}",
            "password={$uuid}",
        ];

        if ($network === 'ws') {
            // WebSocket 模式
            $obfs = $tls_enabled ? 'wss' : 'ws';
            $parts[] = "obfs={$obfs}";
            if ($host !== '') {
                $parts[] = "obfs-host={$host}";
            }
            if ($path !== '') {
                $parts[] = "obfs-uri={$path}";
            }
        } elseif ($tls_enabled) {
            // 纯 TCP + TLS
            if ($host !== '') {
                $parts[] = "tls-host={$host}";
            }
            $parts[] = 'over-tls=true';
        }

        $parts[] = 'tls-verification=' . ($allow_insecure ? 'false' : 'true');
        $parts[] = 'fast-open=false';
        $parts[] = 'udp-relay=false';
        $parts[] = "tag={$name}";

        return implode(', ', $parts);
    }

    /**
     * 构建 Trojan 节点行
     *
     * 传输层:
     *   - TCP + TLS:      over-tls=true, tls-host=sni
     *   - WS + TLS (WSS): obfs=wss, obfs-host=host, obfs-uri=/path
     */
    private function buildTrojan(
        string $server,
        string $password,
        string $name,
        array $config
    ): string {
        $port = $config['offset_port_user'] ?? ($config['offset_port_node'] ?? 443);
        $network = $config['network'] ?? 'tcp';
        $allow_insecure = (bool) ($config['allow_insecure'] ?? false);

        $ws_opts = is_array($config['ws-opts'] ?? null) ? $config['ws-opts'] : (is_array($config['ws_opts'] ?? null) ? $config['ws_opts'] : []);
        $ws_headers = is_array($ws_opts['headers'] ?? null) ? $ws_opts['headers'] : [];

        $host = $config['header']['request']['headers']['Host'][0]
            ?? $ws_headers['Host']
            ?? $ws_headers['host']
            ?? $config['host']
            ?? $config['sni']
            ?? '';

        $path = $config['header']['request']['path'][0]
            ?? $ws_opts['path']
            ?? $config['path']
            ?? '';

        $parts = [
            "trojan={$server}:{$port}",
            "password={$password}",
        ];

        if ($network === 'ws') {
            // Trojan over WebSocket + TLS (wss)
            // QX 文档说：使用 wss 时不要设置 over-tls 和 tls-host，改用 obfs=wss 和 obfs-host
            $parts[] = 'obfs=wss';
            if ($host !== '') {
                $parts[] = "obfs-host={$host}";
            }
            if ($path !== '') {
                $parts[] = "obfs-uri={$path}";
            }
        } else {
            // 标准 TCP + TLS
            $parts[] = 'over-tls=true';
            if ($host !== '') {
                $parts[] = "tls-host={$host}";
            }
        }

        $parts[] = 'tls-verification=' . ($allow_insecure ? 'false' : 'true');
        $parts[] = 'fast-open=false';
        $parts[] = 'udp-relay=false';
        $parts[] = "tag={$name}";

        return implode(', ', $parts);
    }

    /**
     * 构建 AnyTLS 节点行（Quantumult X 1.7.0+ 原生支持）
     *
     * @param array<string, mixed> $config
     */
    private function buildAnyTLS(
        string $server,
        string $password,
        string $name,
        array $config
    ): string {
        $port = (int) ($config['offset_port_user'] ?? ($config['offset_port_node'] ?? 443));
        $host = (string) ($config['host'] ?? ($config['sni'] ?? ''));
        $allow_insecure = (bool) ($config['allow_insecure'] ?? false);
        $udp = (bool) ($config['udp'] ?? true);
        $reality_pubkey = (string) ($config['reality-base64-pubkey'] ?? ($config['reality_pubkey'] ?? ''));
        $reality_shortid = (string) ($config['reality-hex-shortid'] ?? ($config['reality_shortid'] ?? ''));

        $server_host = self::formatUriHost($server);

        $parts = [
            "anytls={$server_host}:{$port}",
            "password={$password}",
            'over-tls=true',
        ];

        if ($host !== '') {
            $parts[] = "tls-host={$host}";
        }

        $parts[] = 'tls-verification=' . ($allow_insecure ? 'false' : 'true');

        if ($reality_pubkey !== '') {
            $parts[] = "reality-base64-pubkey={$reality_pubkey}";
            if ($reality_shortid !== '') {
                $parts[] = "reality-hex-shortid={$reality_shortid}";
            }
        }

        $parts[] = 'fast-open=false';
        $parts[] = 'udp-relay=' . ($udp ? 'true' : 'false');
        $parts[] = "tag={$name}";

        return implode(', ', $parts);
    }

    /**
     * 构建 Quantumult X 完整配置文件（包含 policy 策略组、分流规则集与节点订阅）
     */
    public function getProfileContent($user): string
    {
        $qx_config = $_ENV['QuantumultX_Config'] ?? [];
        $sections = [];

        // [general]
        $general = ['[general]'];
        if (isset($qx_config['general'])) {
            foreach ($qx_config['general'] as $k => $v) {
                $general[] = "{$k}={$v}";
            }
        }
        $sections[] = implode(PHP_EOL, $general);

        // [dns]
        $dns = ['[dns]'];
        if (isset($qx_config['dns']['servers'])) {
            foreach ($qx_config['dns']['servers'] as $server) {
                $dns[] = "server={$server}";
            }
        }
        $sections[] = implode(PHP_EOL, $dns);

        // [policy]
        $policy = ['[policy]'];
        if (isset($qx_config['policy'])) {
            foreach ($qx_config['policy'] as $p) {
                $policy[] = $p;
            }
        }
        $sections[] = implode(PHP_EOL, $policy);

        // [server_remote]
        $server_remote = ['[server_remote]'];
        $link = (new Link())->where('userid', $user->id)->first();
        if ($link === null) {
            $link = new Link();
            $link->userid = $user->id;
            $link->token = Tools::genSubToken();
            $link->save();
        }
        $sub_url = $_ENV['subUrl'] . '/sub/' . $link->token . '/qx';
        $server_remote[] = "{$sub_url}, tag=" . ($_ENV['appName'] ?? 'SSPanel') . ', update-interval=86400, opt-parser=false, enabled=true';
        $sections[] = implode(PHP_EOL, $server_remote);

        // [server_local]
        $server_local = ['[server_local]'];
        if (isset($qx_config['server_local'])) {
            foreach ($qx_config['server_local'] as $sl) {
                $server_local[] = $sl;
            }
        }
        $sections[] = implode(PHP_EOL, $server_local);

        // [filter_remote]
        $filter_remote = ['[filter_remote]'];
        if (isset($qx_config['filter_remote'])) {
            foreach ($qx_config['filter_remote'] as $fr) {
                $filter_remote[] = $fr;
            }
        }
        $sections[] = implode(PHP_EOL, $filter_remote);

        // [filter_local]
        $filter_local = ['[filter_local]'];
        if (isset($qx_config['filter_local'])) {
            foreach ($qx_config['filter_local'] as $fl) {
                $filter_local[] = $fl;
            }
        }
        $sections[] = implode(PHP_EOL, $filter_local);

        // [rewrite_remote]
        $rewrite_remote = ['[rewrite_remote]'];
        if (isset($qx_config['rewrite_remote'])) {
            foreach ($qx_config['rewrite_remote'] as $rr) {
                $rewrite_remote[] = $rr;
            }
        }
        $sections[] = implode(PHP_EOL, $rewrite_remote);

        // [rewrite_local]
        $rewrite_local = ['[rewrite_local]'];
        if (isset($qx_config['rewrite_local'])) {
            foreach ($qx_config['rewrite_local'] as $rl) {
                $rewrite_local[] = $rl;
            }
        }
        $sections[] = implode(PHP_EOL, $rewrite_local);

        // [mitm]
        $mitm = ['[mitm]'];
        if (isset($qx_config['mitm'])) {
            foreach ($qx_config['mitm'] as $k => $v) {
                $mitm[] = "{$k}={$v}";
            }
        }
        $sections[] = implode(PHP_EOL, $mitm);

        return implode(PHP_EOL . PHP_EOL, $sections);
    }
}
