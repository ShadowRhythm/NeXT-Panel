{include file='header.tpl'}

<script src="https://unpkg.com/@simplewebauthn/browser/dist/bundle/index.umd.min.js"></script>
<style>
    .otp-cell {
        min-height: 3.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        user-select: none;
        transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }
    .otp-cell.is-active {
        border-color: var(--tblr-primary);
        box-shadow: 0 0 0 .25rem rgba(var(--tblr-primary-rgb), .25);
    }
    .otp-real-input {
        color: transparent !important;
        caret-color: transparent !important;
        outline: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        -webkit-appearance: none;
        appearance: none;
        z-index: 2;
        font-size: 1rem;
    }
    .otp-real-input::selection {
        background: transparent;
        color: transparent;
    }
    .otp-real-input:-webkit-autofill,
    .otp-real-input:-webkit-autofill:hover,
    .otp-real-input:-webkit-autofill:focus {
        -webkit-text-fill-color: transparent !important;
        -webkit-box-shadow: 0 0 0 1000px transparent inset !important;
        transition: background-color 9999s ease-in-out 0s;
    }
</style>

<body class="border-top-wide border-primary d-flex flex-column">
<div class="page page-center">
    <div class="container-tight my-auto py-4">
        <div class="card card-md">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">{trans key='auth.mfa_title'}</h2>
                <p>{trans key='auth.mfa_notice'}</p>
                {if $method['totp']}
                    <form id="totpForm" method="post" action="/auth/totp"
                          hx-post="/auth/totp" hx-swap="none" hx-vals="js:{
                                code: readTotpCode(),
                            }">
                        <div class="my-5 position-relative">
                            <div class="row g-4 pe-none" aria-hidden="true" data-otp-cells>
                                <div class="col">
                                    <div class="row g-2">
                                        <div class="col"><div class="form-control form-control-lg text-center py-3 px-0 otp-cell"></div></div>
                                        <div class="col"><div class="form-control form-control-lg text-center py-3 px-0 otp-cell"></div></div>
                                        <div class="col"><div class="form-control form-control-lg text-center py-3 px-0 otp-cell"></div></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="row g-2">
                                        <div class="col"><div class="form-control form-control-lg text-center py-3 px-0 otp-cell"></div></div>
                                        <div class="col"><div class="form-control form-control-lg text-center py-3 px-0 otp-cell"></div></div>
                                        <div class="col"><div class="form-control form-control-lg text-center py-3 px-0 otp-cell"></div></div>
                                    </div>
                                </div>
                            </div>
                            <label for="otpCode" class="visually-hidden">{trans key='auth.mfa_title'}</label>
                            <input type="text" id="otpCode" name="code" maxlength="6"
                                   inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code"
                                   class="position-absolute top-0 start-0 w-100 h-100 border-0 bg-transparent otp-real-input">
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                {trans key='auth.btn_submit'}
                            </button>
                        </div>
                    </form>
                {/if}
                {if $method['fido']}
                    <div class="{if ! $method['totp']}form-footer{/if}">
                        <button type="button" class="btn btn-primary w-100" id="webauthnLogin">
                            {trans key='auth.btn_fido_verify'}
                        </button>
                    </div>
                {/if}
            </div>
        </div>
    </div>
</div>

{if $method['totp']}
    {literal}
    <script>
        function readTotpCode() {
            var input = document.getElementById('otpCode');
            return input ? input.value.replace(/\D/g, '').slice(0, 6) : '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('totpForm');
            var input = document.getElementById('otpCode');
            var cells = Array.prototype.slice.call(document.querySelectorAll('[data-otp-cells] .otp-cell'));
            var lastAutoSubmitted = '';

            if (!form || !input || cells.length === 0) {
                return;
            }

            function render() {
                var code = readTotpCode();
                if (input.value !== code) {
                    input.value = code;
                }
                var focused = document.activeElement === input;
                var active = Math.min(code.length, cells.length - 1);
                cells.forEach(function (cell, i) {
                    cell.textContent = code.charAt(i);
                    cell.classList.toggle('is-active', focused && i === active);
                });
            }

            function autoSubmitWhenComplete() {
                var code = readTotpCode();
                if (code.length !== cells.length || code === lastAutoSubmitted) {
                    return;
                }
                lastAutoSubmitted = code;
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                }
            }

            ['input', 'change'].forEach(function (evt) {
                input.addEventListener(evt, function () {
                    render();
                    autoSubmitWhenComplete();
                });
            });

            ['focus', 'click', 'keyup'].forEach(function (evt) {
                input.addEventListener(evt, function () {
                    var end = input.value.length;
                    if (typeof input.setSelectionRange === 'function' &&
                        (input.selectionStart !== end || input.selectionEnd !== end)) {
                        try {
                            input.setSelectionRange(end, end);
                        } catch (e) {}
                    }
                    render();
                });
            });
            input.addEventListener('blur', render);

            input.addEventListener('paste', function (e) {
                var digits = (e.clipboardData ? e.clipboardData.getData('text') : '')
                    .replace(/\D/g, '').slice(0, cells.length);
                if (digits === '') {
                    return;
                }
                e.preventDefault();
                input.value = digits;
                render();
                autoSubmitWhenComplete();
            });

            render();
        });
    </script>
    {/literal}
{/if}

{include file='footer.tpl'}

{if $method['fido']}
<script>
    const { startAuthentication } = SimpleWebAuthnBrowser;
    document.getElementById('webauthnLogin').addEventListener('click', async () => {
        const resp = await fetch('/auth/fido');
        const options = await resp.json();
        let asseResp;
        try {
            asseResp = await startAuthentication({ optionsJSON: options });
        } catch (error) {
            document.getElementById("fail-message").innerHTML = error;
            throw error;
        }
        const verificationResp = await fetch('/auth/fido', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(asseResp),
        });
        const verificationJSON = await verificationResp.json();
        if (verificationJSON.ret === 1) {
            document.getElementById("success-message").innerHTML = verificationJSON.msg;
            if (typeof successDialog !== 'undefined' && successDialog) successDialog.show();
            window.location.href = verificationJSON.redir;
        } else {
            document.getElementById("fail-message").innerHTML = verificationJSON.msg;
            if (typeof failDialog !== 'undefined' && failDialog) failDialog.show();
        }
    });
</script>
{/if}