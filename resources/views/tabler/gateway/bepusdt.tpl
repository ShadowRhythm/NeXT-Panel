<div>
    <h4 class="mb-1">
        <img src="/images/tether.svg"
             style="height:22px;vertical-align:middle;margin-right:6px;" alt="USDT">
        {trans key='user.bepusdt'}
    </h4>
    <p class="text-secondary small mb-3">
        {trans key='user.bepusdt_desc'}
    </p>
    <button class="btn btn-primary w-100" type="button"
            hx-post="/user/payment/purchase/bepusdt" hx-swap="none"
            hx-vals='js:{
                price: {$invoice->price},
                invoice_id: {$invoice->id},
                type: "bepusdt",
                redir: window.location.href
            }'>
        <i class="icon ti ti-coin me-1"></i> {trans key='user.bepusdt_pay'}
    </button>
</div>
