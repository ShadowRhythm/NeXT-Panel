{include file='header.tpl'}

<body class="border-top-wide border-primary d-flex flex-column">
<div class="page page-center">
    <div class="container-tight my-auto py-4">
        <div class="text-center mb-4">
            <a href="#" class="navbar-brand navbar-brand-autodark">
                <img src="/images/uim-logo-round_96x96.png" height="64" alt="SSPanel-UIM Logo">
            </a>
        </div>
        <div class="card card-md">
            {if $public_setting['reg_mode'] !== 'close'}
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">{trans key='auth.register_title'}</h2>
                    <div class="mb-3">
                        <input id="name" type="text" class="form-control" placeholder="{trans key='auth.nickname'}">
                    </div>
                    <div class="mb-3">
                        <input id="email" type="email" class="form-control" placeholder="{trans key='auth.email_placeholder'}">
                    </div>
                    {if $public_setting['reg_email_verify']}
                    <div class="mb-3">
                        <div class="input-group mb-2">
                            <input id="emailcode" type="text" class="form-control" placeholder="{trans key='auth.email_verify_code_placeholder'}">
                            <button id="send-verify-email" class="btn text-blue" type="button"
                                    hx-post="/auth/send" hx-swap="none" hx-disabled-elt="this"
                                    hx-vals='js:{ email: document.getElementById("email").value }'>
                                {trans key='auth.btn_get_code'}
                            </button>
                        </div>
                    </div>
                    {/if}
                    <div class="mb-3">
                        <div class="input-group input-group-flat">
                            <input id="password" type="password" class="form-control" placeholder="{trans key='auth.password_placeholder'}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group input-group-flat">
                            <input id="confirm_password" type="password" class="form-control" placeholder="{trans key='auth.confirm_password_placeholder'}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group input-group-flat">
                            <input id="invite_code" type="text" class="form-control"
                                   placeholder="{if $public_setting['reg_mode'] === 'open'}{trans key='auth.invite_code_optional'}{else}{trans key='auth.invite_code_required'}{/if}"
                                   value="{$invite_code}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input id="tos" type="checkbox" class="form-check-input"/>
                            <span class="form-check-label">
                                    {trans key='auth.agree_terms_prefix'} <a href="/tos" tabindex="-1"> {trans key='auth.terms_and_privacy'} </a>
                                </span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <div class="input-group mb-3">
                        {if $public_setting['enable_reg_captcha']}
                            {include file='captcha/div.tpl'}
                        {/if}
                        </div>
                    </div>
                    <div class="form-footer">
                        <button class="btn btn-primary w-100"
                                hx-post="/auth/register" hx-swap="none" hx-vals='js:{
                                    {if $public_setting['reg_email_verify']}
                                        emailcode: document.getElementById("emailcode").value,
                                    {/if}
                                    {if $public_setting['enable_reg_captcha']}
                                        {include file='captcha/ajax.tpl'}
                                    {/if}
                                    name: document.getElementById("name").value,
                                    email: document.getElementById("email").value,
                                    password: document.getElementById("password").value,
                                    confirm_password: document.getElementById("confirm_password").value,
                                    invite_code: document.getElementById("invite_code").value,
                                    tos: document.getElementById("tos").checked,
                                 }'>
                            {trans key='auth.btn_register'}
                        </button>
                        <div class="text-center text-secondary mt-3">
                            {trans key='auth.verify_code_tip'}
                        </div>
                    </div>
                    {if ! empty($public_setting['enable_google_oauth']) || ! empty($public_setting['enable_github_oauth'])}
                        <div class="hr-text">{trans key='auth.or_continue_with'}</div>
                        <div class="row g-2">
                            {if ! empty($public_setting['enable_google_oauth'])}
                                <div class="col">
                                    <a href="/oauth/google" data-oauth-register="google" class="btn w-100">
                                        <i class="ti ti-brand-google icon me-1"></i>
                                        Google
                                    </a>
                                </div>
                            {/if}
                            {if ! empty($public_setting['enable_github_oauth'])}
                                <div class="col">
                                    <a href="/oauth/github" data-oauth-register="github" class="btn w-100">
                                        <i class="ti ti-brand-github icon me-1"></i>
                                        GitHub
                                    </a>
                                </div>
                            {/if}
                        </div>
                    {/if}
                </div>
            {else}
                <div class="card-body">
                    <p>{trans key='auth.registration_closed'}</p>
                </div>
            {/if}
        </div>
        <div class="text-center text-secondary mt-3">
            {trans key='auth.already_have_account'} <a href="/auth/login" tabindex="-1">{trans key='auth.click_to_login'}</a>
        </div>
    </div>
</div>

{if $public_setting['enable_reg_captcha']}
    {include file='captcha/js.tpl'}
{/if}

<script>
    document.querySelectorAll('[data-oauth-register]').forEach(function (link) {
        link.addEventListener('click', function () {
            const inviteInput = document.getElementById('invite_code');
            const inviteVal = inviteInput ? inviteInput.value.trim() : '';
            const provider = link.getAttribute('data-oauth-register');
            if (inviteVal !== '') {
                link.setAttribute('href', '/oauth/' + provider + '?invite_code=' + encodeURIComponent(inviteVal));
            } else {
                link.setAttribute('href', '/oauth/' + provider);
            }
        });
    });
</script>

{include file='footer.tpl'}
