<div id="smogate-gateway" data-invoice-id="{$invoice->id}" data-amount="{$invoice->price}"
     data-error-msg="{trans key='common.unexpected_error' escape='html'}">
    <h4 class="mb-2">
        {trans key='user.alipay_f2f'}
    </h4>
    <button class="btn btn-primary w-100" id="smogate-button" type="button" data-smogate-start>
        <i class="ti ti-qrcode icon me-1"></i>
        {trans key='user.recharge'}
    </button>
    <div class="d-none flex-column align-items-center gap-2 mt-3" data-smogate-result>
        <p class="text-secondary small mb-1 text-center">{trans key='user.alipay_scan_pay'}</p>
        <div id="smogate-qrcode" class="bg-white p-2 rounded border d-inline-block" data-smogate-qrcode></div>
        <p class="text-secondary small mb-1 text-center">{trans key='user.payment_success_refresh_prompt'}</p>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="location.reload()">
            <i class="ti ti-refresh icon me-1"></i>{trans key='common.refresh'}
        </button>
    </div>
</div>

{literal}
<script>
    (function () {
        const root = document.getElementById('smogate-gateway');
        if (!root) {
            return;
        }
        const startBtn = root.querySelector('[data-smogate-start]');
        const result = root.querySelector('[data-smogate-result]');
        const holder = root.querySelector('[data-smogate-qrcode]');

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

            fetch('/user/payment/purchase/smogate', {
                method: 'POST',
                body: new URLSearchParams({
                    amount: root.dataset.amount,
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
                    if (data.type === 'url') {
                        window.location.href = data.qrcode;
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
