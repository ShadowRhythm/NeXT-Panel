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
                <h2 class="card-title text-center mb-4">{trans key='auth.reset_password_title'}</h2>
                <div class="mb-3">
                    <label class="form-label">{trans key='auth.new_password'}</label>
                    <input id="password" type="password" class="form-control" placeholder="{trans key='auth.new_password_placeholder'}">
                </div>
                <div class="mb-3">
                    <label class="form-label">{trans key='auth.confirm_new_password'}</label>
                    <input id="confirm_password" type="password" class="form-control" placeholder="{trans key='auth.confirm_new_password_placeholder'}">
                </div>
                <div class="form-footer">
                    <button class="btn btn-primary w-100"
                            hx-post="/password/token/{$token}" hx-swap="none"
                            hx-vals='js:{
                            password: document.getElementById("password").value,
                            confirm_password: document.getElementById("confirm_password").value,
                            token: "{$token}" }'>
                        <i class="ti ti-key icon"></i>
                        {trans key='auth.btn_reset_password'}
                    </button>
                </div>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            {trans key='auth.already_have_account'} <a href="/auth/login" tabindex="-1">{trans key='auth.click_to_login'}</a>
        </div>
    </div>
</div>

{include file='footer.tpl'}
