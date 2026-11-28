<div>
    <h4 class="mb-2">
        EPay
    </h4>
    <div class="row g-2">
        {if $public_setting['epay_alipay']}
        <div class="col-6">
            <button class="btn btn-outline-secondary w-100 py-2" type="button"
                    hx-post="/user/payment/purchase/epay" hx-swap="none"
                    hx-vals='js:{
                        invoice_id: {$invoice->id},
                        type: "alipay",
                        redir: window.location.href
                    }'>
                <img src="/images/alipay.svg" alt="Alipay" style="height: 36px; width: auto;">
            </button>
        </div>
        {/if}
        {if $public_setting['epay_wechat']}
        <div class="col-6">
            <button class="btn btn-outline-secondary w-100 py-2" type="button"
                    hx-post="/user/payment/purchase/epay" hx-swap="none"
                    hx-vals='js:{
                        invoice_id: {$invoice->id},
                        type: "wxpay",
                        redir: window.location.href
                    }'>
                <img src="/images/wechat.svg" alt="WeChat Pay" style="height: 36px; width: auto;">
            </button>
        </div>
        {/if}
        {if $public_setting['epay_qq']}
        <div class="col-6">
            <button class="btn btn-outline-secondary w-100 py-2" type="button"
                    hx-post="/user/payment/purchase/epay" hx-swap="none"
                    hx-vals='js:{
                        invoice_id: {$invoice->id},
                        type: "qqpay",
                        redir: window.location.href
                    }'>
                <img src="/images/qq.svg" alt="QQ Pay" style="height: 36px; width: auto;">
            </button>
        </div>
        {/if}
        {if $public_setting['epay_usdt']}
        <div class="col-6">
            <button class="btn btn-outline-secondary w-100 py-2" type="button"
                    hx-post="/user/payment/purchase/epay" hx-swap="none"
                    hx-vals='js:{
                        invoice_id: {$invoice->id},
                        type: "usdt",
                        redir: window.location.href
                    }'>
                <img src="/images/tether.svg" alt="USDT" style="height: 36px; width: auto;">
            </button>
        </div>
        {/if}
    </div>
</div>
