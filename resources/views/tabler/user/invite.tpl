{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.invite_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.invite_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="row row-deck row-cards">
                        <div class="col-sm-12 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title">{trans key='user.invite_rules'}</h3>
                                    <ul>
                                        <li>{trans key='user.invite_reward_rate_desc' rate="<code>{$invite_reward_rate}</code>"}</li>
                                        <li>{trans key='user.invite_reward_exception'}</li>
                                    </ul>
                                    <p>{trans key='user.total_referral_reward' amount="<code>{$paybacks_sum}</code>"}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title">{trans key='user.invitation_link'}</h3>
                                    {if $can_invite}
                                    <input class="form-control" id="invite-url" value="{$invite_url|escape}" disabled>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex">
                                        <button class="btn text-red btn-link"
                                                hx-post="/user/invite/reset" hx-swap="none">
                                            {trans key='common.reset'}
                                        </button>
                                        <button data-clipboard-text="{$invite_url|escape}"
                                           class="copy btn btn-primary ms-auto">{trans key='common.copy'}</button>
                                    </div>
                                </div>
                                {else}
                                <p>{trans key='user.no_invite_permission'}</p>
                                <ul class="mb-0">
                                    {foreach $invite_reasons as $reason}
                                        <li>{$reason|escape}</li>
                                    {/foreach}
                                </ul>
                            </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 my-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{trans key='user.rebate_history'}</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    <th>{trans key='user.record_id'}</th>
                                    <th>{trans key='user.invitee_id'}</th>
                                    <th>{trans key='user.invitee_name'}</th>
                                    <th>{trans key='user.rebate_amount'}</th>
                                    <th>{trans key='user.rebate_time'}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach $paybacks as $payback}
                                    <tr>
                                        <td>{$payback->id}</td>
                                        <td>{$payback->userid}</td>
                                        <td>{$payback->user_name|escape}</td>
                                        <td>{$payback->ref_get} {trans key='user.cny'}</td>
                                        <td>{$payback->datetime}</td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
