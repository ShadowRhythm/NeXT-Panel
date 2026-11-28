<div>
    <h4 class="mb-2">
        Cryptomus
    </h4>
    <button class="btn btn-primary w-100" type="button"
            hx-post="/user/payment/purchase/cryptomus" hx-swap="none"
            hx-vals='js:{
                price: {$invoice->price},
                invoice_id: {$invoice->id},
                type: "cryptomus",
                redir: window.location.href
            }'>
        <i class="icon ti ti-coin me-1"></i> {trans key='user.pay'}
    </button>
</div>
