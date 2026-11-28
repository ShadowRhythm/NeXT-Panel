{include file='user/header.tpl'}

<!-- Audit rules are used to prevent DMCA and email Spam, not for building walls for users. -->
<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.detect_rules_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.detect_rules_subtitle'}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-vcenter card-table">
                                <thead>
                                <tr>
                                    <th class="w-1 text-nowrap">ID</th>
                                    <th class="text-nowrap" style="min-width: 7.5rem;">{trans key='user.name'}</th>
                                    <th style="min-width: 11rem;">{trans key='common.description'}</th>
                                    <th style="min-width: 16rem; width: 45%;">{trans key='user.detect_regex'}</th>
                                    <th class="w-1 text-nowrap text-end">{trans key='common.type'}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach $rules as $rule}
                                    <tr>
                                        <td class="text-secondary text-nowrap">#{$rule->id}</td>
                                        <td class="fw-medium text-nowrap">{$rule->name|escape}</td>
                                        <td class="text-secondary">{$rule->text|escape}</td>
                                        <td style="word-break: break-all; overflow-wrap: anywhere;">
                                            <code class="d-inline-block small user-select-all px-2 py-1 rounded bg-body-tertiary text-body"
                                                  style="word-break: break-all; overflow-wrap: anywhere; max-width: 100%;">{$rule->regex|escape}</code>
                                        </td>
                                        <td class="text-nowrap text-end">
                                            {if $rule->type === 1}
                                                <span class="badge bg-blue-lt">{trans key='user.detect_type_packet_plaintext'}</span>
                                            {elseif $rule->type === 2}
                                                <span class="badge bg-purple-lt">{trans key='user.detect_type_packet_hex'}</span>
                                            {else}
                                                <span class="badge bg-secondary-lt">{$rule->type|escape}</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                        <div class="divide-y d-md-none">
                            {foreach $rules as $rule}
                                <div class="p-3">
                                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                        <div class="fw-medium">
                                            <span class="text-secondary me-1">#{$rule->id}</span>
                                            <span>{$rule->name|escape}</span>
                                        </div>
                                        {if $rule->type === 1}
                                            <span class="badge bg-blue-lt text-nowrap">{trans key='user.detect_type_packet_plaintext'}</span>
                                        {elseif $rule->type === 2}
                                            <span class="badge bg-purple-lt text-nowrap">{trans key='user.detect_type_packet_hex'}</span>
                                        {else}
                                            <span class="badge bg-secondary-lt text-nowrap">{$rule->type|escape}</span>
                                        {/if}
                                    </div>
                                    <div class="text-secondary small mb-2">{$rule->text|escape}</div>
                                    <code class="d-block small user-select-all px-2 py-1 rounded bg-body-tertiary text-body"
                                          style="word-break: break-all; overflow-wrap: anywhere;">{$rule->regex|escape}</code>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
