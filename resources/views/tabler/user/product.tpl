{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.product_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.product_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <ul class="nav nav-tabs nav-fill" data-bs-toggle="tabs">
                            <li class="nav-item">
                                <a href="#tabp" class="nav-link active" data-bs-toggle="tab">
                                    <i class="ti ti-rotate-360 icon"></i>
                                    &nbsp;{trans key='user.tab_time_bandwidth'}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#bandwidth" class="nav-link" data-bs-toggle="tab">
                                    <i class="ti ti-arrows-down-up icon"></i>
                                    &nbsp;{trans key='user.tab_bandwidth'}
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#time" class="nav-link" data-bs-toggle="tab">
                                    <i class="ti ti-clock icon"></i>
                                    &nbsp;{trans key='user.tab_time'}
                                </a>
                            </li>
                        </ul>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tabp">
                                    <ul>
                                        <li>{trans key='user.order_activation_notice'}</li>
                                        <li>{trans key='user.single_package_notice'}</li>
                                    </ul>
                                    {if $has_active_tabp && $active_tabp_summary}
                                        <div class="alert alert-info mb-3" role="alert">
                                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                                <div>
                                                    <div class="fw-bold">
                                                        {trans key='user.current_active_package'}: {$active_tabp_summary->product_name|escape}
                                                    </div>
                                                    <div class="text-secondary small mt-1">
                                                        {trans key='user.expire_time'}: {$active_tabp_summary->expire_date}
                                                        ({trans key='user.duration_days' days=$active_tabp_summary->remaining_days})
                                                        · {trans key='user.available_traffic'}: {$active_tabp_summary->remaining_traffic_gb} GB
                                                    </div>
                                                </div>
                                                {if $active_tabp_summary->can_renew_direct}
                                                    <a href="/user/order/create?product_id={$active_tabp_summary->product_id}&mode=renew"
                                                       class="btn btn-primary btn-sm">
                                                        <i class="ti ti-refresh icon me-1"></i>
                                                        {trans key='user.renew_current_package'}
                                                    </a>
                                                {/if}
                                            </div>
                                        </div>
                                    {/if}
                                    <div class="row row-cards">
                                        {foreach $tabp_groups as $group}
                                            {assign var="initialSku" value=$group->skus[$group->default_index]}
                                            <div class="col-xl-4 col-md-6 col-12 my-2">
                                                <div class="card card-md h-100 {if $group->is_current}border-primary{/if}"
                                                     data-plan-card
                                                     data-skus="{$group->skus_json|escape:'html'}">
                                                    <div class="card-body d-flex flex-column text-center">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <span class="badge bg-azure-lt">Lv. {$initialSku.class}</span>
                                                            <div class="d-flex gap-1">
                                                                {if $group->is_current}
                                                                    <span class="badge bg-green-lt">{trans key='user.current_plan_badge'}</span>
                                                                {/if}
                                                                <span class="badge bg-orange-lt {if $initialSku.discount_percent <= 0}d-none{/if}" data-plan-discount>
                                                                    {trans key='user.save_percent' percent=$initialSku.discount_percent}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="h3 fw-bold mb-1">{$group->name|escape}</div>
                                                        <div class="text-secondary small mb-3" data-plan-sku-name>{$initialSku.name|escape}</div>

                                                        <div class="btn-group flex-wrap justify-content-center mb-3" role="group">
                                                            {foreach $group->skus as $idx => $sku}
                                                                <button type="button"
                                                                        class="btn btn-sm {if $idx === $group->default_index}btn-primary{else}btn-azure{/if}"
                                                                        data-cycle-btn
                                                                        data-sku-index="{$idx}">
                                                                    {$sku.pill_label|escape}
                                                                </button>
                                                            {/foreach}
                                                        </div>

                                                        <div class="my-2">
                                                            <div class="display-6 fw-bold" data-plan-price>
                                                                {if $initialSku.action_mode === 'upgrade'}
                                                                    ¥{$initialSku.upgrade_price}
                                                                    <span class="fs-4 text-secondary text-decoration-line-through ms-1">¥{$initialSku.price}</span>
                                                                {else}
                                                                    ¥{$initialSku.price}
                                                                {/if}
                                                            </div>
                                                            <div class="text-secondary small mt-1" data-plan-subprice>
                                                                {if $initialSku.days > 31}
                                                                    {trans key='user.approx_monthly_price' price=$initialSku.monthly_price}
                                                                {/if}
                                                            </div>
                                                        </div>

                                                        <div class="list-group list-group-flush my-3">
                                                            <div class="list-group-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-secondary">{trans key='user.level'}</span>
                                                                    <span class="fw-medium" data-plan-class>Lv. {$initialSku.class}</span>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-secondary">{trans key='user.class_duration'}</span>
                                                                    <span class="fw-medium" data-plan-days>{trans key='user.duration_days' days=$initialSku.days}</span>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-secondary">{trans key='user.available_traffic'}</span>
                                                                    <span class="fw-medium" data-plan-bandwidth>{$initialSku.bandwidth} GB</span>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-secondary">{trans key='user.speed_limit'}</span>
                                                                    <span class="fw-medium" data-plan-speed>
                                                                        {if $initialSku.speed_limit === '0'}{trans key='user.unlimited'}{else}{$initialSku.speed_limit} Mbps{/if}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-secondary">{trans key='user.ip_limit'}</span>
                                                                    <span class="fw-medium" data-plan-ip>
                                                                        {if $initialSku.ip_limit === '0'}{trans key='user.unlimited'}{else}{$initialSku.ip_limit}{/if}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mt-auto" data-plan-actions></div>
                                                    </div>
                                                </div>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="tab-pane show" id="bandwidth">
                                    <ul>
                                        <li>{trans key='user.order_activation_notice'}</li>
                                        <li>{trans key='user.bandwidth_package_notice'}</li>
                                        <li>{trans key='user.unused_traffic_reset_notice'}</li>
                                    </ul>
                                    <div class="row">
                                        {foreach $bandwidths as $bandwidth}
                                            <div class="col-md-3 col-sm-12 my-3">
                                                <div class="card card-md">
                                                    <div class="card-body text-center">
                                                        <div id="product-{$bandwidth->id}-name"
                                                             class="text-uppercase text-secondary font-weight-medium">
                                                            {$bandwidth->name}</div>
                                                        <div id="product-{$bandwidth->id}-price"
                                                             class="display-6 my-3">
                                                            <p class="fw-bold">{$bandwidth->price}</p>
                                                            <i class="ti ti-currency-yuan"></i>
                                                        </div>
                                                        <div class="list-group list-group-flush">
                                                            <div class="list-group-item">
                                                                <div class="row align-items-center">
                                                                    <div class="col text-truncate">
                                                                        <div class="text-reset d-block">{$bandwidth->content->bandwidth}
                                                                            GB
                                                                        </div>
                                                                        <div class="d-block text-secondary text-truncate mt-n1">
                                                                            {trans key='user.available_traffic'}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row g-2">
                                                            {if $bandwidth->stock === -1 || $bandwidth->stock > 0}
                                                                <div class="col">
                                                                    <a href="/user/order/create?product_id={$bandwidth->id}"
                                                                       class="btn btn-primary w-100 my-3">{trans key='user.buy'}</a>
                                                                </div>
                                                            {else}
                                                                <div class="col">
                                                                    <a href="" class="btn btn-primary w-100 my-3"
                                                                       disabled>{trans key='user.out_of_stock'}</a>
                                                                </div>
                                                            {/if}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                                <div class="tab-pane show" id="time">
                                    <div class="row">
                                        {foreach $times as $time}
                                            <div class="col-md-3 col-sm-12 my-3">
                                                <div class="card card-md">
                                                    <div class="card-body text-center">
                                                        <div id="product-{$time->id}-name"
                                                             class="text-uppercase text-secondary font-weight-medium">
                                                            {$time->name}
                                                        </div>
                                                        <div id="product-{$time->id}-price"
                                                             class="display-6 my-3"><p
                                                                    class="fw-bold">{$time->price}</p>
                                                            <i class="ti ti-currency-yuan"></i>
                                                        </div>
                                                        <div class="list-group list-group-flush">
                                                            <div class="list-group-item">
                                                                <div class="row align-items-center">
                                                                    <div class="col text-truncate">
                                                                        <div class="text-reset d-block">
                                                                            Lv. {$time->content->class}</div>
                                                                        <div class="d-block text-secondary text-truncate mt-n1">
                                                                            {trans key='user.level'}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="row align-items-center">
                                                                    <div class="col text-truncate">
                                                                        <div class="text-reset d-block">{trans key='user.duration_days' days=$time->content->class_time}</div>
                                                                        <div class="d-block text-secondary text-truncate mt-n1">
                                                                            {trans key='user.class_duration'}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="row align-items-center">
                                                                    <div class="col text-truncate">
                                                                        {if $time->content->speed_limit === '0'}
                                                                            <div class="text-reset d-block">{trans key='user.unlimited'}</div>
                                                                        {else}
                                                                            <div class="text-reset d-block">{$time->content->speed_limit}
                                                                                Mbps
                                                                            </div>
                                                                        {/if}
                                                                        <div class="d-block text-secondary text-truncate mt-n1">
                                                                            {trans key='user.speed_limit'}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="list-group-item">
                                                                <div class="row align-items-center">
                                                                    <div class="col text-truncate">
                                                                        {if $time->content->ip_limit === '0'}
                                                                            <div class="text-reset d-block">{trans key='user.unlimited'}</div>
                                                                        {else}
                                                                            <div class="text-reset d-block">{$time->content->ip_limit}</div>
                                                                        {/if}
                                                                        <div class="d-block text-secondary text-truncate mt-n1">
                                                                            {trans key='user.ip_limit'}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row g-2">
                                                            {if $time->stock === -1 || $time->stock > 0}
                                                                <div class="col">
                                                                    <a href="/user/order/create?product_id={$time->id}"
                                                                       class="btn btn-primary w-100 my-3">{trans key='user.buy'}</a>
                                                                </div>
                                                            {else}
                                                                <div class="col">
                                                                    <a href="" class="btn btn-primary w-100 my-3"
                                                                       disabled>{trans key='user.out_of_stock'}</a>
                                                                </div>
                                                            {/if}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const I18N = {
                unlimited: '{trans key="user.unlimited" escape="js"}',
                durationDays: '{trans key="user.duration_days" days="__DAYS__" escape="js"}',
                savePercent: '{trans key="user.save_percent" percent="__PERCENT__" escape="js"}',
                approxMonthly: '{trans key="user.approx_monthly_price" price="__PRICE__" escape="js"}',
                buy: '{trans key="user.buy" escape="js"}',
                outOfStock: '{trans key="user.out_of_stock" escape="js"}',
                upgradePackage: '{trans key="user.upgrade_package" escape="js"}',
                upgradeCreditPrefix: '{trans key="user.upgrade_credit_prefix" escape="js"}',
                buyQueuedAfterExpire: '{trans key="user.buy_queued_after_expire" escape="js"}',
                renewCurrentPackage: '{trans key="user.renew_current_package" escape="js"}',
                switchCycleQueued: '{trans key="user.switch_cycle_queued" escape="js"}',
                downgradeQueued: '{trans key="user.downgrade_queued" escape="js"}',
                queuedHint: '{trans key="user.queued_activation_hint" escape="js"}',
                upgradeImmediateHint: '{trans key="user.upgrade_immediate_hint" escape="js"}'
            };

            function renderActions(container, sku) {
                if (sku.stock === 0) {
                    container.innerHTML = '<button type="button" class="btn btn-primary w-100" disabled>' + I18N.outOfStock + '</button>';
                    return;
                }

                let html = '';
                if (sku.action_mode === 'upgrade') {
                    html += '<a href="/user/order/create?product_id=' + sku.id + '&upgrade=1" class="btn btn-warning w-100 mb-2">' +
                        I18N.upgradePackage + ' (' + I18N.upgradeCreditPrefix + '¥' + sku.upgrade_credit + ')' +
                        '</a>';
                    html += '<a href="/user/order/create?product_id=' + sku.id + '" class="btn btn-azure btn-sm w-100">' +
                        I18N.buyQueuedAfterExpire +
                        '</a>';
                    html += '<div class="text-secondary small mt-2">' + I18N.upgradeImmediateHint + '</div>';
                } else if (sku.action_mode === 'renew') {
                    html += '<a href="/user/order/create?product_id=' + sku.id + '&mode=renew" class="btn btn-primary w-100">' +
                        I18N.renewCurrentPackage +
                        '</a>';
                    html += '<div class="text-secondary small mt-2">' + I18N.queuedHint + '</div>';
                } else if (sku.action_mode === 'cycle_switch') {
                    if (sku.can_upgrade) {
                        html += '<a href="/user/order/create?product_id=' + sku.id + '&upgrade=1" class="btn btn-warning w-100 mb-2">' +
                            I18N.upgradePackage + ' (' + I18N.upgradeCreditPrefix + '¥' + sku.upgrade_credit + ')' +
                            '</a>';
                        html += '<a href="/user/order/create?product_id=' + sku.id + '&mode=cycle_switch" class="btn btn-azure btn-sm w-100">' +
                            I18N.switchCycleQueued +
                            '</a>';
                    } else {
                        html += '<a href="/user/order/create?product_id=' + sku.id + '&mode=cycle_switch" class="btn btn-primary w-100">' +
                            I18N.switchCycleQueued +
                            '</a>';
                        html += '<div class="text-secondary small mt-2">' + I18N.queuedHint + '</div>';
                    }
                } else if (sku.action_mode === 'downgrade') {
                    html += '<a href="/user/order/create?product_id=' + sku.id + '&mode=downgrade" class="btn btn-azure w-100">' +
                        I18N.downgradeQueued +
                        '</a>';
                    html += '<div class="text-secondary small mt-2">' + I18N.queuedHint + '</div>';
                } else {
                    html += '<a href="/user/order/create?product_id=' + sku.id + '" class="btn btn-primary w-100">' +
                        I18N.buy +
                        '</a>';
                }
                container.innerHTML = html;
            }

            function selectPlanSku(card, skuIndex) {
                const skus = JSON.parse(card.getAttribute('data-skus') || '[]');
                const sku = skus[skuIndex];
                if (!sku) return;

                card.querySelectorAll('[data-cycle-btn]').forEach(function (btn) {
                    const idx = Number(btn.getAttribute('data-sku-index'));
                    if (idx === skuIndex) {
                        btn.classList.remove('btn-azure');
                        btn.classList.add('btn-primary');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-azure');
                    }
                });

                const skuNameEl = card.querySelector('[data-plan-sku-name]');
                if (skuNameEl) skuNameEl.textContent = sku.name;

                const priceEl = card.querySelector('[data-plan-price]');
                if (priceEl) {
                    if (sku.action_mode === 'upgrade') {
                        priceEl.innerHTML = '¥' + sku.upgrade_price +
                            ' <span class="fs-4 text-secondary text-decoration-line-through ms-1">¥' + sku.price + '</span>';
                    } else {
                        priceEl.textContent = '¥' + sku.price;
                    }
                }

                const subPriceEl = card.querySelector('[data-plan-subprice]');
                if (subPriceEl) {
                    subPriceEl.textContent = sku.days > 31
                        ? I18N.approxMonthly.replace('__PRICE__', String(sku.monthly_price))
                        : '';
                }

                const discountEl = card.querySelector('[data-plan-discount]');
                if (discountEl) {
                    if (sku.discount_percent > 0) {
                        discountEl.textContent = I18N.savePercent.replace('__PERCENT__', String(sku.discount_percent));
                        discountEl.classList.remove('d-none');
                    } else {
                        discountEl.classList.add('d-none');
                    }
                }

                const classEl = card.querySelector('[data-plan-class]');
                if (classEl) classEl.textContent = 'Lv. ' + sku.class;

                const daysEl = card.querySelector('[data-plan-days]');
                if (daysEl) daysEl.textContent = I18N.durationDays.replace('__DAYS__', String(sku.days));

                const bwEl = card.querySelector('[data-plan-bandwidth]');
                if (bwEl) bwEl.textContent = sku.bandwidth + ' GB';

                const speedEl = card.querySelector('[data-plan-speed]');
                if (speedEl) {
                    speedEl.textContent = sku.speed_limit === '0' ? I18N.unlimited : (sku.speed_limit + ' Mbps');
                }

                const ipEl = card.querySelector('[data-plan-ip]');
                if (ipEl) {
                    ipEl.textContent = sku.ip_limit === '0' ? I18N.unlimited : String(sku.ip_limit);
                }

                const actionsEl = card.querySelector('[data-plan-actions]');
                if (actionsEl) {
                    renderActions(actionsEl, sku);
                }
            }

            document.querySelectorAll('[data-plan-card]').forEach(function (card) {
                const buttons = card.querySelectorAll('[data-cycle-btn]');
                let initialIdx = 0;
                buttons.forEach(function (btn) {
                    if (btn.classList.contains('btn-primary')) {
                        initialIdx = Number(btn.getAttribute('data-sku-index')) || 0;
                    }
                    btn.addEventListener('click', function () {
                        selectPlanSku(card, Number(btn.getAttribute('data-sku-index')) || 0);
                    });
                });
                selectPlanSku(card, initialIdx);
            });
        })();
    </script>

    {include file='user/footer.tpl'}
