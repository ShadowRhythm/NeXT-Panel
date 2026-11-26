<!doctype html>
<html lang="{$current_locale|replace:'_':'-'}"{if isset($is_rtl) && $is_rtl} dir="rtl"{/if}{if $user->is_dark_mode} data-bs-theme="dark"{/if} data-bs-navbar-theme="dark">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="referrer" content="never">
    <title>{$config['appName']}</title>
    <!-- CSS files -->
    {if isset($is_rtl) && $is_rtl}
    <link href="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/css/tabler.rtl.min.css" rel="stylesheet"/>
    {else}
    <link href="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    {/if}
    <link href="//{$config['jsdelivr_url']}/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    <!-- JS files -->
    <script src="/assets/js/fuck.min.js"></script>
    <script src="//{$config['jsdelivr_url']}/npm/qrcode_js@latest/qrcode.min.js"></script>
    <script src="//{$config['jsdelivr_url']}/npm/clipboard@latest/dist/clipboard.min.js"></script>
    <script src="//{$config['jsdelivr_url']}/npm/htmx.org@latest/dist/htmx.min.js"></script>
    <style>
        .home-subtitle {
            font-size: 14px;
        }

        .home-title {
            font-size: 36px;
        }
    </style>
</head>

{if $user->is_dark_mode}
<body data-bs-theme="dark">
{else}
<body>
{/if}
<div class="page">
    <header class="navbar navbar-expand-md navbar-overlap d-print-none" data-bs-theme="dark">
        <div class="container-xl" style="background-image: none;">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                <img src="/images/uim-logo-round_48x48.png" height="32" alt="SSPanel-UIM Logo"
                     class="navbar-brand-image" style="filter: none;">
            </h1>
            <div class="navbar-nav flex-row order-md-last">
                <div class="nav-item dropdown me-2">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                        <i class="ti ti-language icon"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow">
                        <a class="dropdown-item {if $current_locale == 'zh_CN'}active{/if}" href="/locale/zh_CN">🇨🇳 简体中文</a>
                        <a class="dropdown-item {if $current_locale == 'zh_TW'}active{/if}" href="/locale/zh_TW">🇭🇰 繁體中文</a>
                        <a class="dropdown-item {if $current_locale == 'en_US'}active{/if}" href="/locale/en_US">🇺🇸 English</a>
                        <a class="dropdown-item {if $current_locale == 'ja_JP'}active{/if}" href="/locale/ja_JP">🇯🇵 日本語</a>
                        <a class="dropdown-item {if $current_locale == 'ko_KR'}active{/if}" href="/locale/ko_KR">🇰🇷 한국어</a>
                        <a class="dropdown-item {if $current_locale == 'ru_RU'}active{/if}" href="/locale/ru_RU">🇷🇺 Русский</a>
                        <a class="dropdown-item {if $current_locale == 'fr_FR'}active{/if}" href="/locale/fr_FR">🇫🇷 Français</a>
                        <a class="dropdown-item {if $current_locale == 'de_DE'}active{/if}" href="/locale/de_DE">🇩🇪 Deutsch</a>
                        <a class="dropdown-item {if $current_locale == 'es_ES'}active{/if}" href="/locale/es_ES">🇪🇸 Español</a>
                        <a class="dropdown-item {if $current_locale == 'pt_BR'}active{/if}" href="/locale/pt_BR">🇧🇷 Português</a>
                        <a class="dropdown-item {if $current_locale == 'vi_VN'}active{/if}" href="/locale/vi_VN">🇻🇳 Tiếng Việt</a>
                        <a class="dropdown-item {if $current_locale == 'id_ID'}active{/if}" href="/locale/id_ID">🇮🇩 Bahasa Indonesia</a>
                        <a class="dropdown-item {if $current_locale == 'tr_TR'}active{/if}" href="/locale/tr_TR">🇹🇷 Türkçe</a>
                        <a class="dropdown-item {if $current_locale == 'ar_SA'}active{/if}" href="/locale/ar_SA">🇸🇦 العربية</a>
                        <a class="dropdown-item {if $current_locale == 'fa_IR'}active{/if}" href="/locale/fa_IR">🇮🇷 فارسی</a>
                    </div>
                </div>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                       aria-label="Open user menu">
                            <span class="avatar avatar-sm"
                                  style="background-image: url({$user->dice_bear})"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{$user->email|escape}</div>
                            <div class="mt-1 small text-secondary">{$user->user_name|escape}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        {if $user->is_dark_mode}
                            <a class="dropdown-item" hx-post="/user/switch_theme_mode" hx-swap="none">
                                {trans key='common.light_mode'}
                            </a>
                        {else}
                            <a class="dropdown-item" hx-post="/user/switch_theme_mode" hx-swap="none">
                                {trans key='common.dark_mode'}
                            </a>
                        {/if}
                        <a href="/user/logout" class="dropdown-item">{trans key='common.logout'}</a>
                    </div>
                </div>
            </div>
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="/user">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        {trans key='common.home'}
                                    </span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-user icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        {trans key='common.my_account'}
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <a class="dropdown-item" href="/user/profile">
                                            <i class="ti ti-info-square"></i>&nbsp;
                                            {trans key='common.profile'}
                                        </a>
                                        <a class="dropdown-item" href="/user/edit">
                                            <i class="ti ti-edit"></i>&nbsp;
                                            {trans key='common.edit_profile'}
                                        </a>
                                        <a class="dropdown-item" href="/user/invite">
                                            <i class="ti ti-friends"></i>&nbsp;
                                            {trans key='common.invite'}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-brand-telegram icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        {trans key='common.usage'}
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/user/server">
                                    <i class="ti ti-server"></i>&nbsp;
                                    {trans key='common.servers'}
                                </a>
                                <a class="dropdown-item" href="/user/rate">
                                    <i class="ti ti-chart-bar"></i>&nbsp;
                                    {trans key='common.traffic_rate'}
                                </a>
                                <a class="dropdown-item" href="/user/media">
                                    <i class="ti ti-key"></i>&nbsp;
                                    {trans key='common.media_unlock'}
                                </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-dots-circle-horizontal icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        {trans key='common.support'}
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/user/announcement">
                                    <i class="ti ti-speakerphone"></i>&nbsp;
                                    {trans key='common.announcement'}
                                </a>
                                {if $public_setting['enable_ticket']}
                                    <a class="dropdown-item" href="/user/ticket">
                                        <i class="ti ti-ticket"></i>&nbsp;
                                        {trans key='common.ticket'}
                                    </a>
                                {/if}
                                {if $public_setting['display_docs'] &&
                                (! $public_setting['display_docs_only_for_paid_user'] || $user->class !== 0)}
                                    <a class="dropdown-item" href="/user/docs">
                                        <i class="ti ti-notes"></i>&nbsp;
                                        {trans key='common.docs'}
                                    </a>
                                {/if}
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-shield-check icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        {trans key='common.audit'}
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/user/detect">
                                    <i class="ti ti-barrier-block"></i>&nbsp;
                                    {trans key='common.rules'}
                                </a>
                                {if $public_setting['display_detect_log']}
                                    <a class="dropdown-item" href="/user/detect/log">
                                        <i class="ti ti-notes"></i>&nbsp;
                                        {trans key='common.logs'}
                                    </a>
                                {/if}
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-layout" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-building-store icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        {trans key='common.shop'}
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <a class="dropdown-item" href="/user/product">
                                            <i class="ti ti-list"></i>&nbsp;
                                            {trans key='common.product'}
                                        </a>
                                        <a class="dropdown-item" href="/user/order">
                                            <i class="ti ti-file-invoice"></i>&nbsp;
                                            {trans key='common.order'}
                                        </a>
                                        <a class="dropdown-item" href="/user/invoice">
                                            <i class="ti ti-file-dollar"></i>&nbsp;
                                            {trans key='common.invoice'}
                                        </a>
                                        <a class="dropdown-item" href="/user/money">
                                            <i class="ti ti-home-dollar"></i>&nbsp;
                                            {trans key='common.money'}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        {if $user->is_admin}
                            <li class="nav-item">
                                <a class="nav-link" href="/admin">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings icon"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        {trans key='common.site_admin'}
                                    </span>
                                </a>
                            </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>
    </header>
