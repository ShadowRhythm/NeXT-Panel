{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.banned_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.banned_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-sm-12 col-lg-12">
                    <div class="card">
                        <div class="empty">
                            <div class="empty-img">
                                <i class="ti ti-circle-x icon mb-2 text-danger icon-lg" style="font-size:3.5rem;"></i>
                            </div>
                            {if $banned_reason === 'DetectBan'}
                                <p class="empty-title">{trans key='user.banned_audit_title'}</p>
                                <p class="empty-subtitle text-secondary">{trans key='user.banned_audit_desc'}</p>
                            {else}
                                <p class="empty-title">{trans key='user.banned_reason_title'}</p>
                                <p class="empty-subtitle text-secondary">{$banned_reason}</p>
                            {/if}
                            {if $is_impersonating}
                                <div class="empty-action">
                                    <button type="button" class="btn btn-warning"
                                            hx-post="/user/impersonate/leave" hx-swap="none">
                                        <i class="ti ti-arrow-back-up icon"></i>
                                        {trans key='common.leave_impersonation'}
                                    </button>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{include file='user/footer.tpl'}
