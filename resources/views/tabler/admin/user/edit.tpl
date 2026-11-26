{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">用户 #{$edit_user->id}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">用户编辑</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        <a id="save_changes" href="#" class="btn btn-primary">
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
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">账户信息</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">邮箱</label>
                                <div class="col">
                                    <input id="email" type="email" class="form-control" value="{$edit_user->email|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">用户名</label>
                                <div class="col">
                                    <input id="user_name" type="text" class="form-control"
                                           value="{$edit_user->user_name|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">账户密码</label>
                                <div class="col">
                                    <input id="pass" type="text" class="form-control"
                                           placeholder="若需为此用户重置密码, 填写此栏">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">账户余额</label>
                                <div class="col">
                                    <input id="money" type="number" step="1" class="form-control"
                                           value="{$edit_user->money|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">邀请人</label>
                                <div class="col">
                                    <input id="ref_by" type="text" class="form-control" value="{$edit_user->ref_by|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">SS端口</label>
                                <div class="col">
                                    <input id="port" type="text" class="form-control" value="{$edit_user->port|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">SS加密方式</label>
                                <div class="col">
                                    <select id="method" class="col form-select" value="{$edit_user->method|escape}">
                                        {foreach $ss_methods as $method}
                                            <option value="{$method}" {if $edit_user->method === $method}selected{/if}>
                                                {$method}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">注册IP</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->reg_ip|escape}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">注册日期</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->reg_date|escape}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">最后使用时间</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->last_use_time|escape}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">最后签到时间</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->last_check_in_time|escape}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">最后登录时间</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->last_login_time|escape}" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">使用限制</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">流量限制</label>
                                <div class="col">
                                    <input id="transfer_enable" type="text" class="form-control"
                                           value="{$edit_user->enableTraffic()|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">当期用量</label>
                                <div class="col">
                                    <input type="text" class="form-control"
                                           value="{$edit_user->usedTraffic()|escape}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">累计用量</label>
                                <div class="col">
                                    <input type="text" class="form-control"
                                           value="{$edit_user->totalTraffic()|escape}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">节点群组</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control"
                                           value="{$edit_user->node_group|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">账户等级</label>
                                <div class="col">
                                    <input id="class" type="text" class="form-control"
                                           value="{$edit_user->class|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">等级过期时间</label>
                                <div class="col">
                                    <input id="class_expire" type="text" class="form-control"
                                           value="{$edit_user->class_expire|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">免费用户流量重置日</label>
                                <div class="col">
                                    <input id="auto_reset_day" type="text" class="form-control"
                                           value="{$edit_user->auto_reset_day|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">重置的免费流量(GB)</label>
                                <div class="col">
                                    <input id="auto_reset_bandwidth" type="text" class="form-control"
                                           value="{$edit_user->auto_reset_bandwidth|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">速度限制 (Mbps)</label>
                                <div class="col">
                                    <input id="node_speedlimit" type="text" class="form-control"
                                           value="{$edit_user->node_speedlimit|escape}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">同時连接 IP 限制</label>
                                <div class="col">
                                    <input id="node_iplimit" type="text" class="form-control"
                                           value="{$edit_user->node_iplimit|escape}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">其他设置</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">显示语言</label>
                                <div class="col">
                                    <select id="locale" class="col form-select">
                                        {foreach $locales as $locale}
                                        <option value="{$locale|escape}" {if $edit_user->locale === $locale}selected{/if}>
                                            {if $locale === 'zh_CN'}🇨🇳 简体中文 (zh_CN)
                                            {elseif $locale === 'zh_TW'}🇭🇰 繁體中文 (zh_TW)
                                            {elseif $locale === 'en_US'}🇺🇸 English (en_US)
                                            {elseif $locale === 'ja_JP'}🇯🇵 日本語 (ja_JP)
                                            {elseif $locale === 'ko_KR'}🇰🇷 한국어 (ko_KR)
                                            {elseif $locale === 'ru_RU'}🇷🇺 Русский (ru_RU)
                                            {elseif $locale === 'fr_FR'}🇫🇷 Français (fr_FR)
                                            {elseif $locale === 'de_DE'}🇩🇪 Deutsch (de_DE)
                                            {elseif $locale === 'es_ES'}🇪🇸 Español (es_ES)
                                            {elseif $locale === 'pt_BR'}🇧🇷 Português (pt_BR)
                                            {elseif $locale === 'vi_VN'}🇻🇳 Tiếng Việt (vi_VN)
                                            {elseif $locale === 'id_ID'}🇮🇩 Bahasa Indonesia (id_ID)
                                            {elseif $locale === 'tr_TR'}🇹🇷 Türkçe (tr_TR)
                                            {elseif $locale === 'ar_SA'}🇸🇦 العربية (ar_SA)
                                            {elseif $locale === 'fa_IR'}🇮🇷 فارسی (fa_IR)
                                            {else}{$locale|escape}
                                            {/if}
                                        </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">管理员</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="is_admin" class="form-check-input" type="checkbox"
                                               {if $edit_user->is_admin}checked="" {/if}>
                                    </label>
                                </span>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">两步认证</span>
                                <span class="col-auto d-flex align-items-center gap-2">
                                    {if $has_mfa}
                                        <span class="badge bg-green text-green-fg text-white">已启用</span>
                                        <button type="button" class="btn btn-sm btn-danger" id="revoke-mfa">撤销所有 MFA</button>
                                    {else}
                                        <span class="badge bg-secondary text-secondary-fg text-white">未启用</span>
                                    {/if}
                                </span>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">账户异常状态（Shadow Banned）</span>
                                <span class="col-auto form-check-single form-switch">
                                    <input id="is_shadow_banned" class="form-check-input" type="checkbox"
                                           {if $edit_user->is_shadow_banned}checked=""{/if}>
                                </span>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">封禁用户</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="is_banned" class="form-check-input" type="checkbox"
                                               {if $edit_user->is_banned}checked=""{/if}>
                                    </label>
                                </span>
                            </div>
                            <div class="form-group mb-3 col-12">
                                <span class="form-label col-12 col-form-label">手动封禁理由</span>
                                <span class="col-auto">
                                    <textarea id="banned_reason" class="form-control"
                                              value="{$edit_user->banned_reason|escape}">{$edit_user->banned_reason|escape}</textarea>
                                </span>
                            </div>
                            <div class="form-group mb-3 col-12">
                                <label class="form-label col-12 col-form-label">账户备注</label>
                                <div class="col">
                                    <textarea id="remark" class="form-control" value="{$edit_user->remark|escape}"
                                              placeholder="仅管理员可见">{$edit_user->remark|escape}</textarea>
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
    $("#save_changes").click(function (e) {
        e.preventDefault();
        let $btn = $(this);
        if ($btn.hasClass('disabled') || $btn.prop('disabled')) return;
        $btn.addClass('disabled');

        $.ajax({
            url: '/admin/user/{$edit_user->id}',
            type: 'PUT',
            dataType: "json",
            data: {
                {foreach $update_field as $key}
                {$key}: $('#{$key}').val(),
                {/foreach}
                is_admin: $("#is_admin").is(":checked"),
                is_shadow_banned: $("#is_shadow_banned").is(":checked"),
                is_banned: $("#is_banned").is(":checked"),
            },
            success: function (data) {
                $btn.removeClass('disabled');
                if (data.ret === 1) {
                    $('#success-message').text(data.msg);
                    if (typeof successDialog !== 'undefined' && successDialog) {
                        successDialog.show();
                    } else {
                        alert(data.msg);
                    }
                    window.setTimeout(function () {
                        location.href = document.referrer || '/admin/user';
                    }, {$config['jump_delay']});
                } else {
                    $('#fail-message').text(data.msg);
                    if (typeof failDialog !== 'undefined' && failDialog) {
                        failDialog.show();
                    } else {
                        alert(data.msg);
                    }
                }
            },
            error: function (jqXHR) {
                $btn.removeClass('disabled');
                let msg = '保存失败，发生网络或服务器错误';
                if (jqXHR.responseJSON && jqXHR.responseJSON.msg) {
                    msg = jqXHR.responseJSON.msg;
                } else if (jqXHR.status) {
                    msg += ' (HTTP ' + jqXHR.status + ' ' + (jqXHR.statusText || '') + ')';
                }
                $('#fail-message').text(msg);
                if (typeof failDialog !== 'undefined' && failDialog) {
                    failDialog.show();
                } else {
                    alert(msg);
                }
            }
        });
    });

    $('#revoke-mfa').click(function () {
        if (!confirm('确定撤销该用户的所有 MFA 设备？')) return;
        $.ajax({
            url: '/admin/user/{$edit_user->id}/revoke_mfa',
            type: 'POST',
            dataType: 'json',
            success: function (data) {
                if (data.ret === 1) {
                    alert(data.msg);
                    location.reload();
                } else {
                    alert(data.msg);
                }
            }
        });
    });
</script>

{include file='admin/footer.tpl'}
