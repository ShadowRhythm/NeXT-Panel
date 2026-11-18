<?php

declare(strict_types=1);

$_ENV['V2RayJson_Config'] = [
    'log' => [
        'error' => [
            'level' => 'error',
            'type' => 'console',
        ],
        'access' => [
            'type' => 'none',
        ],
    ],
    'dns' => [
        'nameServer' => [
            [
                'address' => '1.1.1.1',
            ],
            [
                'address' => '1.0.0.1',
            ],
        ],
    ],
    'inbounds' => [
        [
            'protocol' => 'socks',
            'settings' => [
                'udpEnabled' => true,
                'address' => '127.0.0.1',
                'packetEncoding' => 'packet',
            ],
            'port' => 7892,
            'listen' => '127.0.0.1',
        ],
        [
            'protocol' => 'http',
            'settings' => [],
            'port' => 7893,
            'listen' => '127.0.0.1',
        ],
    ],
    'outbounds' => [],
];

//sing-box 的 override_android_vpn 仅 Android 支持，按订阅客户端 User Agent 判断
$isSfaVisitor = str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'SFA');

$_ENV['SingBox_Config'] = [
    'log' => [
        'disabled' => false,
        'level' => 'error',
        'timestamp' => true,
    ],
    'dns' => [
        'servers' => [
            [
                'tag' => 'local',
                'type' => 'local',
            ],
            [
                'tag' => 'alidns',
                'type' => 'https',
                'server' => '223.5.5.5',
            ],
            [
                'tag' => 'cloudflare',
                'type' => 'tls',
                'server' => '1.1.1.1',
                'server_port' => 853,
                'detour' => '🔰 Select',
            ],
            [
                'tag' => 'google',
                'type' => 'tls',
                'server' => '8.8.8.8',
                'server_port' => 853,
                'detour' => '🔰 Select',
            ],
            [
                'tag' => 'opendns',
                'type' => 'tls',
                'server' => '208.67.222.222',
                'server_port' => 853,
                'detour' => '🔰 Select',
            ],
            [
                'tag' => 'mine_853',
                'type' => $_ENV['dns_type_853'] ?? 'tls',
                'server' => $_ENV['dns_server_853'] ?? 'dns.google',
                'server_port' => (int) ($_ENV['dns_server_port_853'] ?? 853),
                'domain_resolver' => 'local',
            ],
            [
                'tag' => 'mine_443',
                'type' => $_ENV['dns_type_443'] ?? 'https',
                'server' => $_ENV['dns_server_443'] ?? 'dns.google',
                'server_port' => (int) ($_ENV['dns_server_port_443'] ?? 443),
                'path' => $_ENV['dns_path_443'] ?? '/dns-query',
                'domain_resolver' => 'local',
            ],
            [
                'tag' => 'fakeip',
                'type' => 'fakeip',
                'inet4_range' => '198.18.0.0/15',
                'inet6_range' => 'fc00::/18',
            ],
        ],
        'rules' => [
            [
                'query_type' => [
                    'SVCB',
                    'HTTPS',
                ],
                'action' => 'predefined',
                'rcode' => 'REFUSED',
            ],
            [
                'rule_set' => [
                    'geosite-category-ads-all',
                ],
                'action' => 'predefined',
                'rcode' => 'REFUSED',
            ],
            [
                'clash_mode' => 'Direct',
                'server' => 'local',
            ],
            [
                'clash_mode' => 'Global',
                'server' => 'fakeip',
            ],
            [
                'rule_set' => [
                    'geosite-geolocation-cn',
                    'geosite-cn',
                    'geosite-netease',
                    'geosite-bilibili',
                ],
                'server' => 'local',
            ],
            [
                'rule_set' => [
                    'geosite-geolocation-!cn',
                ],
                'server' => 'fakeip',
            ],
            [
                'clash_mode' => 'Rule',
                'server' => 'fakeip',
            ],
        ],
        'final' => 'cloudflare',
        'strategy' => 'prefer_ipv4',
    ],
    'inbounds' => [
        [
            'type' => 'tun',
            'tag' => 'in',
            'address' => [
                '172.18.0.1/30',
                'fdfe:dcba:9876::1/126',
            ],
            'auto_route' => true,
            'strict_route' => true,
            'endpoint_independent_nat' => true,
            'stack' => 'mixed',
        ],
    ],
    'outbounds' => [
        [
            'tag' => '🔰 Select',
            'type' => 'selector',
            'outbounds' => [
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '♻️ Auto',
            'interrupt_exist_connections' => true,
        ],
        [
            'type' => 'urltest',
            'tag' => '♻️ Auto',
            'outbounds' => [],
            'url' => 'https://cp.cloudflare.com/generate_204',
            'interval' => '3m',
            'tolerance' => 50,
            'idle_timeout' => '30m',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '♾️ AI',
            'type' => 'selector',
            'outbounds' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '🔰 Select',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '🎥 Netflix',
            'type' => 'selector',
            'outbounds' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '🔰 Select',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '🌍 Global Media',
            'type' => 'selector',
            'outbounds' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '🔰 Select',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '🇭🇰 HK Media',
            'type' => 'selector',
            'outbounds' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '🔰 Select',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '🇨🇳 China Media',
            'type' => 'selector',
            'outbounds' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
            'default' => '🎯 Direct',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '🎮 Gaming',
            'type' => 'selector',
            'outbounds' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
            'default' => '🎯 Direct',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '📲 Telegram',
            'type' => 'selector',
            'outbounds' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '🔰 Select',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => 'Ⓜ️ Microsoft',
            'type' => 'selector',
            'outbounds' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
            'default' => '🎯 Direct',
            'interrupt_exist_connections' => true,
        ],
        [
            'tag' => '🍎 Apple',
            'type' => 'selector',
            'outbounds' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
            'default' => '🎯 Direct',
            'interrupt_exist_connections' => true,
        ],
        [
            'type' => 'direct',
            'tag' => '🎯 Direct',
        ],
        [
            'type' => 'block',
            'tag' => '🛑 Block',
        ],
        [
            'tag' => '🐟 Final',
            'type' => 'selector',
            'outbounds' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
            'default' => '🔰 Select',
            'interrupt_exist_connections' => true,
        ],
    ],
    'route' => [
        'rules' => [
            [
                'action' => 'sniff',
                'timeout' => '1s',
            ],
            [
                'protocol' => 'dns',
                'action' => 'hijack-dns',
            ],
            [
                'ip_is_private' => true,
                'outbound' => '🎯 Direct',
            ],
            [
                'domain_suffix' => [
                    'local',
                    'lan',
                    'home.arpa',
                    'localhost',
                ],
                'outbound' => '🎯 Direct',
            ],
            [
                'domain_keyword' => [
                    'ntp',
                ],
                'domain_suffix' => [
                    'pool.ntp.org',
                    'time.apple.com',
                    'time.windows.com',
                ],
                'outbound' => '🎯 Direct',
            ],
            [
                'rule_set' => [
                    'geosite-category-ads-all',
                ],
                'action' => 'reject',
                'method' => 'default',
            ],
            [
                'clash_mode' => 'Direct',
                'outbound' => '🎯 Direct',
            ],
            [
                'clash_mode' => 'Global',
                'outbound' => '🔰 Select',
            ],
            // ♾️ AI
            [
                'rule_set' => [
                    'overseas-ai',
                ],
                'outbound' => '♾️ AI',
            ],
            // 🎥 Netflix
            [
                'rule_set' => [
                    'geosite-netflix',
                ],
                'outbound' => '🎥 Netflix',
            ],
            // 🌍 Global Media
            [
                'rule_set' => [
                    'geosite-category-media',
                ],
                'domain_suffix' => [
                    'youtube.com',
                    'googlevideo.com',
                    'ytimg.com',
                    'youtu.be',
                    'tiktok.com',
                    'tiktokcdn.com',
                    'tiktokv.com',
                    'byteoversea.com',
                    'ibytedtos.com',
                    'musical.ly',
                    'disneyplus.com',
                    'disney-plus.net',
                    'bamgrid.com',
                    'spotify.com',
                    'scdn.co',
                    'primevideo.com',
                    'amazonvideo.com',
                    'aiv-cdn.net',
                    'max.com',
                    'hbo.com',
                    'hbomax.com',
                    'hulu.com',
                    'huluim.com',
                    'hulustream.com',
                    'gamer.com.tw',
                    'twitch.tv',
                    'ttvnw.net',
                    'abema.tv',
                    'abema-tv.com',
                    'ameba.jp',
                    'dmm.com',
                    'dmm.co.jp',
                    'fanza.com',
                    'nicovideo.jp',
                    'nimg.jp',
                ],
                'outbound' => '🌍 Global Media',
            ],
            // 🇭🇰 HK Media
            [
                'domain_suffix' => [
                    // JOOX
                    'joox.com',
                    'i-joox.com',
                    'sanook.com',
                    // Viu & ViuTV
                    'viu.com',
                    'viu.tv',
                    'viu.now.com',
                    'vuclip.com',
                    'd1k2us671qcoau.cloudfront.net',
                    'd2anahhhmp1ffz.cloudfront.net',
                    'dfp6rglgjqszk.cloudfront.net',
                    'viu.ott.cams.id',
                    'viu.ott.wavenet.stream',
                    // Now E & Now TV
                    'nowe.com',
                    'nowe.hk',
                    'now.com',
                    'now.com.hk',
                    'now-tv.com',
                    'nowplayer.now.com',
                    'nowestatic.com',
                    'nowtv.pccw.com',
                    // myTV SUPER & TVB
                    'mytvsuper.com',
                    'mytvsuperlimited.hb.omtrdc.net',
                    'mytvsuperlimited.sc.omtrdc.net',
                    'tvb.com',
                    'tvb.com.au',
                    'tvbanywhere.com',
                    'tvbanywhere.com.sg',
                    'tvbc.com.cn',
                    'tvbevent.com',
                    'tvbeventpower.com.hk',
                    'tvbusa.com',
                    'tvbweekly.com',
                    'tvmedia.net.au',
                    'bigbigchannel.com.hk',
                    'bigbigshop.com',
                    // HOY TV & i-CABLE
                    'hoy.tv',
                    'fantv.hk',
                    'icable.com',
                    'cabletv.com.hk',
                    'hkcable.com.hk',
                    // MOOV
                    'moov.hk',
                    'moov-music.com',
                    // RTHK (香港电台)
                    'rthk.hk',
                    'rthk.org.hk',
                    // HBO GO Asia (HK)
                    'hbogoasia.com',
                    'hbogoasia.hk',
                    // Bilibili 港澳台限制番剧 (BiliBiliIntl)
                    'bilibili.tv',
                    'biliintl.com',
                    'bstarstatic.com',
                    // hmvod
                    'hmvod.com',
                    'hmvod.com.hk',
                    // 香港广播 (商台、新城)
                    '881903.com',
                    'metroradio.com.hk',
                ],
                'domain_keyword' => [
                    'jooxweb-api',
                ],
                'domain' => [
                    'p-bstar-static.bilibiligame.net',
                ],
                'ip_cidr' => [
                    '119.28.182.0/23',
                    '203.205.234.0/23',
                ],
                'outbound' => '🇭🇰 HK Media',
            ],
            // 🇨🇳 China Media
            [
                'rule_set' => [
                    'geosite-category-media-cn',
                    'geosite-netease',
                    'geosite-bilibili',
                ],
                'outbound' => '🇨🇳 China Media',
            ],
            // 🎮 Gaming
            [
                'rule_set' => [
                    'geosite-category-games',
                ],
                'outbound' => '🎮 Gaming',
            ],
            // 📲 Telegram
            [
                'ip_cidr' => [
                    '91.108.4.0/22',
                    '91.108.8.0/22',
                    '91.108.12.0/22',
                    '91.108.16.0/22',
                    '91.108.20.0/22',
                    '91.108.56.0/22',
                    '149.154.160.0/20',
                    '149.154.164.0/22',
                    '149.154.168.0/22',
                    '149.154.172.0/22',
                    '185.76.151.0/24',
                    '2001:b28:f23d::/48',
                    '2001:b28:f23f::/48',
                    '2001:67c:4e8::/48',
                ],
                'rule_set' => [
                    'geosite-telegram',
                ],
                'outbound' => '📲 Telegram',
            ],
            // 🐙 GitHub
            [
                'rule_set' => [
                    'geosite-github',
                ],
                'domain_suffix' => [
                    'github.com',
                    'github.io',
                    'githubassets.com',
                    'githubusercontent.com',
                    'githubcopilot.com',
                    'ghcr.io',
                    'git.io',
                ],
                'domain_keyword' => [
                    'github',
                ],
                'outbound' => '🔰 Select',
            ],
            // Ⓜ️ Microsoft
            [
                'rule_set' => [
                    'geosite-microsoft',
                ],
                'outbound' => 'Ⓜ️ Microsoft',
            ],
            // 🍎 Apple
            [
                'rule_set' => [
                    'geosite-apple',
                ],
                'outbound' => '🍎 Apple',
            ],
            // 🌐 核心国际基础服务
            [
                'domain_suffix' => [
                    'google.com',
                    'googleapis.com',
                    'gstatic.com',
                    'googleusercontent.com',
                    'googletagmanager.com',
                    'android.com',
                    'gmail.com',
                    'x.com',
                    'twitter.com',
                    'twimg.com',
                    't.co',
                    'facebook.com',
                    'fbcdn.net',
                    'instagram.com',
                    'cdninstagram.com',
                    'threads.net',
                    'whatsapp.com',
                    'whatsapp.net',
                    'discord.com',
                    'discord.gg',
                    'discordapp.com',
                    'discordapp.net',
                    'discord.media',
                    'reddit.com',
                    'redditmedia.com',
                    'redd.it',
                    'wikipedia.org',
                    'wikimedia.org',
                    'cloudflare.com',
                    'cloudflare.net',
                ],
                'outbound' => '🔰 Select',
            ],
            // 🎯 Direct
            [
                'rule_set' => [
                    'geosite-geolocation-cn',
                    'geosite-cn',
                ],
                'outbound' => '🎯 Direct',
            ],
            [
                'rule_set' => [
                    'geoip-cn',
                ],
                'outbound' => '🎯 Direct',
            ],
            // 国外其它
            [
                'rule_set' => [
                    'geosite-geolocation-!cn',
                ],
                'outbound' => '🔰 Select',
            ],
        ],
        'rule_set' => [
            [
                'tag' => 'overseas-ai',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/viewer12/OverseasAI.list@main/rule/Singbox/OverseasAI/OverseasAI.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-netflix',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-netflix.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-category-media',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-category-media.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-telegram',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-telegram.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-category-games',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-category-games.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-github',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-github.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-microsoft',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-microsoft.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-apple',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-apple.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-category-media-cn',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-category-media-cn.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geoip-cn',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geoip@rule-set/geoip-cn.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-cn',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-cn.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-category-ads-all',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-category-ads-all.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-geolocation-cn',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-geolocation-cn.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-geolocation-!cn',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-geolocation-!cn.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-netease',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-netease.srs',
                'update_interval' => '1d',
            ],
            [
                'tag' => 'geosite-bilibili',
                'type' => 'remote',
                'format' => 'binary',
                'url' => 'https://' . ($_ENV['jsdelivr_url'] ?? 'fastly.jsdelivr.net') . '/gh/SagerNet/sing-geosite@rule-set/geosite-bilibili.srs',
                'update_interval' => '1d',
            ],
        ],
        'final' => '🐟 Final',
        'auto_detect_interface' => true,
        'override_android_vpn' => $isSfaVisitor,
        'default_http_client' => 'rules-client',
        'default_domain_resolver' => [
            'server' => 'local',
            'rewrite_ttl' => 60,
        ],
    ],
    'http_clients' => [
        [
            'tag' => 'rules-client',
            'detour' => '🔰 Select',
        ],
    ],
    'experimental' => [
        'cache_file' => [
            'enabled' => true,
            'cache_id' => '',
            'path' => 'cache.db',
        ],
    ],
];

// SingBox group indexes to be inserted node names
$_ENV['SingBox_Group_Indexes'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 13];

$_ENV['Clash_Config'] = [
    'port' => 7890,
    'socks-port' => 7891,
    'allow-lan' => false,
    'mode' => 'Rule',
    'ipv6' => true,
    'log-level' => 'error',
    'tcp-concurrent' => $_ENV['tcp_concurrent'],
    'external-controller' => '0.0.0.0:9091',
    'dns' => [
        'enable' => true,
        'ipv6' => true,
        'enhanced-mode' => 'fake-ip',
        'fake-ip-range' => '198.18.0.1/16',
        'fake-ip-range6' => 'fdfe:dcba:9876::1/64',
        'respect-rules' => true,
        'fake-ip-filter' => [
            '*.lan',
            '*.local',
            '*.arpa',
            'time.*.com',
            'ntp.*.com',
            '+.pool.ntp.org',
            '+.msftconnecttest.com',
            '+.msftncsi.com',
            'msftconnecttest.com',
            'msftncsi.com',
            'localhost.ptlogin2.qq.com',
        ],
        'default-nameserver' => [
            '223.5.5.5',
            '119.29.29.29',
        ],
        'nameserver' => [
            'https://1.1.1.1/dns-query',
            'https://8.8.8.8/dns-query',
        ],
        'nameserver-policy' => [
            'geosite:cn,private' => [
                'https://223.5.5.5/dns-query',
                'https://doh.pub/dns-query',
            ],
        ],
        'proxy-server-nameserver' => [
            '223.5.5.5',
            '119.29.29.29',
        ],
    ],
];

// Clash group indexes to be inserted node names
$_ENV['Clash_Group_Indexes'] = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 14];

$_ENV['Clash_Group_Config'] = [
    'proxy-groups' => [
        [
            'name' => '🔰 Select',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
        [
            'name' => '♻️ Auto',
            'type' => 'url-test',
            'url' => 'http://cp.cloudflare.com/generate_204',
            'interval' => 300,
            // 插入节点名称
            'proxies' => [
            ],
        ],
        [
            'name' => '♾️ AI',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
        [
            'name' => '🎥 Netflix',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
        [
            'name' => '🌍 Global Media',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
        [
            'name' => '🇭🇰 HK Media',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
        [
            'name' => '🇨🇳 China Media',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
        ],
        [
            'name' => '🎮 Gaming',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
        ],
        [
            'name' => '📲 Telegram',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
        [
            'name' => 'Ⓜ️ Microsoft',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
        ],
        [
            'name' => '🍎 Apple',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🎯 Direct',
                '🔰 Select',
                '♻️ Auto',
            ],
        ],
        [
            'name' => '⛔️ AdBlock',
            'type' => 'select',
            'proxies' => [
                '🛑 Block',
                '🎯 Direct',
                '🔰 Select',
            ],
        ],
        [
            'name' => '🎯 Direct',
            'type' => 'select',
            'proxies' => [
                'DIRECT',
            ],
        ],
        [
            'name' => '🛑 Block',
            'type' => 'select',
            'proxies' => [
                'REJECT',
            ],
        ],
        [
            'name' => '🐟 Final',
            'type' => 'select',
            // 插入节点名称
            'proxies' => [
                '🔰 Select',
                '♻️ Auto',
                '🎯 Direct',
            ],
        ],
    ],
    'rules' => [
        // ⛔️ AdBlock
        'GEOSITE,category-ads-all,🛑 Block',
        'GEOIP,private,🎯 Direct,no-resolve',
        // BT / P2P / 本地下载工具
        'PROCESS-NAME,DownloadService,🎯 Direct',
        'PROCESS-NAME,Folx,🎯 Direct',
        'PROCESS-NAME,Motrix,🎯 Direct',
        'PROCESS-NAME,NetTransport,🎯 Direct',
        'PROCESS-NAME,Thunder,🎯 Direct',
        'PROCESS-NAME,Transmission,🎯 Direct',
        'PROCESS-NAME,WebTorrent Helper,🎯 Direct',
        'PROCESS-NAME,WebTorrent,🎯 Direct',
        'PROCESS-NAME,Weiyun,🎯 Direct',
        'PROCESS-NAME,aria2c,🎯 Direct',
        'PROCESS-NAME,fdm,🎯 Direct',
        'PROCESS-NAME,qbittorrent,🎯 Direct',
        'PROCESS-NAME,uTorrent,🎯 Direct',
        'DOMAIN-KEYWORD,aria2,🎯 Direct',
        'DOMAIN-KEYWORD,xunlei,🎯 Direct',
        'DOMAIN-KEYWORD,yunpan,🎯 Direct',
        'DOMAIN-KEYWORD,thunder,🎯 Direct',
        // 局域网与本地网络直连
        'DOMAIN-SUFFIX,local,🎯 Direct',
        'DOMAIN-SUFFIX,lan,🎯 Direct',
        'DOMAIN-SUFFIX,home.arpa,🎯 Direct',
        'DOMAIN-SUFFIX,localhost,🎯 Direct',
        // NTP 网络授时服务
        'DOMAIN-KEYWORD,ntp,🎯 Direct',
        'DOMAIN-SUFFIX,pool.ntp.org,🎯 Direct',
        'DOMAIN-SUFFIX,time.apple.com,🎯 Direct',
        'DOMAIN-SUFFIX,time.windows.com,🎯 Direct',
        // ♾️ AI (viewer12/OverseasAI.list)
        // OpenAI / ChatGPT / Sora
        'DOMAIN-SUFFIX,ai.com,♾️ AI',
        'DOMAIN-SUFFIX,chatgpt.com,♾️ AI',
        'DOMAIN-SUFFIX,chatgpt.livekit.cloud,♾️ AI',
        'DOMAIN-SUFFIX,host.livekit.cloud,♾️ AI',
        'DOMAIN-SUFFIX,turn.livekit.cloud,♾️ AI',
        'DOMAIN-SUFFIX,livekit.cloud,♾️ AI',
        'DOMAIN-SUFFIX,oaistatic.com,♾️ AI',
        'DOMAIN-SUFFIX,oaiusercontent.com,♾️ AI',
        'DOMAIN-SUFFIX,openai.com,♾️ AI',
        'DOMAIN-SUFFIX,openaiapi-site.azureedge.net,♾️ AI',
        'DOMAIN-SUFFIX,openaicom.imgix.net,♾️ AI',
        'DOMAIN-KEYWORD,openai,♾️ AI',
        'DOMAIN,chat.openai.com.cdn.cloudflare.net,♾️ AI',
        'DOMAIN,openai-api.arkoselabs.com,♾️ AI',
        'DOMAIN,openaicomproductionae4b.blob.core.windows.net,♾️ AI',
        'DOMAIN,production-openaicom-storage.azureedge.net,♾️ AI',
        // Anthropic / Claude
        'DOMAIN-SUFFIX,anthropic.com,♾️ AI',
        'DOMAIN-SUFFIX,claude.ai,♾️ AI',
        'DOMAIN-SUFFIX,claudeusercontent.com,♾️ AI',
        'DOMAIN-KEYWORD,claude,♾️ AI',
        'DOMAIN,cdn.usefathom.com,♾️ AI',
        // Google Gemini / DeepMind / MakerSuite
        'DOMAIN,ai.google.dev,♾️ AI',
        'DOMAIN,alkalimakersuite-pa.clients6.google.com,♾️ AI',
        'DOMAIN,makersuite.google.com,♾️ AI',
        'DOMAIN-SUFFIX,bard.google.com,♾️ AI',
        'DOMAIN-SUFFIX,deepmind.com,♾️ AI',
        'DOMAIN-SUFFIX,deepmind.google,♾️ AI',
        'DOMAIN-SUFFIX,gemini.google.com,♾️ AI',
        'DOMAIN-SUFFIX,generativeai.google,♾️ AI',
        'DOMAIN-SUFFIX,proactivebackend-pa.googleapis.com,♾️ AI',
        'DOMAIN-KEYWORD,colab,♾️ AI',
        'DOMAIN-KEYWORD,generativelanguage,♾️ AI',
        // xAI / Grok
        'DOMAIN-SUFFIX,x.ai,♾️ AI',
        'DOMAIN-SUFFIX,grok.com,♾️ AI',
        'DOMAIN-KEYWORD,grok,♾️ AI',
        // Perplexity AI
        'DOMAIN-SUFFIX,perplexity.ai,♾️ AI',
        'DOMAIN-SUFFIX,pplx.ai,♾️ AI',
        // Microsoft Copilot / Bing AI / Sydney
        'DOMAIN,copilot.microsoft.com,♾️ AI',
        'DOMAIN,sydney.bing.com,♾️ AI',
        'DOMAIN,edgeservices.bing.com,♾️ AI',
        'DOMAIN,gateway.bingviz.microsoft.net,♾️ AI',
        'DOMAIN,gateway.bingviz.microsoftapp.net,♾️ AI',
        'DOMAIN,api.microsoftapp.net,♾️ AI',
        'DOMAIN,bing-shopping.microsoft-falcon.io,♾️ AI',
        // Meta AI
        'DOMAIN-SUFFIX,meta.ai,♾️ AI',
        'DOMAIN-SUFFIX,imagine.meta.com,♾️ AI',
        // AI Code & Developer Tools
        'DOMAIN-SUFFIX,cursor.com,♾️ AI',
        'DOMAIN-SUFFIX,cursor.sh,♾️ AI',
        'DOMAIN-SUFFIX,anysphere.co,♾️ AI',
        'DOMAIN-SUFFIX,windsurf.ai,♾️ AI',
        'DOMAIN-SUFFIX,codeium.com,♾️ AI',
        'DOMAIN-SUFFIX,codeiumdata.com,♾️ AI',
        'DOMAIN-SUFFIX,tabnine.com,♾️ AI',
        'DOMAIN-SUFFIX,v0.dev,♾️ AI',
        'DOMAIN-SUFFIX,bolt.new,♾️ AI',
        'DOMAIN-SUFFIX,lovable.dev,♾️ AI',
        'DOMAIN-SUFFIX,lovable.ai,♾️ AI',
        'DOMAIN-SUFFIX,trae.ai,♾️ AI',
        'DOMAIN-SUFFIX,replit.com,♾️ AI',
        'DOMAIN-SUFFIX,replit.dev,♾️ AI',
        // AI Creative & Generative Media (Flux, Midjourney, Suno, etc.)
        'DOMAIN-SUFFIX,midjourney.com,♾️ AI',
        'DOMAIN-SUFFIX,bfl.ai,♾️ AI',
        'DOMAIN-SUFFIX,bfl.ml,♾️ AI',
        'DOMAIN-SUFFIX,blackforestlabs.ai,♾️ AI',
        'DOMAIN-SUFFIX,suno.ai,♾️ AI',
        'DOMAIN-SUFFIX,suno.com,♾️ AI',
        'DOMAIN-SUFFIX,udio.com,♾️ AI',
        'DOMAIN-SUFFIX,runwayml.com,♾️ AI',
        'DOMAIN-SUFFIX,runway.com,♾️ AI',
        'DOMAIN-SUFFIX,pika.art,♾️ AI',
        'DOMAIN-SUFFIX,lumalabs.ai,♾️ AI',
        'DOMAIN-SUFFIX,luma.ai,♾️ AI',
        'DOMAIN-SUFFIX,krea.ai,♾️ AI',
        'DOMAIN-SUFFIX,ideogram.ai,♾️ AI',
        'DOMAIN-SUFFIX,leonardo.ai,♾️ AI',
        'DOMAIN-SUFFIX,civitai.com,♾️ AI',
        'DOMAIN-SUFFIX,elevenlabs.io,♾️ AI',
        'DOMAIN-SUFFIX,comfy.org,♾️ AI',
        'DOMAIN-SUFFIX,comfydeploy.com,♾️ AI',
        // Model Hubs, Frontier Labs & Inference APIs
        'DOMAIN-SUFFIX,mistral.ai,♾️ AI',
        'DOMAIN-SUFFIX,cohere.com,♾️ AI',
        'DOMAIN-SUFFIX,cohere.ai,♾️ AI',
        'DOMAIN-SUFFIX,cerebras.ai,♾️ AI',
        'DOMAIN-SUFFIX,fireworks.ai,♾️ AI',
        'DOMAIN-SUFFIX,deepinfra.com,♾️ AI',
        'DOMAIN-SUFFIX,openrouter.ai,♾️ AI',
        'DOMAIN-SUFFIX,replicate.com,♾️ AI',
        'DOMAIN-SUFFIX,replicate.delivery,♾️ AI',
        'DOMAIN-SUFFIX,together.ai,♾️ AI',
        'DOMAIN-SUFFIX,together.xyz,♾️ AI',
        'DOMAIN-SUFFIX,groq.com,♾️ AI',
        'DOMAIN-SUFFIX,huggingface.co,♾️ AI',
        'DOMAIN-SUFFIX,hf.co,♾️ AI',
        'DOMAIN-SUFFIX,hf.space,♾️ AI',
        'DOMAIN-SUFFIX,poe.com,♾️ AI',
        'DOMAIN-SUFFIX,coze.com,♾️ AI',
        'DOMAIN-SUFFIX,dify.ai,♾️ AI',
        'DOMAIN-SUFFIX,jasper.ai,♾️ AI',
        'DOMAIN-SUFFIX,character.ai,♾️ AI',
        'DOMAIN-SUFFIX,phind.com,♾️ AI',
        'DOMAIN-SUFFIX,ollama.com,♾️ AI',
        'DOMAIN-SUFFIX,jan.ai,♾️ AI',
        'DOMAIN-SUFFIX,deepgram.com,♾️ AI',
        'DOMAIN-SUFFIX,assemblyai.com,♾️ AI',
        'DOMAIN-SUFFIX,pinecone.io,♾️ AI',
        'DOMAIN-SUFFIX,qdrant.tech,♾️ AI',
        'DOMAIN-SUFFIX,weaviate.io,♾️ AI',
        'DOMAIN-SUFFIX,voyageai.com,♾️ AI',
        // Apple Intelligence & Siri PCC
        'DOMAIN-SUFFIX,smoot.apple.com,♾️ AI',
        'DOMAIN,guzzoni.apple.com,♾️ AI',
        'DOMAIN,gspe1-ssl.ls.apple.com,♾️ AI',
        'DOMAIN,apple-relay.mask.apple-dns.net,♾️ AI',
        'DOMAIN,apple-relay.fastly-edge.com,♾️ AI',
        'DOMAIN,apple-relay.apple.com,♾️ AI',
        'DOMAIN,apple-relay.cloudflare.com,♾️ AI',
        'DOMAIN,cp4.cloudflare.com,♾️ AI',
        // 🎥 Netflix
        'GEOIP,netflix,🎥 Netflix',
        'GEOSITE,netflix,🎥 Netflix',
        'DOMAIN-SUFFIX,netflix.com,🎥 Netflix',
        'DOMAIN-SUFFIX,netflix.net,🎥 Netflix',
        'DOMAIN-SUFFIX,nflximg.net,🎥 Netflix',
        'DOMAIN-SUFFIX,nflxvideo.net,🎥 Netflix',
        'DOMAIN-SUFFIX,nflxext.com,🎥 Netflix',
        'DOMAIN-SUFFIX,nflxso.net,🎥 Netflix',
        'DOMAIN-SUFFIX,fast.com,🎥 Netflix',
        'DOMAIN-KEYWORD,netflixdnstest,🎥 Netflix',
        // 🌍 Global Media
        'GEOSITE,category-media,🌍 Global Media',
        // YouTube
        'DOMAIN-SUFFIX,youtube.com,🌍 Global Media',
        'DOMAIN-SUFFIX,googlevideo.com,🌍 Global Media',
        'DOMAIN-SUFFIX,ytimg.com,🌍 Global Media',
        'DOMAIN-SUFFIX,youtu.be,🌍 Global Media',
        // TikTok / 国际版抖音
        'GEOSITE,tiktok,🌍 Global Media',
        'DOMAIN-SUFFIX,tiktok.com,🌍 Global Media',
        'DOMAIN-SUFFIX,tiktokcdn.com,🌍 Global Media',
        'DOMAIN-SUFFIX,tiktokv.com,🌍 Global Media',
        'DOMAIN-SUFFIX,byteoversea.com,🌍 Global Media',
        'DOMAIN-SUFFIX,ibytedtos.com,🌍 Global Media',
        'DOMAIN-SUFFIX,musical.ly,🌍 Global Media',
        // Disney+ / Star+
        'GEOSITE,disney,🌍 Global Media',
        'DOMAIN-SUFFIX,disneyplus.com,🌍 Global Media',
        'DOMAIN-SUFFIX,disney-plus.net,🌍 Global Media',
        'DOMAIN-SUFFIX,bamgrid.com,🌍 Global Media',
        // Spotify
        'GEOSITE,spotify,🌍 Global Media',
        'DOMAIN-SUFFIX,spotify.com,🌍 Global Media',
        'DOMAIN-SUFFIX,scdn.co,🌍 Global Media',
        // Amazon Prime Video
        'GEOSITE,primevideo,🌍 Global Media',
        'DOMAIN-SUFFIX,primevideo.com,🌍 Global Media',
        'DOMAIN-SUFFIX,amazonvideo.com,🌍 Global Media',
        'DOMAIN-SUFFIX,aiv-cdn.net,🌍 Global Media',
        // HBO / Max
        'GEOSITE,hbo,🌍 Global Media',
        'DOMAIN-SUFFIX,max.com,🌍 Global Media',
        'DOMAIN-SUFFIX,hbo.com,🌍 Global Media',
        'DOMAIN-SUFFIX,hbomax.com,🌍 Global Media',
        // Hulu
        'GEOSITE,hulu,🌍 Global Media',
        'DOMAIN-SUFFIX,hulu.com,🌍 Global Media',
        'DOMAIN-SUFFIX,huluim.com,🌍 Global Media',
        'DOMAIN-SUFFIX,hulustream.com,🌍 Global Media',
        // 巴哈姆特动画疯 / Twitch
        'DOMAIN-SUFFIX,gamer.com.tw,🌍 Global Media',
        'DOMAIN-SUFFIX,twitch.tv,🌍 Global Media',
        'DOMAIN-SUFFIX,ttvnw.net,🌍 Global Media',
        // 日本主流媒体 (Abema / DMM / Niconico)
        'GEOSITE,abema,🌍 Global Media',
        'DOMAIN-SUFFIX,abema.tv,🌍 Global Media',
        'DOMAIN-SUFFIX,abema-tv.com,🌍 Global Media',
        'DOMAIN-SUFFIX,ameba.jp,🌍 Global Media',
        'GEOSITE,dmm,🌍 Global Media',
        'DOMAIN-SUFFIX,dmm.com,🌍 Global Media',
        'DOMAIN-SUFFIX,dmm.co.jp,🌍 Global Media',
        'DOMAIN-SUFFIX,fanza.com,🌍 Global Media',
        'DOMAIN-SUFFIX,nicovideo.jp,🌍 Global Media',
        'DOMAIN-SUFFIX,nimg.jp,🌍 Global Media',
        // 🇭🇰 HK Media
        // JOOX
        'PROCESS-NAME,com.tencent.ibg.joox,🇭🇰 HK Media',
        'DOMAIN-KEYWORD,jooxweb-api,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,i-joox.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,joox.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,sanook.com,🇭🇰 HK Media',
        'IP-CIDR,119.28.182.0/23,🇭🇰 HK Media,no-resolve',
        'IP-CIDR,203.205.234.0/23,🇭🇰 HK Media,no-resolve',
        // Viu & ViuTV
        'PROCESS-NAME,com.viu.pad,🇭🇰 HK Media',
        'PROCESS-NAME,com.vuclip.viu,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,viu.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,viu.tv,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,viu.now.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,vuclip.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,d1k2us671qcoau.cloudfront.net,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,d2anahhhmp1ffz.cloudfront.net,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,dfp6rglgjqszk.cloudfront.net,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,viu.ott.cams.id,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,viu.ott.wavenet.stream,🇭🇰 HK Media',
        // Now E & Now TV
        'DOMAIN-SUFFIX,nowe.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,nowe.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,now.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,now.com.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,now-tv.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,nowplayer.now.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,nowestatic.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,nowtv.pccw.com,🇭🇰 HK Media',
        // myTV SUPER & TVB
        'DOMAIN-SUFFIX,mytvsuper.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,mytvsuperlimited.hb.omtrdc.net,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,mytvsuperlimited.sc.omtrdc.net,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvb.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvb.com.au,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbanywhere.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbanywhere.com.sg,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbc.com.cn,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbevent.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbeventpower.com.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbusa.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvbweekly.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,tvmedia.net.au,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,bigbigchannel.com.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,bigbigshop.com,🇭🇰 HK Media',
        // HOY TV & i-CABLE
        'DOMAIN-SUFFIX,hoy.tv,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,fantv.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,icable.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,cabletv.com.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,hkcable.com.hk,🇭🇰 HK Media',
        // MOOV
        'DOMAIN-SUFFIX,moov.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,moov-music.com,🇭🇰 HK Media',
        // RTHK (香港电台)
        'DOMAIN-SUFFIX,rthk.hk,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,rthk.org.hk,🇭🇰 HK Media',
        // HBO GO Asia (HK)
        'DOMAIN-SUFFIX,hbogoasia.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,hbogoasia.hk,🇭🇰 HK Media',
        // Bilibili 港澳台限制番剧 (BiliBiliIntl)
        'DOMAIN-SUFFIX,bilibili.tv,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,biliintl.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,bstarstatic.com,🇭🇰 HK Media',
        'DOMAIN,p-bstar-static.bilibiligame.net,🇭🇰 HK Media',
        // hmvod
        'DOMAIN-SUFFIX,hmvod.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,hmvod.com.hk,🇭🇰 HK Media',
        // 香港广播电台 (商台、新城等)
        'DOMAIN-SUFFIX,881903.com,🇭🇰 HK Media',
        'DOMAIN-SUFFIX,metroradio.com.hk,🇭🇰 HK Media',
        // 🇨🇳 China Media
        'GEOSITE,category-media-cn,🇨🇳 China Media',
        // 哔哩哔哩 (Bilibili)
        'DOMAIN-SUFFIX,bilibili.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,bilivideo.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,biliapi.net,🇨🇳 China Media',
        'DOMAIN-SUFFIX,biliapi.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,bilibili.co,🇨🇳 China Media',
        'DOMAIN-SUFFIX,hdslb.com,🇨🇳 China Media',
        // 爱奇艺 (iQiyi)
        'DOMAIN-SUFFIX,iqiyi.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,iqiyipic.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,71.am,🇨🇳 China Media',
        // 腾讯视频 (Tencent Video)
        'DOMAIN-SUFFIX,v.qq.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,video.qq.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,qqlive.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,gtimg.com,🇨🇳 China Media',
        // 优酷 (Youku)
        'DOMAIN-SUFFIX,youku.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,ykimg.com,🇨🇳 China Media',
        // 芒果TV & 咪咕视频
        'DOMAIN-SUFFIX,mgtv.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,hunantv.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,miguvideo.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,migu.cn,🇨🇳 China Media',
        // 音乐与音频 (网易云音乐、QQ音乐、酷狗、喜马拉雅等)
        'DOMAIN-SUFFIX,music.163.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,music.126.net,🇨🇳 China Media',
        'DOMAIN-SUFFIX,qqmusic.qq.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,kugou.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,kuwo.cn,🇨🇳 China Media',
        'DOMAIN-SUFFIX,ximalaya.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,qingting.fm,🇨🇳 China Media',
        // 抖音与快手
        'DOMAIN-SUFFIX,douyin.com,🇨🇳 China Media',
        'DOMAIN-SUFFIX,kuaishou.com,🇨🇳 China Media',
        // 🎮 Gaming
        'GEOSITE,category-games,🎮 Gaming',
        // Steam
        'DOMAIN-SUFFIX,steampowered.com,🎮 Gaming',
        'DOMAIN-SUFFIX,steamcommunity.com,🎮 Gaming',
        'DOMAIN-SUFFIX,steamgames.com,🎮 Gaming',
        'DOMAIN-SUFFIX,steamusercontent.com,🎮 Gaming',
        'DOMAIN-SUFFIX,steamcontent.com,🎮 Gaming',
        'DOMAIN-SUFFIX,steamstatic.com,🎮 Gaming',
        // Epic Games
        'DOMAIN-SUFFIX,epicgames.com,🎮 Gaming',
        'DOMAIN-SUFFIX,unrealengine.com,🎮 Gaming',
        // Sony PlayStation
        'DOMAIN-SUFFIX,playstation.com,🎮 Gaming',
        'DOMAIN-SUFFIX,playstation.net,🎮 Gaming',
        'DOMAIN-SUFFIX,playstationnetwork.com,🎮 Gaming',
        'DOMAIN-SUFFIX,sonyentertainmentnetwork.com,🎮 Gaming',
        // Microsoft Xbox
        'DOMAIN-SUFFIX,xbox.com,🎮 Gaming',
        'DOMAIN-SUFFIX,xboxlive.com,🎮 Gaming',
        // Nintendo
        'DOMAIN-SUFFIX,nintendo.com,🎮 Gaming',
        'DOMAIN-SUFFIX,nintendo.net,🎮 Gaming',
        'DOMAIN-SUFFIX,nintendo-europe.com,🎮 Gaming',
        'DOMAIN-SUFFIX,nintendo.co.jp,🎮 Gaming',
        // Blizzard / Battle.net
        'DOMAIN-SUFFIX,battle.net,🎮 Gaming',
        'DOMAIN-SUFFIX,blizzard.com,🎮 Gaming',
        // Electronic Arts (EA)
        'DOMAIN-SUFFIX,ea.com,🎮 Gaming',
        'DOMAIN-SUFFIX,origin.com,🎮 Gaming',
        // Ubisoft
        'DOMAIN-SUFFIX,ubisoft.com,🎮 Gaming',
        'DOMAIN-SUFFIX,uplay.com,🎮 Gaming',
        // Riot Games
        'DOMAIN-SUFFIX,riotgames.com,🎮 Gaming',
        'DOMAIN-SUFFIX,leagueoflegends.com,🎮 Gaming',
        // Rockstar Games & GOG
        'DOMAIN-SUFFIX,rockstargames.com,🎮 Gaming',
        'DOMAIN-SUFFIX,gog.com,🎮 Gaming',
        // 📲 Telegram
        'GEOIP,telegram,📲 Telegram',
        'GEOSITE,telegram,📲 Telegram',
        'DOMAIN-SUFFIX,t.me,📲 Telegram',
        'DOMAIN-SUFFIX,tdesktop.com,📲 Telegram',
        'DOMAIN-SUFFIX,telegra.ph,📲 Telegram',
        'DOMAIN-SUFFIX,telegram.me,📲 Telegram',
        'DOMAIN-SUFFIX,telegram.org,📲 Telegram',
        'DOMAIN-SUFFIX,telesco.pe,📲 Telegram',
        // 🐙 GitHub
        'GEOSITE,github,🔰 Select',
        'DOMAIN-KEYWORD,github,🔰 Select',
        'DOMAIN-SUFFIX,github.com,🔰 Select',
        'DOMAIN-SUFFIX,github.io,🔰 Select',
        'DOMAIN-SUFFIX,githubassets.com,🔰 Select',
        'DOMAIN-SUFFIX,githubusercontent.com,🔰 Select',
        'DOMAIN-SUFFIX,githubcopilot.com,🔰 Select',
        'DOMAIN-SUFFIX,ghcr.io,🔰 Select',
        'DOMAIN-SUFFIX,git.io,🔰 Select',
        // Ⓜ️ Microsoft
        'GEOSITE,microsoft,Ⓜ️ Microsoft',
        'DOMAIN-SUFFIX,microsoft.com,Ⓜ️ Microsoft',
        'DOMAIN-SUFFIX,windows.com,Ⓜ️ Microsoft',
        'DOMAIN-SUFFIX,office.com,Ⓜ️ Microsoft',
        'DOMAIN-SUFFIX,live.com,Ⓜ️ Microsoft',
        'DOMAIN-SUFFIX,azure.com,Ⓜ️ Microsoft',
        // 🍎 Apple
        'GEOSITE,apple,🍎 Apple',
        'DOMAIN-SUFFIX,apple.com,🍎 Apple',
        'DOMAIN-SUFFIX,icloud.com,🍎 Apple',
        'DOMAIN-SUFFIX,mzstatic.com,🍎 Apple',
        // 🌐 核心国际基础服务
        // Google 全球生态 (搜索/Play商店/Drive/reCAPTCHA/Chrome等)
        'GEOSITE,google,🔰 Select',
        'DOMAIN-KEYWORD,google,🔰 Select',
        'DOMAIN-KEYWORD,1e100,🔰 Select',
        'DOMAIN-SUFFIX,google.com,🔰 Select',
        'DOMAIN-SUFFIX,google.com.hk,🔰 Select',
        'DOMAIN-SUFFIX,google.com.tw,🔰 Select',
        'DOMAIN-SUFFIX,google.co.jp,🔰 Select',
        'DOMAIN-SUFFIX,googleapis.com,🔰 Select',
        'DOMAIN-SUFFIX,gstatic.com,🔰 Select',
        'DOMAIN-SUFFIX,googleusercontent.com,🔰 Select',
        'DOMAIN-SUFFIX,googletagmanager.com,🔰 Select',
        'DOMAIN-SUFFIX,android.com,🔰 Select',
        'DOMAIN-SUFFIX,gmail.com,🔰 Select',
        'DOMAIN-SUFFIX,ggpht.com,🔰 Select',
        // Twitter / X 全球社交
        'GEOSITE,twitter,🔰 Select',
        'DOMAIN-SUFFIX,x.com,🔰 Select',
        'DOMAIN-SUFFIX,twitter.com,🔰 Select',
        'DOMAIN-SUFFIX,twimg.com,🔰 Select',
        'DOMAIN-SUFFIX,t.co,🔰 Select',
        // Meta 生态 (Facebook / Instagram / Threads / WhatsApp)
        'GEOSITE,facebook,🔰 Select',
        'GEOSITE,instagram,🔰 Select',
        'DOMAIN-SUFFIX,facebook.com,🔰 Select',
        'DOMAIN-SUFFIX,fbcdn.net,🔰 Select',
        'DOMAIN-SUFFIX,instagram.com,🔰 Select',
        'DOMAIN-SUFFIX,cdninstagram.com,🔰 Select',
        'DOMAIN-SUFFIX,threads.net,🔰 Select',
        'DOMAIN-SUFFIX,whatsapp.com,🔰 Select',
        'DOMAIN-SUFFIX,whatsapp.net,🔰 Select',
        // Discord 语音与社群
        'GEOSITE,discord,🔰 Select',
        'DOMAIN-SUFFIX,discord.com,🔰 Select',
        'DOMAIN-SUFFIX,discord.gg,🔰 Select',
        'DOMAIN-SUFFIX,discordapp.com,🔰 Select',
        'DOMAIN-SUFFIX,discordapp.net,🔰 Select',
        'DOMAIN-SUFFIX,discord.media,🔰 Select',
        // Reddit 国际论坛
        'GEOSITE,reddit,🔰 Select',
        'DOMAIN-SUFFIX,reddit.com,🔰 Select',
        'DOMAIN-SUFFIX,redditmedia.com,🔰 Select',
        'DOMAIN-SUFFIX,redd.it,🔰 Select',
        // Wikipedia 维基百科
        'DOMAIN-SUFFIX,wikipedia.org,🔰 Select',
        'DOMAIN-SUFFIX,wikimedia.org,🔰 Select',
        // Cloudflare 国际网络服务
        'DOMAIN-SUFFIX,cloudflare.com,🔰 Select',
        'DOMAIN-SUFFIX,cloudflare.net,🔰 Select',
        // Stripe 国际在线支付
        'DOMAIN-SUFFIX,stripe.com,🔰 Select',
        'DOMAIN-SUFFIX,stripe.network,🔰 Select',
        // 🎓 学术科研与文献检索
        'DOMAIN-SUFFIX,arxiv.org,🔰 Select',
        'DOMAIN-SUFFIX,ieee.org,🔰 Select',
        'DOMAIN-SUFFIX,sciencedirect.com,🔰 Select',
        'DOMAIN-SUFFIX,springer.com,🔰 Select',
        'DOMAIN-SUFFIX,nature.com,🔰 Select',
        'DOMAIN-SUFFIX,wiley.com,🔰 Select',
        'DOMAIN-SUFFIX,researchgate.net,🔰 Select',
        'DOMAIN-SUFFIX,semanticscholar.org,🔰 Select',
        'DOMAIN-SUFFIX,jstor.org,🔰 Select',
        // 🎯 中国大陆直连
        'DOMAIN-SUFFIX,cn,🎯 Direct',
        'DOMAIN-SUFFIX,gov.cn,🎯 Direct',
        'DOMAIN-SUFFIX,edu.cn,🎯 Direct',
        'DOMAIN-SUFFIX,12306.cn,🎯 Direct',
        // 阿里巴巴 / 淘宝 / 支付宝
        'DOMAIN-SUFFIX,taobao.com,🎯 Direct',
        'DOMAIN-SUFFIX,tmall.com,🎯 Direct',
        'DOMAIN-SUFFIX,alipay.com,🎯 Direct',
        'DOMAIN-SUFFIX,alibaba.com,🎯 Direct',
        'DOMAIN-SUFFIX,alicdn.com,🎯 Direct',
        'DOMAIN-SUFFIX,aliyun.com,🎯 Direct',
        // 腾讯
        'DOMAIN-SUFFIX,qq.com,🎯 Direct',
        'DOMAIN-SUFFIX,tencent.com,🎯 Direct',
        'DOMAIN-SUFFIX,weixin.com,🎯 Direct',
        'DOMAIN-SUFFIX,wechat.com,🎯 Direct',
        'DOMAIN-SUFFIX,qpic.cn,🎯 Direct',
        // 百度
        'DOMAIN-SUFFIX,baidu.com,🎯 Direct',
        'DOMAIN-SUFFIX,bdstatic.com,🎯 Direct',
        // 京东 / 字节跳动 / 美团 / 新浪 / 知乎
        'DOMAIN-SUFFIX,jd.com,🎯 Direct',
        'DOMAIN-SUFFIX,360buyimg.com,🎯 Direct',
        'DOMAIN-SUFFIX,bytedance.com,🎯 Direct',
        'DOMAIN-SUFFIX,toutiao.com,🎯 Direct',
        'DOMAIN-SUFFIX,meituan.com,🎯 Direct',
        'DOMAIN-SUFFIX,dianping.com,🎯 Direct',
        'DOMAIN-SUFFIX,weibo.com,🎯 Direct',
        'DOMAIN-SUFFIX,sina.com.cn,🎯 Direct',
        'DOMAIN-SUFFIX,zhihu.com,🎯 Direct',
        'DOMAIN-SUFFIX,zhimg.com,🎯 Direct',
        'DOMAIN-SUFFIX,douban.com,🎯 Direct',
        'DOMAIN-SUFFIX,xiaohongshu.com,🎯 Direct',
        'DOMAIN-SUFFIX,pinduoduo.com,🎯 Direct',
        // 国产手机生态 (小米/华为/荣耀/OPPO/vivo)
        'DOMAIN-SUFFIX,mi.com,🎯 Direct',
        'DOMAIN-SUFFIX,xiaomi.com,🎯 Direct',
        'DOMAIN-SUFFIX,huawei.com,🎯 Direct',
        'DOMAIN-SUFFIX,honor.cn,🎯 Direct',
        'DOMAIN-SUFFIX,oppo.com,🎯 Direct',
        'DOMAIN-SUFFIX,vivo.com,🎯 Direct',
        // 国内主要银行与金融机构
        'DOMAIN-SUFFIX,unionpay.com,🎯 Direct',
        'DOMAIN-SUFFIX,icbc.com.cn,🎯 Direct',
        'DOMAIN-SUFFIX,ccb.com,🎯 Direct',
        'DOMAIN-SUFFIX,boc.cn,🎯 Direct',
        'DOMAIN-SUFFIX,abchina.com,🎯 Direct',
        'DOMAIN-SUFFIX,cmbchina.com,🎯 Direct',
        'DOMAIN-SUFFIX,bankcomm.com,🎯 Direct',
        // 大陆 GeoSite / GeoIP 兜底（置于特定规则之后，避免误吞流媒体/AI）
        'GEOSITE,cn,🎯 Direct',
        'GEOIP,cn,🎯 Direct,no-resolve',
        // 🐟 Final
        'MATCH,🐟 Final',
    ],
];

$_ENV['QuantumultX_Config'] = [
    'general' => [
        'server_check_url' => 'http://cp.cloudflare.com/generate_204',
        'dns_exclusion_list' => '*.cmpassport.com, *.jegotrip.com.cn, *.icitymobile.mobi, id6.me',
    ],
    'dns' => [
        'servers' => [
            '223.5.5.5',
            '119.29.29.29',
            '1.1.1.1',
            '8.8.8.8',
        ],
    ],
    'policy' => [
        'static=🔰 Select, direct, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Rocket.png',
        'url-latency-benchmark=♻️ Auto, server-tag-regex=.*, check-interval=300, tolerance=50, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Speed.png',
        'static=♾️ AI, 🔰 Select, ♻️ Auto, 🎯 Direct, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Bot.png',
        'static=🎥 Netflix, 🔰 Select, ♻️ Auto, 🎯 Direct, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Netflix.png',
        'static=🌍 Global Media, 🔰 Select, ♻️ Auto, 🎯 Direct, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/GlobalMedia.png',
        'static=🇭🇰 HK Media, 🔰 Select, ♻️ Auto, 🎯 Direct, server-tag-regex=(?i)港|hk|hongkong, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Hong_Kong.png',
        'static=🇨🇳 China Media, 🎯 Direct, 🔰 Select, ♻️ Auto, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/DomesticMedia.png',
        'static=🎮 Gaming, 🎯 Direct, 🔰 Select, ♻️ Auto, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Game.png',
        'static=📲 Telegram, 🔰 Select, ♻️ Auto, 🎯 Direct, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Telegram.png',
        'static=Ⓜ️ Microsoft, 🎯 Direct, 🔰 Select, ♻️ Auto, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Microsoft.png',
        'static=🍎 Apple, 🎯 Direct, 🔰 Select, ♻️ Auto, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Apple.png',
        'static=⛔️ AdBlock, 🛑 Block, 🎯 Direct, 🔰 Select, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Advertising.png',
        'static=🎯 Direct, direct, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Direct.png',
        'static=🛑 Block, reject, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Reject.png',
        'static=🐟 Final, 🔰 Select, ♻️ Auto, 🎯 Direct, server-tag-regex=.*, img-url=https://raw.githubusercontent.com/Koolson/Qure/master/IconSet/Color/Final.png',
    ],
    'filter_remote' => [
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/Advertising/Advertising.list, tag=⛔️ AdBlock, force-policy=⛔️ AdBlock, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/viewer12/OverseasAI.list/main/rule/QuantumultX/OverseasAI/OverseasAI.list, tag=OverseasAI, force-policy=♾️ AI, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/Netflix/Netflix.list, tag=Netflix, force-policy=🎥 Netflix, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/GlobalMedia/GlobalMedia.list, tag=GlobalMedia, force-policy=🌍 Global Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/JOOX/JOOX.list, tag=JOOX, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/ViuTV/ViuTV.list, tag=ViuTV, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/NowE/NowE.list, tag=Now E, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/myTVSUPER/myTVSUPER.list, tag=myTV SUPER, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/TVB/TVB.list, tag=TVB, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/MOOV/MOOV.list, tag=MOOV, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/RTHK/RTHK.list, tag=RTHK, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/HBOHK/HBOHK.list, tag=HBO HK, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/BiliBiliIntl/BiliBiliIntl.list, tag=Bilibili港澳台, force-policy=🇭🇰 HK Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/ChinaMedia/ChinaMedia.list, tag=ChinaMedia, force-policy=🇨🇳 China Media, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/Game/Game.list, tag=Gaming, force-policy=🎮 Gaming, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/Telegram/Telegram.list, tag=Telegram, force-policy=📲 Telegram, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/GitHub/GitHub.list, tag=GitHub, force-policy=🔰 Select, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/Microsoft/Microsoft.list, tag=Microsoft, force-policy=Ⓜ️ Microsoft, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/Apple/Apple.list, tag=Apple, force-policy=🍎 Apple, update-interval=86400, opt-parser=false, enabled=true',
        'https://raw.githubusercontent.com/blackmatrix7/ios_rule_script/master/rule/QuantumultX/China/China.list, tag=Direct, force-policy=🎯 Direct, update-interval=86400, opt-parser=false, enabled=true',
    ],
    'server_local' => [
    ],
    'filter_local' => [
        'host-keyword, jooxweb-api, 🇭🇰 HK Media',
        'host-suffix, i-joox.com, 🇭🇰 HK Media',
        'host-suffix, joox.com, 🇭🇰 HK Media',
        'host-suffix, sanook.com, 🇭🇰 HK Media',
        'ip-cidr, 119.28.182.0/23, 🇭🇰 HK Media',
        'ip-cidr, 203.205.234.0/23, 🇭🇰 HK Media',
        'host-suffix, biliintl.com, 🇭🇰 HK Media',
        'host-suffix, bstarstatic.com, 🇭🇰 HK Media',
        'host-suffix, hmvod.com, 🇭🇰 HK Media',
        'host-suffix, hmvod.com.hk, 🇭🇰 HK Media',
        'host-suffix, 881903.com, 🇭🇰 HK Media',
        'host-suffix, metroradio.com.hk, 🇭🇰 HK Media',
        'host-keyword, github, 🔰 Select',
        'host-suffix, github.com, 🔰 Select',
        'host-suffix, github.io, 🔰 Select',
        'host-suffix, githubassets.com, 🔰 Select',
        'host-suffix, githubusercontent.com, 🔰 Select',
        'host-suffix, githubcopilot.com, 🔰 Select',
        'host-suffix, ghcr.io, 🔰 Select',
        'host-suffix, git.io, 🔰 Select',
        // 局域网与网络授时直连
        'host-suffix, local, 🎯 Direct',
        'host-suffix, lan, 🎯 Direct',
        'host-suffix, home.arpa, 🎯 Direct',
        'host-keyword, ntp, 🎯 Direct',
        'host-suffix, pool.ntp.org, 🎯 Direct',
        // 🌐 核心国际基础服务 (走 🔰 Select)
        'host-keyword, google, 🔰 Select',
        'host-keyword, 1e100, 🔰 Select',
        'host-suffix, google.com, 🔰 Select',
        'host-suffix, google.com.hk, 🔰 Select',
        'host-suffix, google.com.tw, 🔰 Select',
        'host-suffix, google.co.jp, 🔰 Select',
        'host-suffix, googleapis.com, 🔰 Select',
        'host-suffix, gstatic.com, 🔰 Select',
        'host-suffix, googleusercontent.com, 🔰 Select',
        'host-suffix, googletagmanager.com, 🔰 Select',
        'host-suffix, android.com, 🔰 Select',
        'host-suffix, gmail.com, 🔰 Select',
        'host-suffix, ggpht.com, 🔰 Select',
        'host-suffix, x.com, 🔰 Select',
        'host-suffix, twitter.com, 🔰 Select',
        'host-suffix, twimg.com, 🔰 Select',
        'host-suffix, t.co, 🔰 Select',
        'host-suffix, facebook.com, 🔰 Select',
        'host-suffix, instagram.com, 🔰 Select',
        'host-suffix, cdninstagram.com, 🔰 Select',
        'host-suffix, threads.net, 🔰 Select',
        'host-suffix, whatsapp.com, 🔰 Select',
        'host-suffix, discord.com, 🔰 Select',
        'host-suffix, discord.gg, 🔰 Select',
        'host-suffix, reddit.com, 🔰 Select',
        'host-suffix, wikipedia.org, 🔰 Select',
        'host-suffix, cloudflare.com, 🔰 Select',
        'host-suffix, stripe.com, 🔰 Select',
        'host-suffix, stripe.network, 🔰 Select',
        // 🎓 学术科研与文献检索
        'host-suffix, arxiv.org, 🔰 Select',
        'host-suffix, ieee.org, 🔰 Select',
        'host-suffix, sciencedirect.com, 🔰 Select',
        'host-suffix, springer.com, 🔰 Select',
        'host-suffix, nature.com, 🔰 Select',
        'host-suffix, wiley.com, 🔰 Select',
        'host-suffix, researchgate.net, 🔰 Select',
        'host-suffix, semanticscholar.org, 🔰 Select',
        'host-suffix, jstor.org, 🔰 Select',
        'geoip, cn, 🎯 Direct',
        'final, 🐟 Final',
    ],
    'rewrite_remote' => [
    ],
    'rewrite_local' => [
    ],
    'mitm' => [
    ],
];
