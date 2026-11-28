<div id="f2f-gateway" data-invoice-id="{$invoice->id}"
     data-error-msg="{trans key='common.unexpected_error' escape='html'}">
    <h4 class="mb-2">
        {trans key='user.alipay_f2f'}
    </h4>
    <button class="btn btn-primary w-100" id="f2fpay-button" type="button" data-f2f-start>
        <i class="ti ti-qrcode icon me-1"></i>
        {trans key='user.generate_payment_qrcode'}
    </button>
    <div class="d-none flex-column align-items-center gap-2 mt-3" data-f2f-result>
        <p class="text-secondary small mb-1 text-center">{trans key='user.alipay_mobile_scan_pay'}</p>
        <div id="f2f-qrcode" class="bg-white p-2 rounded border d-inline-block" data-f2f-qrcode></div>
        <p class="text-secondary small mb-1 text-center">{trans key='user.payment_success_refresh_prompt'}</p>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="location.reload()">
            <i class="ti ti-refresh icon me-1"></i>{trans key='common.refresh'}
        </button>
    </div>
</div>

{literal}
<script>
    (function () {
        const root = document.getElementById('f2f-gateway');
        if (!root) {
            return;
        }
        const startBtn = root.querySelector('[data-f2f-start]');
        const result = root.querySelector('[data-f2f-result]');
        const holder = root.querySelector('[data-f2f-qrcode]');

        function showGatewayError(msg) {
            const failEl = document.getElementById('fail-message');
            if (failEl && window.failDialog) {
                failEl.textContent = msg;
                window.failDialog.show();
            } else if (typeof showToast === 'function') {
                showToast(msg, 'danger');
            }
        }

        startBtn.addEventListener('click', function () {
            startBtn.disabled = true;

            fetch('/user/payment/purchase/f2f', {
                method: 'POST',
                body: new URLSearchParams({
                    invoice_id: root.dataset.invoiceId,
                }),
            })
                .then(function (resp) { return resp.json(); })
                .then(function (data) {
                    if (data.ret !== 1 || !data.qrcode) {
                        showGatewayError(data.msg || root.dataset.errorMsg);
                        startBtn.disabled = false;
                        return;
                    }
                    if (typeof QRCode === 'undefined') {
                        showGatewayError(root.dataset.errorMsg);
                        startBtn.disabled = false;
                        return;
                    }
                    holder.innerHTML = '';
                    new QRCode(holder, {
                        text: data.qrcode,
                        width: 200,
                        height: 200,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H,
                    });
                    startBtn.classList.add('d-none');
                    result.classList.remove('d-none');
                    result.classList.add('d-flex');
                })
                .catch(function () {
                    showGatewayError(root.dataset.errorMsg);
                    startBtn.disabled = false;
                });
        });
    })();
</script>
{/literal}
