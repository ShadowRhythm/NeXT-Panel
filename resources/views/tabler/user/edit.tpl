{include file='user/header.tpl'}

<script src="//{$config['jsdelivr_url']}/npm/jquery/dist/jquery.min.js"></script>
<script src="https://unpkg.com/@simplewebauthn/browser/dist/bundle/index.umd.min.js"></script>

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.edit_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.edit_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <ul class="nav nav-tabs nav-fill" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#personal_information" class="nav-link active" data-bs-toggle="tab"
                                   aria-selected="true" role="tab">
                                    <i class="ti ti-chart-candle icon"></i>&nbsp;
                                    {trans key='user.tab_profile'}
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#login_security" class="nav-link" data-bs-toggle="tab" aria-selected="true"
                                   role="tab">
                                    <i class="ti ti-shield-lock icon"></i>&nbsp;
                                    {trans key='user.tab_security'}
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#use_safety" class="nav-link" data-bs-toggle="tab" aria-selected="false"
                                   tabindex="-1" role="tab">
                                    <i class="ti ti-brand-telegram icon"></i>&nbsp;
                                    {trans key='user.tab_connectivity'}
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#other_settings" class="nav-link" data-bs-toggle="tab" aria-selected="false"
                                   tabindex="-1" role="tab">
                                    <i class="ti ti-settings icon"></i>&nbsp;
                                    {trans key='user.tab_preferences'}
                                </a>
                            </li>
                        </ul>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="personal_information" role="tabpanel">
                                    <div class="row row-deck row-cards">
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.login_email'}</h3>
                                                    <p>{trans key='user.current_email' email="<code id='email'>{$user->email|escape}</code>"}</p>
                                                    <div class="mb-3">
                                                        <input id="new-email" type="email" class="form-control"
                                                               placeholder="{trans key='user.new_email'}"
                                                               {if ! $config['enable_change_email']}disabled=""{/if}>
                                                    </div>
                                                    {if $public_setting['reg_email_verify'] && $config['enable_change_email']}
                                                    <div class="mb-3">
                                                        <input id="email-code" type="text" class="form-control"
                                                               placeholder="{trans key='user.verification_code'}">
                                                    </div>
                                                    {/if}
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        {if $public_setting['reg_email_verify'] && $config['enable_change_email']}
                                                        <button class="btn btn-link"
                                                                hx-post="/user/edit/send" hx-swap="none"
                                                                hx-vals='js:{ email: document.getElementById("new-email").value }'>
                                                            {trans key='user.get_code'}
                                                        </button>
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/email" hx-swap="none"
                                                                hx-vals='js:{
                                                                    newemail: document.getElementById("new-email").value,
                                                                    emailcode: document.getElementById("email-code").value
                                                                }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                        {elseif $config['enable_change_email']}
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/email" hx-swap="none"
                                                                hx-vals='js:{ newemail: document.getElementById("new-email").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                        {else}
                                                        <button class="btn btn-primary ms-auto"
                                                                disabled>{trans key='user.not_allowed_to_modify'}
                                                        </button>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.username'}</h3>
                                                    <p>{trans key='user.current_username' username="<code id='username'>{$user->user_name|escape}</code>"}</p>
                                                    <div class="mb-3">
                                                        <input id="new-username" type="text" class="form-control"
                                                               placeholder="{trans key='user.new_username'}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                           hx-post="/user/edit/username" hx-swap="none"
                                                           hx-vals='js:{ newusername: document.getElementById("new-username").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.im_bind'}</h3>
                                                    <div class="mb-3">
                                                        <select id="imtype" class="form-select"
                                                                {if $user->im_type !== 0 && $user->im_value !== ''}disabled=""{/if}>
                                                            <option value="0" {if $user->im_type === 0}selected{/if}>
                                                                {trans key='user.im_not_bound'}
                                                            </option>
                                                            <option value="1" {if $user->im_type === 1}selected{/if}>
                                                                Slack
                                                            </option>
                                                            <option value="2" {if $user->im_type === 2}selected{/if}>
                                                                Discord
                                                            </option>
                                                            <option value="4" {if $user->im_type === 4}selected{/if}>
                                                                Telegram
                                                            </option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <input id="imvalue" type="text" class="form-control"
                                                               value="{$user->im_value|escape}" disabled>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div id="oauth-provider"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.im_unbind'}</h3>
                                                    {if $user->im_type === 0}
                                                    <p>{trans key='user.im_no_service_bound'}</p>
                                                    {else}
                                                    <p>
                                                        {trans key='user.im_current_bound' service=$user->imType()}
                                                        <br>
                                                        {trans key='user.im_account_id' id="<code>{$user->im_value|escape}</code>"}
                                                    </p>
                                                    {/if}
                                                </div>
                                                {if $user->im_type !== 0}
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-red ms-auto"
                                                                hx-post="/user/edit/unbind_im" hx-swap="none">
                                                            {trans key='user.unbind'}
                                                        </button>
                                                    </div>
                                                </div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="login_security" role="tabpanel">
                                    <div class="row row-deck row-cards">
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.change_password'}</h3>
                                                    <div class="mb-3">
                                                        <form>
                                                            <input id="password" type="password" class="form-control"
                                                                   placeholder="{trans key='user.current_password'}" autocomplete="off">
                                                        </form>
                                                    </div>
                                                    <div class="mb-3">
                                                        <form>
                                                            <input id="new_password" type="password"
                                                                   class="form-control" placeholder="{trans key='user.enter_new_password'}"
                                                                   autocomplete="off">
                                                        </form>
                                                    </div>
                                                    <div class="mb-3">
                                                        <form>
                                                            <input id="confirm_new_password" type="password"
                                                                   class="form-control" placeholder="{trans key='user.reenter_new_password'}"
                                                                   autocomplete="off">
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/password" hx-swap="none"
                                                                hx-vals='js:{
                                                                    new_password: document.getElementById("new_password").value,
                                                                    confirm_new_password: document.getElementById("confirm_new_password").value,
                                                                    password: document.getElementById("password").value
                                                                }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">TOTP
                                                        {if $totpDevices}
                                                            <span class="badge bg-green text-green-fg">{trans key='common.enabled'}</span>
                                                        {else}
                                                            <span class="badge bg-red text-red-fg">{trans key='common.disabled'}</span>
                                                        {/if}
                                                    </h3>
                                                    <p class="card-subtitle">{trans key='user.totp_desc'}</p>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        {if $totpDevices}
                                                            <button class="btn btn-red ms-auto"
                                                                    hx-delete="/user/totp"
                                                                    hx-confirm="{trans key='user.confirm_disable_totp'}"
                                                                    hx-swap="none">
                                                                {trans key='user.disable'}
                                                            </button>
                                                        {else}
                                                            <button class="btn btn-primary ms-auto" id="enableTotp">
                                                                {trans key='user.enable'}
                                                            </button>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-12">
                                            {capture assign='unnamed_device'}{trans key='user.unnamed_device'}{/capture}
                                            {capture assign='never_used'}{trans key='user.never_used'}{/capture}
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">Passkey</h3>
                                                    <p class="card-subtitle">{trans key='user.passkey_desc'}</p>
                                                    <div class="row row-cols-1 row-cols-md-4 g-4">
                                                        {foreach $webauthnDevices as $device}
                                                            <div class="col">
                                                                <div class="card">
                                                                    <div class="card-body">
                                                                        <h5 class="card-title">{$device->name|default:$unnamed_device|escape}</h5>
                                                                        <p class="card-text">
                                                                            {trans key='user.added_at' time=$device->created_at}</p>
                                                                        <p class="card-text">
                                                                            {trans key='user.last_used_at' time=($device->used_at|default:$never_used)}</p>
                                                                        <button class="btn btn-danger"
                                                                                hx-delete="/user/webauthn/{$device->id}"
                                                                                hx-swap="none"
                                                                                hx-confirm="{trans key='user.confirm_delete_device'}"
                                                                        >{trans key='common.delete'}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        {/foreach}
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto" id="webauthnReg">
                                                            {trans key='user.register_passkey_device'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='auth.oauth_accounts_title'}</h3>
                                                    <p class="card-subtitle">{trans key='auth.oauth_accounts_desc'}</p>
                                                    {if ! empty($oauth_error)}
                                                        <div class="alert alert-danger mb-3" role="alert">{$oauth_error|escape}</div>
                                                    {/if}
                                                    {if ! empty($oauth_msg)}
                                                        <div class="alert alert-success mb-3" role="alert">{$oauth_msg|escape}</div>
                                                    {/if}
                                                    <div class="row g-3">
                                                        {if ! empty($public_setting['enable_google_oauth']) || ! empty($user->google_id)}
                                                            <div class="col-sm-12 col-md-6">
                                                                <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <div class="fw-bold">
                                                                            <i class="ti ti-brand-google icon me-1"></i> Google
                                                                        </div>
                                                                        <div class="text-secondary small">
                                                                            {if ! empty($user->google_id)}
                                                                                <span class="badge bg-green-lt">{trans key='auth.oauth_bound'}</span>
                                                                            {else}
                                                                                <span class="badge bg-secondary-lt">{trans key='auth.oauth_not_bound'}</span>
                                                                            {/if}
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        {if ! empty($user->google_id)}
                                                                            <button class="btn btn-outline-danger btn-sm"
                                                                                    hx-post="/user/edit/unbind_oauth"
                                                                                    hx-swap="none"
                                                                                    hx-vals='js:{ provider: "google" }'>
                                                                                {trans key='user.unbind'}
                                                                            </button>
                                                                        {elseif ! empty($public_setting['enable_google_oauth'])}
                                                                            <a href="/oauth/google" class="btn btn-primary btn-sm">
                                                                                {trans key='auth.oauth_bind'}
                                                                            </a>
                                                                        {/if}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        {/if}
                                                        {if ! empty($public_setting['enable_github_oauth']) || ! empty($user->github_id)}
                                                            <div class="col-sm-12 col-md-6">
                                                                <div class="border rounded p-3 d-flex align-items-center justify-content-between">
                                                                    <div>
                                                                        <div class="fw-bold">
                                                                            <i class="ti ti-brand-github icon me-1"></i> GitHub
                                                                        </div>
                                                                        <div class="text-secondary small">
                                                                            {if ! empty($user->github_id)}
                                                                                <span class="badge bg-green-lt">{trans key='auth.oauth_bound'}</span>
                                                                            {else}
                                                                                <span class="badge bg-secondary-lt">{trans key='auth.oauth_not_bound'}</span>
                                                                            {/if}
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        {if ! empty($user->github_id)}
                                                                            <button class="btn btn-outline-danger btn-sm"
                                                                                    hx-post="/user/edit/unbind_oauth"
                                                                                    hx-swap="none"
                                                                                    hx-vals='js:{ provider: "github" }'>
                                                                                {trans key='user.unbind'}
                                                                            </button>
                                                                        {elseif ! empty($public_setting['enable_github_oauth'])}
                                                                            <a href="/oauth/github" class="btn btn-primary btn-sm">
                                                                                {trans key='auth.oauth_bind'}
                                                                            </a>
                                                                        {/if}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-12">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">FIDO
                                                        {if $fidoDevices}
                                                            <span class="badge bg-green text-green-fg">{trans key='common.enabled'}</span>
                                                        {else}
                                                            <span class="badge bg-red text-red-fg">{trans key='common.disabled'}</span>
                                                        {/if}
                                                    </h3>
                                                    <p class="card-subtitle">{trans key='user.fido_desc'}</p>
                                                    {if $fidoDevices}
                                                        <div class="row row-cols-1 row-cols-md-4 g-4">
                                                            {foreach $fidoDevices as $device}
                                                                <div class="col">
                                                                    <div class="card">
                                                                        <div class="card-body">
                                                                            <h5 class="card-title">{$device->name|default:$unnamed_device|escape}</h5>
                                                                            <p class="card-text">
                                                                                {trans key='user.added_at' time=$device->created_at}</p>
                                                                            <p class="card-text">
                                                                                {trans key='user.last_used_at' time=($device->used_at|default:$never_used)}</p>
                                                                            <button class="btn btn-danger"
                                                                                    hx-delete="/user/fido/{$device->id}"
                                                                                    hx-swap="none"
                                                                                    hx-confirm="{trans key='user.confirm_delete_device'}"
                                                                            >{trans key='common.delete'}
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            {/foreach}
                                                        </div>
                                                    {/if}
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto" id="fidoReg">
                                                            {trans key='user.register_fido_device'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="use_safety" role="tabpanel">
                                    <div class="row row-deck row-cards">
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.change_encryption'}</h3>
                                                    <p>
                                                        {trans key='user.encryption_desc'}</p>
                                                    <div class="mb-3">
                                                        <select id="user-method" class="form-select">
                                                            {foreach $methods as $method}
                                                            <option value="{$method}"
                                                                    {if $user->method === $method}selected{/if}>
                                                                {$method}
                                                            </option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/method" hx-swap="none"
                                                                hx-vals='js:{ method: document.getElementById("user-method").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.reset_sub_url'}</h3>
                                                    <p>{trans key='user.reset_sub_desc'}</p>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-red ms-auto"
                                                                hx-post="/user/edit/url_reset" hx-swap="none">
                                                            {trans key='common.reset'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.reset_connection_password'}</h3>
                                                    <p>{trans key='user.reset_connection_password_desc'}</p>
                                                    <p>{trans key='user.current_connection_password' passwd="<code id='passwd' class='spoiler'>{$user->passwd|escape}</code>"}</p>
                                                    <p>{trans key='user.current_uuid' uuid="<code id='uuid' class='spoiler'>{$user->uuid|escape}</code>"}</p>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-red ms-auto"
                                                                hx-post="/user/edit/passwd_reset" hx-swap="none">
                                                            {trans key='common.reset'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="other_settings" role="tabpanel">
                                    <div class="row row-deck row-cards">
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.daily_mail_report'}</h3>
                                                    <div class="mb-3">
                                                        <select id="daily-mail" class="form-select">
                                                            <option value="0"
                                                                    {if $user->daily_mail_enable === 0}selected{/if}>
                                                                {trans key='user.report_none'}
                                                            </option>
                                                            <option value="1"
                                                                    {if $user->daily_mail_enable === 1}selected{/if}>
                                                                {trans key='user.report_email'}
                                                            </option>
                                                            <option value="2"
                                                                    {if $user->daily_mail_enable === 2}selected{/if}>
                                                                {trans key='user.report_im'}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/daily_mail" hx-swap="none"
                                                                hx-vals='js:{ mail: document.getElementById("daily-mail").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.contact_preference'}</h3>
                                                    <p>{trans key='user.contact_preference_desc'}</p>
                                                    <div class="mb-3">
                                                        <select id="contact-method" class="form-select">
                                                            <option value="1"
                                                                    {if $user->contact_method === 1}selected{/if}>
                                                                {trans key='user.contact_email'}
                                                            </option>
                                                            <option value="2"
                                                                    {if $user->contact_method === 2}selected{/if}>
                                                                {trans key='user.contact_im'}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/contact_method" hx-swap="none"
                                                                hx-vals='js:{ contact: document.getElementById("contact-method").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.theme'}</h3>
                                                    <div class="mb-3">
                                                        <select id="user-theme" class="form-select">
                                                            {foreach $themes as $theme}
                                                            <option value="{$theme}"
                                                                    {if $user->theme === $theme}selected{/if}>{$theme}
                                                            </option>
                                                            {/foreach}
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/theme" hx-swap="none"
                                                                hx-vals='js:{ theme: document.getElementById("user-theme").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.theme_mode'}</h3>
                                                    <div class="mb-3">
                                                        <select id="theme-mode" class="form-select">
                                                            <option value="2" {if $user->is_dark_mode === 2}selected{/if}>
                                                                {trans key='user.theme_auto'}
                                                            </option>
                                                            <option value="0" {if $user->is_dark_mode === 0}selected{/if}>
                                                                {trans key='user.theme_light'}
                                                            </option>
                                                            <option value="1" {if $user->is_dark_mode === 1}selected{/if}>
                                                                {trans key='user.theme_dark'}
                                                            </option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex">
                                                        <button class="btn btn-primary ms-auto"
                                                                hx-post="/user/edit/theme_mode" hx-swap="none"
                                                                hx-vals='js:{ theme_mode: document.getElementById("theme-mode").value }'>
                                                            {trans key='user.modify'}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {if $config['enable_kill']}
                                        <div class="col-sm-12 col-md-6">
                                            <div class="card">
                                                <div class="card-stamp">
                                                    <div class="card-stamp-icon bg-red">
                                                        <i class="ti ti-circle-x"></i>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <h3 class="card-title">{trans key='user.delete_account'}</h3>
                                                </div>
                                                <div class="card-footer">
                                                    <button class="btn btn-red" data-bs-toggle="modal"
                                                       data-bs-target="#destroy-account">
                                                        <i class="ti ti-trash icon"></i>
                                                        {trans key='user.confirm_delete_account_btn'}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {if $config['enable_kill']}
    <div class="modal modal-blur fade" id="destroy-account" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content">
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-status bg-danger"></div>
                <div class="modal-body text-center py-4">
                    <i class="ti ti-alert-circle icon mb-2 text-danger icon-lg" style="font-size:3.5rem;"></i>
                    <h3>{trans key='common.delete_dialog_title'}</h3>
                    <div class="text-secondary">
                        {trans key='user.delete_account_warning'}
                    </div>
                    <div class="py-3">
                        <form>
                            <input id="confirm_kill_password" type="password" class="form-control"
                                   placeholder="{trans key='user.enter_password_to_delete'}" autocomplete="off">
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button class="btn w-100" data-bs-dismiss="modal">
                                    {trans key='common.cancel'}
                                </button>
                            </div>
                            <div class="col">
                                <button href="#" class="btn btn-danger w-100" data-bs-dismiss="modal"
                                        hx-post="/user/edit/kill" hx-swap="none"
                                        hx-vals='js:{ password: document.getElementById("confirm_kill_password").value }'>
                                    {trans key='common.confirm'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {/if}

    <div class="modal" id="totpModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{trans key='user.setup_totp'}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="row">
                        <div class="col-md-12">
                            <p>{trans key='user.scan_qrcode_desc'}</p>
                        </div>
                        <div class="col-md-12 d-flex justify-content-center align-items-center">
                            <div id="qrcode"></div>
                        </div>
                        <div class="col-md-12">
                            <p>{trans key='user.manual_totp_key'}</p>
                            <p id="totpSecret"></p>
                        </div>
                        <div class="col-md-12">
                            <input type="text" id="totpCode" placeholder="{trans key='user.enter_totp_code'}" class="form-control mx-auto">
                        </div>
                    </div>
                    <div id="qrcode"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="submitTotp">{trans key='common.submit'}</button>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
    <script>
        let Modal = tabler.Modal || tabler.bootstrap.Modal;
        {if not $totpDevices}
        document.querySelector('#enableTotp').addEventListener('click', async () => {
            const resp = await fetch('/user/totp');
            const data = await resp.json();
            var modal = new Modal(document.getElementById('totpModal'), {
                backdrop: 'static',
                keyboard: false
            });
            if (data.ret === 1) {
                let qrcodeElement = document.getElementById('qrcode');
                qrcodeElement.innerHTML = '';
                let totpSecret = document.getElementById('totpSecret');
                totpSecret.innerHTML = data.token;
                let qrcode = new QRCode(qrcodeElement, {
                    text: data.url,
                    width: 256,
                    height: 256,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.H
                });
                modal.show();
            } else {
                var fail_modal = new Modal(document.getElementById('fail-dialog'));
                document.getElementById('fail-message').innerText = data.msg;
                fail_modal.show();
            }
        });

        document.getElementById('submitTotp').addEventListener('click', function () {
            var totpCode = document.getElementById('totpCode').value;

            fetch('/user/totp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                {literal}
                body: JSON.stringify({code: totpCode}),
                {/literal}
            })
                .then(response => response.json())
                .then(data => {
                    var totpModal = new Modal(document.getElementById('totpModal'));

                    if (data.ret === 1) {
                        totpModal.hide();
                        document.getElementById("success-message").innerHTML = data.msg;
                        successDialog.show();
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        document.getElementById("fail-message").innerHTML = data.msg;
                        failDialog.show();
                    }
                })
        });
        {/if}
        const { startRegistration } = SimpleWebAuthnBrowser;
        document.getElementById('fidoReg').addEventListener('click', async () => {
            const resp = await fetch('/user/fido');
            let attResp;
            const options = await resp.json();
            try {
                attResp = await startRegistration({ optionsJSON: options });
            } catch (error) {
                $('#error-message').text(error.message);
                failDialog.show();
                throw error;
            }
            attResp.name = prompt("{trans key='user.device_name_prompt' escape='js'}");
            const verificationResp = await fetch('/user/fido', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(attResp),
            });

            const verificationJSON = await verificationResp.json();
            if (verificationJSON.ret === 1) {
                $('#success-message').text(verificationJSON.msg);
                successDialog.show();
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else {
                $('#error-message').text(verificationJSON.msg);
                failDialog.show();
            }
        });
        document.getElementById('webauthnReg').addEventListener('click', async () => {
            const resp = await fetch('/user/webauthn');
            const options = await resp.json();
            let attResp;
            try {
                attResp = await startRegistration({ optionsJSON: options });
            } catch (error) {
                $('#error-message').text(error.message);
                failDialog.show();
                throw error;
            }
            attResp.name = prompt("{trans key='user.device_name_prompt' escape='js'}");
            const verificationResp = await fetch('/user/webauthn', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(attResp),
            });
            const verificationJSON = await verificationResp.json();
            if (verificationJSON.ret === 1) {
                $('#success-message').text(verificationJSON.msg);
                successDialog.show();
                setTimeout(function () {
                    location.reload();
                }, 1000);
            } else {
                $('#error-message').text(verificationJSON.msg);
                failDialog.show();
            }
        });
        {if $user->im_type === 0 && $user->im_value === ''}
        let oauthProvider = $('#oauth-provider');
        let tgPollTimer = null;
        let pollStartTime = 0;

        function stopTgPolling() {
            if (tgPollTimer) {
                clearInterval(tgPollTimer);
                tgPollTimer = null;
            }
        }

        function checkTgBindStatus(manual = false) {
            $.ajax({
                type: "GET",
                url: "/oauth/telegram",
                data: { action: "check" },
                dataType: "json",
                success: function (res) {
                    if (res.ret === 1 && res.bound) {
                        stopTgPolling();
                        $('#success-message').text('{trans key="user.tg_bind_success" escape="js"}');
                        if (window.successDialog) {
                            successDialog.show();
                        } else if (typeof showToast === 'function') {
                            showToast('{trans key="user.tg_bind_success" escape="js"}');
                        }
                        setTimeout(function () {
                            location.reload();
                        }, 1200);
                    } else if (manual) {
                        if (typeof showToast === 'function') {
                            showToast('{trans key="user.tg_bind_not_detected" escape="js"}', 'danger');
                        } else {
                            $('#error-message').text('{trans key="user.tg_bind_not_detected" escape="js"}');
                            if (window.failDialog) {
                                failDialog.show();
                            }
                        }
                    }
                },
                error: function () {
                    if (manual) {
                        if (typeof showToast === 'function') {
                            showToast('{trans key="user.tg_bind_check_failed" escape="js"}', 'danger');
                        }
                    }
                }
            });
        }

        function startTgPolling() {
            stopTgPolling();
            pollStartTime = Date.now();
            tgPollTimer = setInterval(function () {
                if (Date.now() - pollStartTime > 60000) {
                    stopTgPolling();
                    return;
                }
                checkTgBindStatus(false);
            }, 3000);
        }

        function renderTelegramUI(data) {
            let tgInstruction = '{trans key="user.tg_bind_instruction" escape="js"}'
                .replace('%bot%', data.bot_username)
                .replace(/%token%/g, data.token);
            let html = '<div class="w-100">' +
                '<div class="card bg-light-lt border mb-3">' +
                '<div class="card-body p-3">' +
                '<div class="d-flex align-items-center justify-content-between mb-2">' +
                '<span class="text-secondary fw-semibold">{trans key="user.tg_bind_code_label" escape="js"}</span>' +
                '<button type="button" class="btn btn-sm btn-outline-secondary copy-token-btn" data-token="' + data.token + '">' +
                '<i class="ti ti-copy icon"></i> {trans key="user.tg_copy_code" escape="js"}' +
                '</button>' +
                '</div>' +
                '<div class="text-center my-2">' +
                '<code class="fs-2 fw-bold text-primary bg-body px-3 py-1 rounded border">' + data.token + '</code>' +
                '</div>' +
                '<div class="text-secondary small mt-2">' +
                tgInstruction +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="d-flex flex-wrap align-items-center justify-content-between gap-2">' +
                '<div class="btn-list">' +
                '<a href="' + data.bind_url + '" target="_blank" class="btn btn-primary">' +
                '<i class="ti ti-brand-telegram icon"></i> {trans key="user.tg_open_bind" escape="js"}' +
                '</a>' +
                '<button type="button" class="btn btn-outline-primary" id="refresh-tg-status">' +
                '<i class="ti ti-refresh icon"></i> {trans key="user.tg_refresh_status" escape="js"}' +
                '</button>' +
                '</div>' +
                '<div>' +
                '<a href="javascript:void(0)" id="toggle-tg-widget" class="text-secondary small ms-2">{trans key="user.tg_web_widget_bind" escape="js"}</a>' +
                '</div>' +
                '</div>' +
                '<div id="tg-widget-container" class="mt-3 text-center d-none"></div>' +
                '</div>';
            oauthProvider.html(html);
        }

        $("#imtype").on('change', function () {
            stopTgPolling();
            if ($(this).val() === '0') {
                oauthProvider.empty();
            } else if ($(this).val() === '1') {
                oauthProvider.empty();
                oauthProvider.append(
                    "<div class='d-flex btn-list justify-content-end'><a id='bind-slack' class='btn btn-azure ms-auto'>{trans key='user.bind_slack' escape='js'}</a></div>"
                );
            } else if ($(this).val() === '2') {
                oauthProvider.empty();
                oauthProvider.append(
                    "<div class='d-flex btn-list justify-content-end'><a id='bind-discord' class='btn btn-indigo ms-auto'>{trans key='user.bind_discord' escape='js'}</a></div>"
                );
            } else if ($(this).val() === '4') {
                oauthProvider.empty().html(
                    '<div class="d-flex align-items-center py-2">' +
                    '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>' +
                    '<span class="text-secondary small">{trans key="user.tg_fetching_bind_info" escape="js"}</span>' +
                    '</div>'
                );

                $.ajax({
                    type: "GET",
                    url: "/oauth/telegram",
                    data: { action: "get_token" },
                    dataType: "json",
                    success: function (data) {
                        if (data.ret === 1) {
                            renderTelegramUI(data);
                            startTgPolling();
                        } else {
                            oauthProvider.html('<div class="text-danger small py-2">' + data.msg + '</div>');
                        }
                    },
                    error: function () {
                        oauthProvider.html('<div class="text-danger small py-2">{trans key="user.tg_fetch_bind_info_failed" escape="js"}</div>');
                    }
                });
            }
        });

        oauthProvider.on('click', '.copy-token-btn', function () {
            let token = $(this).attr('data-token');
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(token).then(function () {
                    if (typeof showToast === 'function') {
                        showToast('{trans key="user.tg_code_copied" escape="js"}');
                    } else {
                        $('#success-message').text('{trans key="user.tg_code_copied" escape="js"}');
                        if (window.successDialog) {
                            successDialog.show();
                        }
                    }
                }).catch(function () {
                    prompt('{trans key="user.tg_copy_code_manual" escape="js"}', token);
                });
            } else {
                prompt('{trans key="user.tg_copy_code_manual" escape="js"}', token);
            }
        });

        oauthProvider.on('click', '#refresh-tg-status', function () {
            let btn = $(this);
            btn.prop('disabled', true);
            checkTgBindStatus(true);
            setTimeout(function () {
                btn.prop('disabled', false);
            }, 1500);
        });

        oauthProvider.on('click', '#toggle-tg-widget', function () {
            let widgetContainer = $('#tg-widget-container');
            if (widgetContainer.hasClass('d-none')) {
                widgetContainer.removeClass('d-none');
                if (!widgetContainer.children().length) {
                    let script = document.createElement('script');
                    script.async = true;
                    script.src = 'https://telegram.org/js/telegram-widget.js?22';
                    script.setAttribute('data-telegram-login', "{$public_setting['telegram_bot']}");
                    script.setAttribute('data-size', 'large');
                    script.setAttribute('data-onauth', 'onTelegramAuth(user)');
                    script.setAttribute('data-request-access', 'write');
                    widgetContainer[0].appendChild(script);
                }
            } else {
                widgetContainer.addClass('d-none');
            }
        });

        oauthProvider.on('click', '#bind-slack', function () {
            $.ajax({
                type: "POST",
                url: "/oauth/slack",
                dataType: "json",
                success: function (data) {
                    handleOauthResult(data, 'slack');
                }
            });
        });

        oauthProvider.on('click', '#bind-discord', function () {
            $.ajax({
                type: "POST",
                url: "/oauth/discord",
                dataType: "json",
                success: function (data) {
                    handleOauthResult(data, 'discord');
                }
            });
        });

        window.onTelegramAuth = function (user) {
            $.ajax({
                type: "POST",
                url: "/oauth/telegram",
                dataType: "json",
                data: {
                    user: JSON.stringify(user),
                },
                success: function (data) {
                    handleOauthResult(data, 'telegram');
                }
            });
        };

        function handleOauthResult(data, type = 'telegram') {
            if (data.ret === 1) {
                if (type === 'telegram') {
                    stopTgPolling();
                    $('#success-message').text(data.msg);
                    successDialog.show();
                    setTimeout(function () {
                        location.reload();
                    }, 1200);
                } else {
                    window.location.replace(data.redir);
                }
            } else {
                $('#error-message').text(data.msg);
                failDialog.show();
            }
        }
        {/if}
        if (window.location.hash === '#login_security') {
            const tabTrigger = document.querySelector('a[href="#login_security"]');
            if (tabTrigger && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                new bootstrap.Tab(tabTrigger).show();
            } else if (tabTrigger) {
                tabTrigger.click();
            }
        }
    </script>
