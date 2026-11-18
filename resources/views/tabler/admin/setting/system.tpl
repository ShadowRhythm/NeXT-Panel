{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">系统设置</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">设置站点的基础系统配置</span>
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
                                    <a href="#rate_limit" class="nav-link active" data-bs-toggle="tab">访问限流</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#geoip" class="nav-link" data-bs-toggle="tab">GeoIP</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#cdn_storage" class="nav-link" data-bs-toggle="tab">CDN 与存储</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="rate_limit">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">开启API访问限流</label>
                                            <div class="col">
                                                <select id="enable_rate_limit" class="col form-select"
                                                        value="{$settings['enable_rate_limit']}">
                                                    <option value="0"
                                                            {if ! $settings['enable_rate_limit']}selected{/if}>关闭
                                                    </option>
                                                    <option value="1"
                                                            {if $settings['enable_rate_limit']}selected{/if}>开启
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">订阅-每IP每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_sub_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_sub_ip']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">订阅-每用户每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_sub" type="text" class="form-control"
                                                       value="{$settings['rate_limit_sub']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">WebAPI-每IP每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_webapi_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_webapi_ip']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">WebAPI全局每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_webapi" type="text" class="form-control"
                                                       value="{$settings['rate_limit_webapi']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">用户API-每IP每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_user_api_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_user_api_ip']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">用户API-每用户每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_user_api" type="text" class="form-control"
                                                       value="{$settings['rate_limit_user_api']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">管理API-每IP每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_admin_api_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_admin_api_ip']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">管理API-每管理员每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_admin_api" type="text" class="form-control"
                                                       value="{$settings['rate_limit_admin_api']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">节点API-每IP每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_node_api_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_node_api_ip']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">节点API-每节点每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_node_api" type="text" class="form-control"
                                                       value="{$settings['rate_limit_node_api']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">登录-每IP每分钟请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_login_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_login_ip']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">注册-每IP每小时请求限制</label>
                                            <div class="col">
                                                <input id="rate_limit_register_ip" type="text" class="form-control"
                                                       value="{$settings['rate_limit_register_ip']}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="geoip">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Maxmind Account ID</label>
                                            <div class="col">
                                                <input id="maxmind_account_id" type="text" class="form-control"
                                                       value="{$settings['maxmind_account_id']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Maxmind License Key</label>
                                            <div class="col">
                                                <input id="maxmind_license_key" type="text" class="form-control"
                                                       value="{$settings['maxmind_license_key']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">GeoIP显示语言（en/zh-CN）</label>
                                            <div class="col">
                                                <input id="geoip_locale" type="text" class="form-control"
                                                       value="{$settings['geoip_locale']}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="cdn_storage">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">jsDelivr CDN节点（可选 cdn.jsdelivr.net/fastly.jsdelivr.net/testingcf.jsdelivr.net）</label>
                                            <div class="col">
                                                <input id="jsdelivr_url" type="text" class="form-control"
                                                       value="{$settings['jsdelivr_url']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">GitHub Access Token</label>
                                            <div class="col">
                                                <input id="github_access_token" type="text" class="form-control"
                                                       value="{$settings['github_access_token']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">使用Cloudflare R2存储客户端</label>
                                            <div class="col">
                                                <select id="enable_r2_client_download" class="col form-select"
                                                        value="{$settings['enable_r2_client_download']}">
                                                    <option value="0"
                                                            {if ! $settings['enable_r2_client_download']}selected{/if}>关闭
                                                    </option>
                                                    <option value="1"
                                                            {if $settings['enable_r2_client_download']}selected{/if}>开启
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">R2 Bucket名称</label>
                                            <div class="col">
                                                <input id="r2_bucket_name" type="text" class="form-control"
                                                       value="{$settings['r2_bucket_name']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">R2 Account ID</label>
                                            <div class="col">
                                                <input id="r2_account_id" type="text" class="form-control"
                                                       value="{$settings['r2_account_id']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">R2 Access Key ID</label>
                                            <div class="col">
                                                <input id="r2_access_key_id" type="text" class="form-control"
                                                       value="{$settings['r2_access_key_id']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">R2 Access Key Secret</label>
                                            <div class="col">
                                                <input id="r2_access_key_secret" type="text" class="form-control"
                                                       value="{$settings['r2_access_key_secret']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">R2下载超时(秒)</label>
                                            <div class="col">
                                                <input id="r2_client_download_timeout" type="text" class="form-control"
                                                       value="{$settings['r2_client_download_timeout']}">
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
                    url: '/admin/setting/system',
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
