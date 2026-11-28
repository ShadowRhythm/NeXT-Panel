{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">{trans key='user.detect_log_title'}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">{trans key='user.detect_log_subtitle'}</span>
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
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                <tr>
                                    <th class="text-nowrap">{trans key='user.event_id'}</th>
                                    <th class="text-nowrap">{trans key='user.node_id'}</th>
                                    <th class="text-nowrap">{trans key='user.node_name'}</th>
                                    <th class="text-nowrap">{trans key='user.rule_id'}</th>
                                    <th class="text-nowrap">{trans key='user.name'}</th>
                                    <th style="min-width: 10rem;">{trans key='common.description'}</th>
                                    <th style="min-width: 14rem;">{trans key='user.detect_regex'}</th>
                                    <th class="text-nowrap">{trans key='common.type'}</th>
                                    <th class="text-nowrap">{trans key='common.time'}</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach $logs as $log}
                                    <tr>
                                        <td class="text-secondary text-nowrap">#{$log->id}</td>
                                        <td class="text-nowrap">{$log->node_id}</td>
                                        <td class="text-nowrap">{$log->node_name|escape}</td>
                                        <td class="text-nowrap">{$log->list_id}</td>
                                        <td class="fw-medium text-nowrap">{$log->rule->name|escape}</td>
                                        <td class="text-secondary">{$log->rule->text|escape}</td>
                                        <td style="word-break: break-all; overflow-wrap: anywhere;">
                                            <code class="d-inline-block small user-select-all px-2 py-1 rounded bg-body-tertiary text-body"
                                                  style="word-break: break-all; overflow-wrap: anywhere; max-width: 100%;">{$log->rule->regex|default:''|escape}</code>
                                        </td>
                                        <td class="text-nowrap">{$log->rule->type|escape}</td>
                                        <td class="text-nowrap text-secondary">{$log->datetime|escape}</td>
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
