{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">订阅设置</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">设置站点的订阅系统</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a id="save-setting" href="#" class="btn btn-primary">
                            <i class="icon ti ti-device-floppy"></i>
                            保存
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                <li class="nav-item">
                                    <a href="#sub" class="nav-link active" data-bs-toggle="tab">订阅设置</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#sub_basic" class="nav-link" data-bs-toggle="tab">基础设置</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#dns_config" class="nav-link" data-bs-toggle="tab">DNS 配置</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="sub">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">
                                                Enable Shadowsocks Subscription
                                            </label>
                                            <div class="col">
                                                <select id="enable_ss_sub" class="col form-select"
                                                        value="{$settings['enable_ss_sub']}">
                                                    <option value="0" {if ! $settings['enable_ss_sub']}selected{/if}>
                                                        False
                                                    </option>
                                                    <option value="1" {if $settings['enable_ss_sub']}selected{/if}>
                                                        True
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">
                                                Enable Vmess Subscription
                                            </label>
                                            <div class="col">
                                                <select id="enable_v2_sub" class="col form-select"
                                                        value="{$settings['enable_v2_sub']}">
                                                    <option value="0" {if ! $settings['enable_v2_sub']}selected{/if}>
                                                        False
                                                    </option>
                                                    <option value="1" {if $settings['enable_v2_sub']}selected{/if}>
                                                        True
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">
                                                Enable Trojan Subscription
                                            </label>
                                            <div class="col">
                                                <select id="enable_trojan_sub" class="col form-select"
                                                        value="{$settings['enable_trojan_sub']}">
                                                    <option value="0" {if ! $settings['enable_trojan_sub']}selected{/if}>
                                                        False
                                                    </option>
                                                    <option value="1" {if $settings['enable_trojan_sub']}selected{/if}>
                                                        True
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">
                                                修改账户登录密码时重置订阅地址
                                            </label>
                                            <div class="col">
                                                <select id="enable_forced_replacement" class="col form-select"
                                                        value="{$settings['enable_forced_replacement']}">
                                                    <option value="0"
                                                            {if ! $settings['enable_forced_replacement']}selected{/if}>
                                                        False
                                                    </option>
                                                    <option value="1"
                                                            {if $settings['enable_forced_replacement']}selected{/if}>
                                                        True
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="sub_basic">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">提供订阅功能</label>
                                            <div class="col">
                                                <select id="subscribe_enable" class="col form-select"
                                                        value="{$settings['subscribe_enable']}">
                                                    <option value="0" {if ! $settings['subscribe_enable']}selected{/if}>关闭
                                                    </option>
                                                    <option value="1" {if $settings['subscribe_enable']}selected{/if}>开启
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">订阅地址（留空同站点地址）</label>
                                            <div class="col">
                                                <input id="sub_url" type="text" class="form-control"
                                                       value="{$settings['sub_url']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">订阅Token长度</label>
                                            <div class="col">
                                                <input id="sub_token_len" type="text" class="form-control"
                                                       value="{$settings['sub_token_len']}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="dns_config">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DNS预设方案（可选 google/cloudflare/opendns/alidns/mine_853/mine_443）</label>
                                            <div class="col">
                                                <input id="dns_select" type="text" class="form-control"
                                                       value="{$settings['dns_select']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoT DNS服务器地址</label>
                                            <div class="col">
                                                <input id="dns_server_853" type="text" class="form-control"
                                                       value="{$settings['dns_server_853']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoT DNS端口</label>
                                            <div class="col">
                                                <input id="dns_server_port_853" type="text" class="form-control"
                                                       value="{$settings['dns_server_port_853']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoT协议类型（tls/quic）</label>
                                            <div class="col">
                                                <input id="dns_type_853" type="text" class="form-control"
                                                       value="{$settings['dns_type_853']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoH DNS服务器地址</label>
                                            <div class="col">
                                                <input id="dns_server_443" type="text" class="form-control"
                                                       value="{$settings['dns_server_443']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoH DNS端口</label>
                                            <div class="col">
                                                <input id="dns_server_port_443" type="text" class="form-control"
                                                       value="{$settings['dns_server_port_443']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoH路径</label>
                                            <div class="col">
                                                <input id="dns_path_443" type="text" class="form-control"
                                                       value="{$settings['dns_path_443']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">DoH协议类型（https/h3）</label>
                                            <div class="col">
                                                <input id="dns_type_443" type="text" class="form-control"
                                                       value="{$settings['dns_type_443']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Clash Meta TCP并发</label>
                                            <div class="col">
                                                <select id="tcp_concurrent" class="col form-select"
                                                        value="{$settings['tcp_concurrent']}">
                                                    <option value="0" {if ! $settings['tcp_concurrent']}selected{/if}>关闭
                                                    </option>
                                                    <option value="1" {if $settings['tcp_concurrent']}selected{/if}>开启
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $("#save-setting").click(function () {
                $.ajax({
                    url: '/admin/setting/sub',
                    type: 'POST',
                    dataType: "json",
                    data: {
                        {foreach $update_field as $key}
                        {$key}: $('#{$key}').val(),
                        {/foreach}
                    },
                    success: function (data) {
                        if (data.ret === 1) {
                            $('#success-message').text(data.msg);
                            successDialog.show();
                        } else {
                            $('#fail-message').text(data.msg);
                            failDialog.show();
                        }
                    }
                })
            });
        </script>

        {include file='admin/footer.tpl'}
