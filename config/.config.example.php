<?php

// 基本设置---------------------------------------------------------------------------------------------------------------
$_ENV['key'] = 'ChangeMe';     // Cookie加密密钥，请务必修改此key为随机字符串
$_ENV['pwdMethod'] = 'bcrypt'; // 密码加密 可选 bcrypt, argon2i, argon2id
$_ENV['salt'] = '';            // bcrypt/argon2i/argon2id 会忽略此项

$_ENV['debug'] = false;                  // debug模式开关，生产环境请保持为false
$_ENV['appName'] = 'SSPanel-UIM';         // 站点名称
$_ENV['baseUrl'] = 'https://example.com'; // 站点地址，必须以https://开头，不要以/结尾

// WebAPI
$_ENV['webAPI'] = true;                // 是否开启WebAPI功能
$_ENV['webAPIUrl'] = $_ENV['baseUrl']; // WebAPI地址，如需和站点地址相同，请不要修改
$_ENV['muKey'] = 'ChangeMe';           // WebAPI密钥，用于节点服务端与面板通信，请务必修改此key为随机字符串
$_ENV['checkNodeIp'] = true;           // 是否webapi验证节点ip

// 数据库设置--------------------------------------------------------------------------------------------------------------
// db_host|db_socket 二选一，若设置 db_socket 则 db_host 会被忽略，不用请留空
// db_host 例: localhost（可解析的主机名）, 127.0.0.1（IP 地址）
// db_socket 例：/var/run/mysqld/mysqld.sock（需使用绝对地址）
$_ENV['db_host'] = '';
$_ENV['db_socket'] = '';
$_ENV['db_database'] = 'sspanel'; // 数据库名
$_ENV['db_username'] = 'root';    // 数据库用户名
$_ENV['db_password'] = 'sspanel'; // 用户密码
$_ENV['db_port'] = '3306';        // 端口
# 读写分离相关配置
$_ENV['enable_db_rw_split'] = false; // 是否开启读写分离
$_ENV['read_db_hosts'] = [''];       // 从库地址，可配置多个
$_ENV['write_db_host'] = '';         // 主库地址
# 高级
$_ENV['db_charset'] = 'utf8mb4';
$_ENV['db_collation'] = 'utf8mb4_unicode_ci';
$_ENV['db_prefix'] = '';

// Redis设置--------------------------------------------------------------------------------------------------------------
$_ENV['redis_host'] = '127.0.0.1';    // Redis地址，使用unix domain socket时填写文件路径
$_ENV['redis_port'] = 6379;           // Redis端口，使用unix domain socket时填写-1
$_ENV['redis_db'] = 0;                // Redis数据库编号，留空则使用默认的0
$_ENV['redis_connect_timeout'] = 2.0; // Redis连接超时时间，单位秒
$_ENV['redis_read_timeout'] = 8.0;    // Redis读取超时时间，单位秒
$_ENV['redis_username'] = '';         // Redis用户名，留空则不使用用户名连接
$_ENV['redis_password'] = '';         // Redis密码，留空则无密码
$_ENV['redis_ssl'] = false;           // 是否使用SSL连接Redis，如果使用了SSL，那么Redis端口应为Redis实例的TLS端口
$_ENV['redis_ssl_context'] = [];      // 使用SSL时的上下文选项，参考 https://www.php.net/manual/zh/context.ssl.php

// 订阅设置----------------------------------------------------------------------------------------------------------------
$_ENV['Subscribe'] = true;          // 本站是否提供订阅功能
$_ENV['subUrl'] = $_ENV['baseUrl']; // 订阅地址，如需和站点名称相同，请不要修改

// 系统设置---------------------------------------------------------------------------------------------------------------
$_ENV['timeZone'] = 'Asia/Shanghai';   // 需使用 PHP 兼容的时区格式
$_ENV['theme'] = 'tabler';             // 默认主题
$_ENV['locale'] = 'zh_CN';             // 默认语言，可选值：en_US、ja_JP、zh_CN、zh_TW
$_ENV['locale_auto_detect'] = false;   // 是否自动检测浏览器语言，true时会根据Accept-Language自动选择语言
$_ENV['jump_delay'] = 1000;            // 跳转延时，单位ms
// 统计同时在线 IP 数时忽略的地址：自家中转的出口 IP（UDP / Hysteria 2 经中转不带 PROXY 头，节点只看得到中转机）
// 非全球单播地址（如本机反代的 127.0.0.1、私网地址）总是忽略
$_ENV['iplimit_ignore_ips'] = [];

// 运维与监控--------------------------------------------------------------------------------------------------------------
$_ENV['enable_telemetry'] = true;      // 是否开启系统遥测
// https://sentry.io for production debugging
$_ENV['sentry_dsn'] = '';
