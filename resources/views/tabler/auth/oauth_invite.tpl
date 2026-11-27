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
            <div class="card-body">
                <h2 class="card-title text-center mb-3">{trans key='auth.oauth_invite_title'}</h2>
                <p class="text-secondary text-center mb-3">
                    {trans key='auth.oauth_invite_desc' email=$oauth_email|escape}
                </p>
                <div class="mb-3">
                    <label class="form-label">{trans key='auth.email'}</label>
                    <input type="email" class="form-control" value="{$oauth_email|escape}" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">{trans key='auth.invite_code_required'}</label>
                    <input id="invite_code" type="text" class="form-control"
                           placeholder="{trans key='auth.invite_code_required'}"
                           value="{$invite_code|escape}">
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input id="tos" type="checkbox" class="form-check-input"/>
                        <span class="form-check-label">
                            {trans key='auth.agree_terms_prefix'} <a href="/tos" tabindex="-1">{trans key='auth.terms_and_privacy'}</a>
                        </span>
                    </label>
                </div>
                <div class="form-footer">
                    <button class="btn btn-primary w-100"
                            hx-post="/auth/oauth/invite" hx-swap="none" hx-vals='js:{
                                token: "{$token|escape:'javascript'}",
                                invite_code: document.getElementById("invite_code").value,
                                tos: document.getElementById("tos").checked
                            }'>
                        {trans key='auth.btn_complete_registration'}
                    </button>
                </div>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            <a href="/auth/login" tabindex="-1">{trans key='auth.click_to_login'}</a>
        </div>
    </div>
</div>

{include file='footer.tpl'}
