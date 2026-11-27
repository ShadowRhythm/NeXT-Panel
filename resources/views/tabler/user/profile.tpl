{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.profile_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.profile_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">{trans key='user.account_email'}</div>
                            </div>
                            <div class="h1 mb-3">{$user->email|escape}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">{trans key='user.account_username'}</div>
                            </div>
                            <div class="h1 mb-3">{$user->user_name|escape}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">{trans key='user.registration_time'}</div>
                            </div>
                            <div class="h1 mb-3">{$user->reg_date}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">{trans key='user.total_traffic_used'}</div>
                            </div>
                            <div class="h1 mb-3">{$user->totalTraffic()}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck my-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <h3 class="card-title mb-0">{trans key='user.device_quota_title'}</h3>
                                        {if $is_over_ip_limit}
                                            <span class="badge bg-red text-red-fg">{trans key='user.ip_limit_exceeded'}</span>
                                        {else}
                                            <span class="badge bg-green-lt">{trans key='user.ip_limit_normal'}</span>
                                        {/if}
                                    </div>
                                    <div class="text-secondary small mb-2">
                                        {if $ip_limit > 0}
                                            {assign var='limit_label' value=$ip_limit}
                                        {else}
                                            {capture assign='limit_label'}{trans key='user.unlimited'}{/capture}
                                        {/if}
                                        {trans key='user.online_ip_quota_desc' active=$active_ip_count limit=$limit_label}
                                    </div>
                                    {if $ip_limit > 0}
                                        <div class="progress progress-sm" style="max-width: 360px;">
                                            <div class="progress-bar {if $is_over_ip_limit}bg-danger{elseif $ip_quota_percent >= 80}bg-warning{else}bg-primary{/if}"
                                                 style="width: {$ip_quota_percent}%"></div>
                                        </div>
                                    {/if}
                                </div>
                                <div>
                                    <button type="button"
                                            class="btn btn-outline-danger"
                                            hx-post="/user/profile/device/kick_all_proxy"
                                            hx-swap="none"
                                            hx-confirm="{trans key='user.kick_all_proxy_confirm'}">
                                        <i class="ti ti-shield-off icon me-1"></i>
                                        {trans key='user.kick_all_proxy_btn'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck my-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ti ti-plug-connected icon me-1"></i>
                                {trans key='user.current_online_ip'} ({$active_ip_count})
                            </h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter text-nowrap card-table">
                                <thead>
                                    <tr>
                                        <th>{trans key='user.device_label'}</th>
                                        <th>{trans key='user.ip_address'}</th>
                                        <th>{trans key='user.ip_location'}</th>
                                        <th>{trans key='user.node_name'}</th>
                                        <th>{trans key='user.matched_proxy_client'}</th>
                                        <th>{trans key='user.last_online_time'}</th>
                                        <th class="w-1">{trans key='common.action'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $ips as $ip}
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="ti {$ip->client_icon|escape} text-secondary"></i>
                                                    {if $ip->alias !== ''}
                                                        <span class="fw-medium">{$ip->alias|escape}</span>
                                                    {else}
                                                        <span class="text-secondary">{trans key='user.unnamed_device'}</span>
                                                    {/if}
                                                    <button type="button"
                                                            class="btn btn-ghost-secondary btn-icon btn-sm"
                                                            title="{trans key='user.edit_device_alias'}"
                                                            onclick="openAliasModal('{$ip->ip|escape:'javascript'}', '{$ip->alias|escape:'javascript'}')">
                                                        <i class="ti ti-pencil icon"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td><code>{$ip->ip|escape}</code></td>
                                            <td>{$ip->location|escape}</td>
                                            <td>{$ip->node_name|escape}</td>
                                            <td>
                                                {if $ip->matched_client !== ''}
                                                    <span class="badge bg-azure-lt">{$ip->matched_client|escape}</span>
                                                {else}
                                                    <span class="text-secondary small">-</span>
                                                {/if}
                                            </td>
                                            <td>{$ip->last_time|escape}</td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        hx-delete="/user/profile/device/online/{$ip->id}"
                                                        hx-swap="none"
                                                        hx-confirm="{trans key='user.confirm_remove_online_ip'}">
                                                    {trans key='user.remove_device_record'}
                                                </button>
                                            </td>
                                        </tr>
                                    {foreachelse}
                                        <tr>
                                            <td colspan="7" class="text-center text-secondary py-4">
                                                {trans key='user.no_online_devices'}
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck my-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title">
                                <i class="ti ti-browser icon me-1"></i>
                                {trans key='user.web_sessions_title'}
                            </h3>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    hx-post="/user/profile/session/kick_others"
                                    hx-swap="none"
                                    hx-confirm="{trans key='user.kick_other_sessions_confirm'}">
                                {trans key='user.kick_other_sessions_btn'}
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter text-nowrap card-table">
                                <thead>
                                    <tr>
                                        <th>{trans key='user.device_label'}</th>
                                        <th>{trans key='user.ip_address'}</th>
                                        <th>{trans key='user.ip_location'}</th>
                                        <th>{trans key='user.last_active_time'}</th>
                                        <th>{trans key='user.expire_time'}</th>
                                        <th class="w-1">{trans key='common.action'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $web_sessions as $session}
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="ti {$session->icon|escape} text-secondary"></i>
                                                    <span class="fw-medium">{$session->device_name|escape}</span>
                                                    {if $session->is_current}
                                                        <span class="badge bg-green-lt">{trans key='user.current_web_session'}</span>
                                                    {/if}
                                                </div>
                                            </td>
                                            <td><code>{$session->ip|escape}</code></td>
                                            <td>{$session->location|escape}</td>
                                            <td>{$session->last_active|escape}</td>
                                            <td>{$session->expires_at|escape}</td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        hx-delete="/user/profile/session/{$session->id}"
                                                        hx-swap="none"
                                                        hx-confirm="{trans key='user.confirm_revoke_session'}">
                                                    {trans key='user.revoke_session_btn'}
                                                </button>
                                            </td>
                                        </tr>
                                    {foreachelse}
                                        <tr>
                                            <td colspan="6" class="text-center text-secondary py-4">
                                                {trans key='user.no_web_sessions'}
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {if $public_setting['subscribe_log']}
            <div class="row row-deck my-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h3 class="card-title">
                                <i class="ti ti-apps icon me-1"></i>
                                {trans key='user.recognized_sub_clients'}
                            </h3>
                            {if count($subs) > 0}
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="collapse" data-bs-target="#raw-sub-logs">
                                    {trans key='user.toggle_raw_sub_logs'}
                                </button>
                            {/if}
                        </div>
                        <div class="card-body">
                            {if count($sub_clients) > 0}
                                <div class="row row-cards">
                                    {foreach $sub_clients as $client}
                                        <div class="col-md-4 col-sm-6">
                                            <div class="border rounded p-3 h-100">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="fw-bold">
                                                        <i class="ti {$client->icon|escape} icon me-1"></i>
                                                        {$client->client|escape}
                                                        {if $client->version !== ''}
                                                            <span class="text-secondary fw-normal small">v{$client->version|escape}</span>
                                                        {/if}
                                                    </div>
                                                    <span class="badge bg-secondary-lt">{$client->type|escape}</span>
                                                </div>
                                                <div class="text-secondary small">
                                                    {$client->os|escape} · <code>{$client->ip|escape}</code> ({$client->location|escape})
                                                </div>
                                                <div class="text-secondary small mt-1">
                                                    {trans key='user.last_sub_pull'}: {$client->last_time|escape}
                                                </div>
                                            </div>
                                        </div>
                                    {/foreach}
                                </div>
                            {else}
                                <div class="text-center text-secondary py-3">{trans key='user.no_sub_clients'}</div>
                            {/if}
                        </div>
                        {if count($subs) > 0}
                            <div class="collapse" id="raw-sub-logs">
                                <div class="table-responsive border-top">
                                    <table class="table table-vcenter text-nowrap card-table">
                                        <thead>
                                            <tr>
                                                <th>{trans key='common.type'}</th>
                                                <th>{trans key='user.user_agent'}</th>
                                                <th>{trans key='user.ip_address'}</th>
                                                <th>{trans key='user.ip_location'}</th>
                                                <th>{trans key='common.time'}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach $subs as $sub}
                                            <tr>
                                                <td>{$sub->type|escape}</td>
                                                <td>{$sub->request_user_agent|escape}</td>
                                                <td>{$sub->request_ip|escape}</td>
                                                <td>{$sub->location|escape}</td>
                                                <td>{$sub->request_time|escape}</td>
                                            </tr>
                                            {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
            {/if}

            {if $public_setting['login_log']}
            <div class="row row-deck my-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{trans key='user.recent_login_logs'}</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter text-nowrap card-table">
                                <thead>
                                    <tr>
                                        <th>{trans key='user.ip_address'}</th>
                                        <th>{trans key='user.ip_location'}</th>
                                        <th>{trans key='common.time'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $logins as $login}
                                    <tr>
                                        <td><code>{$login->ip|escape}</code></td>
                                        <td>{$login->location|escape}</td>
                                        <td>{$login->datetime|escape}</td>
                                    </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            {/if}
        </div>
    </div>

    <div class="modal modal-blur fade" id="device-alias-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{trans key='user.edit_device_alias'}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 text-secondary small">
                        IP: <code id="alias-modal-ip"></code>
                    </div>
                    <input type="text" id="alias-modal-input" class="form-control" maxlength="64"
                           placeholder="{trans key='user.device_alias_placeholder'}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                        {trans key='common.cancel'}
                    </button>
                    <button type="button" class="btn btn-primary ms-auto"
                            hx-post="/user/profile/device/alias"
                            hx-swap="none"
                            hx-vals='js:{
                                ip: document.getElementById("alias-modal-ip").textContent,
                                alias: document.getElementById("alias-modal-input").value
                            }'>
                        {trans key='common.save'}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAliasModal(ip, currentAlias) {
            const modalEl = document.getElementById('device-alias-modal');
            document.getElementById('alias-modal-ip').textContent = ip;
            document.getElementById('alias-modal-input').value = currentAlias || '';
            const ModalCtor = (typeof tabler !== 'undefined' && (tabler.Modal || (tabler.bootstrap && tabler.bootstrap.Modal)))
                || (typeof bootstrap !== 'undefined' && bootstrap.Modal);
            if (ModalCtor && modalEl) {
                const inst = ModalCtor.getOrCreateInstance ? ModalCtor.getOrCreateInstance(modalEl) : new ModalCtor(modalEl);
                inst.show();
            }
        }
    </script>

{include file='user/footer.tpl'}
