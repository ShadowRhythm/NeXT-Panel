{include file='admin/header.tpl'}

<link rel="stylesheet" href="//{$config['jsdelivr_url']}/npm/flatpickr/dist/flatpickr.min.css">
{if $user->is_dark_mode}
    <link rel="stylesheet" href="//{$config['jsdelivr_url']}/npm/flatpickr/dist/themes/dark.min.css">
{/if}
<script src="//{$config['jsdelivr_url']}/npm/flatpickr"></script>
<script src="//{$config['jsdelivr_url']}/npm/flatpickr/dist/l10n/zh.js"></script>

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">优惠码</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">
                            查看并管理优惠码
                        </span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                           data-bs-target="#create-dialog">
                            <i class="icon ti ti-plus"></i>
                            创建
                        </a>
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
                            <table id="data-table" class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    {foreach $details['field'] as $key => $value}
                                        <th>{$value}</th>
                                    {/foreach}
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="create-dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">优惠码内容</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {foreach $details['create_dialog'] as $detail}
                        {if $detail['type'] === 'input'}
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">{$detail['info']}</label>
                                <div class="col">
                                    <input id="{$detail['id']}" type="text" class="form-control"
                                           placeholder="{$detail['placeholder']}">
                                </div>
                            </div>
                        {/if}
                        {if $detail['type'] === 'textarea'}
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">{$detail['info']}</label>
                                <textarea id="{$detail['id']}" class="col form-control" rows="{$detail['rows']}"
                                          placeholder="{$detail['placeholder']}"></textarea>
                            </div>
                        {/if}
                        {if $detail['type'] === 'select'}
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">{$detail['info']}</label>
                                <div class="col">
                                    <select id="{$detail['id']}" class="col form-select">
                                        {foreach $detail['select'] as $key => $value}
                                            <option value="{$key}">{$value}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                    <div class="form-group mb-3 row">
                        <label class="form-label col-3 col-form-label">过期时间（留空则为不限制）</label>
                        <div class="col">
                            <div class="input-group">
                                <input id="expire_time" type="text" class="form-control"
                                       placeholder="">
                                <button class="btn btn-outline-secondary" type="button" onclick="createExpirePicker.clear()">清空</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">取消</button>
                    <button id="create-button" onclick="createCoupon()"
                            type="button" class="btn btn-primary" data-bs-dismiss="modal">创建
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="edit-dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">编辑优惠码</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input id="edit_coupon_id" type="hidden">
                    {foreach $details['edit_dialog'] as $detail}
                        {if $detail['type'] === 'input'}
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">{$detail['info']}</label>
                                <div class="col">
                                    <input id="edit_{$detail['id']}" type="text" class="form-control"
                                           placeholder="{$detail['placeholder']}">
                                </div>
                            </div>
                        {/if}
                        {if $detail['type'] === 'textarea'}
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">{$detail['info']}</label>
                                <textarea id="edit_{$detail['id']}" class="col form-control" rows="{$detail['rows']}"
                                          placeholder="{$detail['placeholder']}"></textarea>
                            </div>
                        {/if}
                        {if $detail['type'] === 'select'}
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">{$detail['info']}</label>
                                <div class="col">
                                    <select id="edit_{$detail['id']}" class="col form-select">
                                        {foreach $detail['select'] as $key => $value}
                                            <option value="{$key}">{$value}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                    <div class="form-group mb-3 row">
                        <label class="form-label col-3 col-form-label">过期时间（留空则为不限制）</label>
                        <div class="col">
                            <div class="input-group">
                                <input id="edit_expire_time" type="text" class="form-control"
                                       placeholder="">
                                <button class="btn btn-outline-secondary" type="button" onclick="editExpirePicker.clear()">清空</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">取消</button>
                    <button id="edit-button" onclick="updateCoupon()"
                            type="button" class="btn btn-primary">提交
                    </button>
                </div>
            </div>
        </div>
    </div>

    {include file='datatable.tpl'}

    <script>
        let createExpirePicker = flatpickr("#expire_time", {
            enableTime: true,
            dateFormat: "U",
            time_24hr: true,
            minDate: "today",
            locale: "zh"
        });

        let editExpirePicker = flatpickr("#edit_expire_time", {
            enableTime: true,
            dateFormat: "U",
            time_24hr: true,
            locale: "zh"
        });

        tableConfig.ajax = {
            url: '/admin/coupon/ajax',
            type: 'POST',
            dataSrc: 'coupons'
        };
        tableConfig.order = [
            [1, 'desc']
        ];
        tableConfig.columnDefs = [
            {
                targets: [0],
                orderable: false
            }
        ];

        let table = new DataTable('#data-table', tableConfig);

        let editModal = null;
        function getEditModal() {
            if (!editModal) {
                let M = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
                    || (typeof tabler !== 'undefined' && (tabler.Modal || (tabler.bootstrap && tabler.bootstrap.Modal)));
                if (M) {
                    editModal = new M(document.getElementById('edit-dialog'));
                }
            }
            return editModal;
        }

        function editCoupon(coupon_id) {
            $.ajax({
                url: "/admin/coupon/" + coupon_id,
                type: 'GET',
                dataType: "json",
                success: function (data) {
                    if (data.ret === 1) {
                        $('#edit_coupon_id').val(data.coupon.id);
                        {foreach $details['edit_dialog'] as $detail}
                        $('#edit_{$detail['id']}').val(data.coupon['{$detail['id']}']);
                        {/foreach}
                        if (data.coupon.expire_time > 0) {
                            editExpirePicker.setDate(new Date(data.coupon.expire_time * 1000));
                        } else {
                            editExpirePicker.clear();
                        }
                        let modal = getEditModal();
                        if (modal) {
                            modal.show();
                        } else {
                            $('#edit-dialog').modal('show');
                        }
                    } else {
                        $('#fail-message').text(data.msg);
                        failDialog.show();
                    }
                }
            });
        }

        function updateCoupon() {
            let coupon_id = $('#edit_coupon_id').val();
            $.ajax({
                url: '/admin/coupon/' + coupon_id,
                type: 'PUT',
                dataType: "json",
                data: {
                    {foreach $details['edit_dialog'] as $detail}
                    {$detail['id']}: $('#edit_{$detail['id']}').val(),
                    {/foreach}
                    expire_time: $('#edit_expire_time').val(),
                },
                success: function (data) {
                    if (data.ret === 1) {
                        let modal = getEditModal();
                        if (modal) {
                            modal.hide();
                        } else {
                            $('#edit-dialog').modal('hide');
                        }
                        $('#success-message').text(data.msg);
                        successDialog.show();
                        reloadTableAjax();
                    } else {
                        $('#fail-message').text(data.msg);
                        failDialog.show();
                    }
                }
            });
        }

        function loadTable() {
            table;
        }

        function createCoupon() {
            $.ajax({
                url: '/admin/coupon',
                type: 'POST',
                dataType: "json",
                data: {
                    {foreach $details['create_dialog'] as $detail}
                    {$detail['id']}: $('#{$detail['id']}').val(),
                    {/foreach}
                    expire_time: $('#expire_time').val(),
                },
                success: function (data) {
                    if (data.ret === 1) {
                        $('#success-message').text(data.msg);
                        successDialog.show();
                        reloadTableAjax();
                    } else {
                        $('#fail-message').text(data.msg);
                        failDialog.show();
                    }
                }
            })
        }

        function deleteCoupon(coupon_id) {
            $('#notice-message').text('确定删除此优惠码？');
            noticeDialog.show();
            $('#notice-confirm').off('click').on('click', function () {
                $.ajax({
                    url: "/admin/coupon/" + coupon_id,
                    type: 'DELETE',
                    dataType: "json",
                    success: function (data) {
                        if (data.ret === 1) {
                            $('#success-message').text(data.msg);
                            successDialog.show();
                            reloadTableAjax();
                        } else {
                            $('#fail-message').text(data.msg);
                            failDialog.show();
                        }
                    }
                })
            });
        }

        function disableCoupon(coupon_id) {
            $('#notice-message').text('确定禁用此优惠码？');
            noticeDialog.show();
            $('#notice-confirm').off('click').on('click', function () {
                $.ajax({
                    url: "/admin/coupon/" + coupon_id + "/disable",
                    type: 'POST',
                    dataType: "json",
                    success: function (data) {
                        if (data.ret === 1) {
                            $('#success-message').text(data.msg);
                            successDialog.show();
                            reloadTableAjax();
                        } else {
                            $('#fail-message').text(data.msg);
                            failDialog.show();
                        }
                    }
                })
            });
        }

        function reloadTableAjax() {
            table.ajax.reload(null, false);
        }

        loadTable();
    </script>

    {include file='admin/footer.tpl'}
