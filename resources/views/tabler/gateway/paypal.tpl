<script src="https://www.paypal.com/sdk/js?client-id={$public_setting['paypal_client_id']}&currency={$public_setting['paypal_currency']}"></script>

<div id="paypal-gateway" data-invoice-id="{$invoice->id}" data-jump-delay="{$config['jump_delay']}">
    <h4 class="mb-2">
        PayPal
    </h4>
    <div id="paypal-button-container"></div>
</div>

{literal}
<script>
    (function () {
        const root = document.getElementById('paypal-gateway');
        if (!root || typeof paypal === 'undefined') {
            return;
        }

        paypal.Buttons({
            createOrder() {
                return fetch('/user/payment/purchase/paypal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        invoice_id: Number(root.dataset.invoiceId),
                    }),
                })
                    .then((response) => response.json())
                    .then((order) => {
                        if (!order || !order.id) {
                            throw new Error(order && order.msg ? order.msg : 'PayPal order creation failed');
                        }
                        return order.id;
                    });
            },
            onApprove() {
                setTimeout(function () {
                    window.location.href = '/user/invoice';
                }, Number(root.dataset.jumpDelay) || 0);
            },
        }).render('#paypal-button-container');
    })();
</script>
{/literal}
