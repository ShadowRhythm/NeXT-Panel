{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.create_order_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.create_order_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-sm-12 col-md-6 col-lg-9">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{trans key='user.order_content'}</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr hidden>
                                    <td>{trans key='user.product_id'}</td>
                                    <td id="product-id" class="text-end">{$product->id}</td>
                                </tr>
                                <tr>
                                    <td>{trans key='user.product_name'}</td>
                                    <td class="text-end">{$product->name}</td>
                                </tr>
                                <tr>
                                    <td>{trans key='user.product_type'}</td>
                                    <td class="text-end">{$product->type_text}</td>
                                </tr>
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    <tr>
                                        <td>{trans key='user.product_duration'}</td>
                                        <td class="text-end">{trans key='user.duration_days' days=$product->content->time}</td>
                                    </tr>
                                    <tr>
                                        <td>{trans key='user.class_duration'}</td>
                                        <td class="text-end">{trans key='user.duration_days' days=$product->content->class_time}</td>
                                    </tr>
                                    <tr>
                                        <td>{trans key='user.level'}</td>
                                        <td class="text-end">Lv. {$product->content->class}</td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'bandwidth'}
                                    <tr>
                                        <td>{trans key='user.available_traffic'}</td>
                                        <td class="text-end">{$product->content->bandwidth} GB</td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    <tr>
                                        <td>{trans key='user.speed_limit'}</td>
                                        {if $product->content->speed_limit === '0'}
                                            <td class="text-end">{trans key='user.unlimited'}</td>
                                        {else}
                                            <td class="text-end">{$product->content->speed_limit} Mbps</td>
                                        {/if}
                                    </tr>
                                    <tr>
                                        <td>{trans key='user.ip_limit'}</td>
                                        {if $product->content->ip_limit === '0'}
                                            <td class="text-end">{trans key='user.unlimited'}</td>
                                        {else}
                                            <td class="text-end">{$product->content->ip_limit}</td>
                                        {/if}
                                    </tr>
                                {/if}
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{trans key='user.price_breakdown'}</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr>
                                    <td>{trans key='user.product_price'}</td>
                                    <td class="text-end">{$product->price}</td>
                                </tr>
                                {if isset($is_upgrade) && $is_upgrade}
                                    <tr>
                                        <td>{trans key='user.old_package_credit'}</td>
                                        <td class="text-end">-{$upgrade_credit}</td>
                                    </tr>
                                    <tr>
                                        <td>{trans key='user.actual_payment'}</td>
                                        <td class="text-end" id="product-buy-total">{$upgrade_payable}</td>
                                    </tr>
                                {else}
                                    <tr>
                                        <td>{trans key='user.coupon'}</td>
                                        <td class="text-end" id="coupon-code"></td>
                                    </tr>
                                    <tr>
                                        <td>{trans key='user.discount_amount'}</td>
                                        <td class="text-end" id="product-buy-discount"></td>
                                    </tr>
                                    <tr>
                                        <td>{trans key='user.actual_payment'}</td>
                                        <td class="text-end" id="product-buy-total">{$product->price}</td>
                                    </tr>
                                {/if}
                            </table>
                        </div>
                    </div>
                    {if ! (isset($is_upgrade) && $is_upgrade)}
                    <div class="card my-3">
                        <div class="card-header">
                            <h3 class="card-title">{trans key='user.coupon'}</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="input-group mb-2">
                                    <input id="coupon" type="text" class="form-control"
                                           placeholder="{trans key='user.coupon_placeholder'}">
                                    <button class="btn" type="button"
                                            hx-post="/user/coupon" hx-swap="none"
                                            hx-vals='js:{
                                                coupon: document.getElementById("coupon").value,
                                                product_id: {$product->id},
                                            }'>
                                        {trans key='common.apply'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    {/if}
                    <div class="card my-3">
                        <div class="card-body">
                            {if $product->type === 'tabp' || $product->type === 'time'}
                                <div class="mb-3">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="auto-renew">
                                        <span class="form-check-label">{trans key='user.auto_renew_before_expire'}</span>
                                    </label>
                                </div>
                            {/if}
                            <button class="btn btn-primary w-100 my-3"
                                     hx-post="/user/order/create" hx-swap="none"
                                     hx-vals='js:{
                                         type: "product",
                                         coupon: document.getElementById("coupon") ? document.getElementById("coupon").value : "",
                                         product_id: {$product->id},
                                         auto_renew: document.getElementById("auto-renew") ? (document.getElementById("auto-renew").checked ? 1 : 0) : 0,
                                         is_upgrade: {if isset($is_upgrade) && $is_upgrade}1{else}0{/if},
                                     }'>
                                {if isset($is_upgrade) && $is_upgrade}{trans key='user.upgrade_package'}{else}{trans key='user.create_order'}{/if}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
