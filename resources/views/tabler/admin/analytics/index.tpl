{include file='admin/header.tpl'}

{assign var="current_month" value=$smarty.now|date_format:"%Y-%m"}
{assign var="last_month" value=($smarty.now - 2592000)|date_format:"%Y-%m"}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">运营分析</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">站点运营状态与多维趋势分析</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">

            <!-- 全局时间维度切换器 + 核心 KPI 摘要 -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary active" id="btn-day">日</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-month">月</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-year">年</button>
                        </div>
                        <div id="day-shortcuts" class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary day-shortcut" data-month="{$current_month}">本月</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary day-shortcut" data-month="{$last_month}">上月</button>
                            <!-- 自定义月份选择器 -->
                            <div class="dropdown" id="month-picker-wrap">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1"
                                        type="button" id="month-picker-btn" data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside" aria-expanded="false">
                                    <i class="ti ti-calendar icon"></i>
                                    <span id="month-picker-label">{$current_month|date_format:"%Y年%-m月"}</span>
                                </button>
                                <div class="dropdown-menu p-3 shadow" style="min-width:260px;" id="month-picker-panel">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <button class="btn btn-sm btn-ghost-secondary px-2" id="picker-prev-year" type="button">
                                            <i class="ti ti-chevron-left"></i>
                                        </button>
                                        <span class="fw-semibold fs-4" id="picker-year-label">2026</span>
                                        <button class="btn btn-sm btn-ghost-secondary px-2" id="picker-next-year" type="button">
                                            <i class="ti ti-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div class="row g-1" id="picker-month-grid"></div>
                                    <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                                        <button class="btn btn-sm btn-link text-secondary px-0" id="picker-clear" type="button">清除</button>
                                        <button class="btn btn-sm btn-link px-0" id="picker-this-month" type="button">本月</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="month-shortcuts" class="d-flex align-items-center gap-2 d-none">
                            <span class="text-secondary small">最近 12 个月</span>
                        </div>
                        <div id="year-shortcuts" class="d-flex align-items-center gap-2 d-none">
                            <button class="btn btn-sm btn-ghost-secondary px-2" id="year-prev" type="button"><i class="ti ti-chevron-left"></i></button>
                            <span class="fw-semibold" id="year-label" style="min-width:4rem;text-align:center"></span>
                            <button class="btn btn-sm btn-ghost-secondary px-2" id="year-next" type="button"><i class="ti ti-chevron-right"></i></button>
                        </div>
                        <!-- 右侧 KPI 摘要 -->
                        <div class="ms-auto d-flex align-items-center gap-4 flex-wrap text-nowrap">
                            <div>
                                <span class="text-secondary small">期间流水</span>
                                <span class="ms-1 fw-medium" id="kpi-income">—</span>
                                <span class="ms-1 badge d-none" id="kpi-income-diff"></span>
                            </div>
                            <div>
                                <span class="text-secondary small">新增用户</span>
                                <span class="ms-1 fw-medium" id="kpi-users">—</span>
                            </div>
                            <div>
                                <span class="text-secondary small">期间流量</span>
                                <span class="ms-1 fw-medium" id="kpi-traffic">—</span>
                            </div>
                            <div>
                                <span class="text-secondary small">当前活跃</span>
                                <span class="ms-1 fw-medium" id="kpi-active">—</span>
                            </div>
                            <div>
                                <span class="text-secondary small">期间签到</span>
                                <span class="ms-1 fw-medium" id="kpi-checkin">—</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 维度分页标签 -->
            <ul class="nav nav-tabs mb-3" id="analytics-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a href="#tab-overview" class="nav-link active" data-bs-toggle="tab" data-tab="overview" role="tab">
                        <i class="ti ti-chart-line icon me-1"></i>总览趋势
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-revenue" class="nav-link" data-bs-toggle="tab" data-tab="revenue" role="tab">
                        <i class="ti ti-coin icon me-1"></i>营收与转化
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-traffic" class="nav-link" data-bs-toggle="tab" data-tab="traffic" role="tab">
                        <i class="ti ti-activity icon me-1"></i>流量与客户端
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a href="#tab-retention" class="nav-link" data-bs-toggle="tab" data-tab="retention" role="tab">
                        <i class="ti ti-users icon me-1"></i>留存与推广
                    </a>
                </li>
            </ul>

            <div class="tab-content">

                <!-- ════════════════════ Tab 1：总览趋势 ════════════════════ -->
                <div class="tab-pane active show" id="tab-overview" role="tabpanel">
                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">收入趋势</h3></div>
                                <div class="card-body">
                                    <div id="chart-income" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">新增用户</h3></div>
                                <div class="card-body">
                                    <div id="chart-users" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">用户等级分布</h3></div>
                                <div class="card-body">
                                    <div id="chart-class" style="min-height: 350px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">活跃与每日签到趋势</h3>
                                    <div class="card-actions text-secondary small">每日凌晨快照汇总</div>
                                </div>
                                <div class="card-body">
                                    <div id="chart-activity" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">流量趋势</h3></div>
                                <div class="card-body">
                                    <div id="chart-traffic" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">商品销量排行</h3></div>
                                <div class="card-body">
                                    <div id="chart-products" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════ Tab 2：营收与转化 ════════════════════ -->
                <div class="tab-pane" id="tab-revenue" role="tabpanel">
                    <div class="row row-cards mb-3">
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">付费订单 / 客单价 (ARPPU)</div>
                                    <div class="h2 mb-1" id="rev-arppu">—</div>
                                    <div class="text-secondary small" id="rev-orders">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">期间新注册付费转化</div>
                                    <div class="h2 mb-1" id="rev-conv-rate">—</div>
                                    <div class="text-secondary small" id="rev-conv-detail">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">新购 vs 复购续费</div>
                                    <div class="h2 mb-1" id="rev-mix-ratio">—</div>
                                    <div class="text-secondary small" id="rev-mix-detail">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">自动续费健康度（实时）</div>
                                    <div class="h2 mb-1" id="rev-autorenew-failed">—</div>
                                    <div class="text-secondary small" id="rev-autorenew-enabled">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">支付网关占比</h3></div>
                                <div class="card-body">
                                    <div id="chart-gateways" style="min-height: 280px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">商品类型收入构成</h3></div>
                                <div class="card-body">
                                    <div id="chart-order-types" style="min-height: 280px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">账单结算方式</h3></div>
                                <div class="card-body">
                                    <div id="chart-invoice-methods" style="min-height: 280px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">优惠码使用排行 Top 8</h3>
                                    <div class="card-actions text-secondary small" id="rev-coupon-summary"></div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-sm">
                                        <thead>
                                            <tr>
                                                <th>优惠码</th>
                                                <th class="text-end">使用单数</th>
                                                <th class="text-end">成交额</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl-coupons">
                                            <tr><td colspan="3" class="text-center text-secondary py-3">加载中...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-7">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">自动续费扣款失败待挽回（最近 10 笔）</h3>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-sm">
                                        <thead>
                                            <tr>
                                                <th>用户</th>
                                                <th>商品 / 订单</th>
                                                <th class="text-end">续费金额</th>
                                                <th class="text-end">当前余额</th>
                                                <th class="text-end">到期时间</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl-autorenew-failed">
                                            <tr><td colspan="5" class="text-center text-secondary py-3">加载中...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════ Tab 3：流量与客户端 ════════════════════ -->
                <div class="tab-pane" id="tab-traffic" role="tabpanel">
                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">全站 24 小时分时流量分布</h3>
                                    <div class="card-actions d-flex align-items-center gap-2 flex-wrap">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-primary active" id="btn-hourly-avg">日均</button>
                                            <button type="button" class="btn btn-outline-primary" id="btn-hourly-total">累计</button>
                                        </div>
                                        <span class="badge bg-orange-lt" id="peak-hour-badge"></span>
                                        <span class="text-secondary small" id="hourly-source-note"></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="chart-hourly" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">订阅客户端分布</h3>
                                    <div class="card-actions text-secondary small">按日去重用户</div>
                                </div>
                                <div class="card-body">
                                    <div id="chart-clients" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-4">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">节点协议流量占比（当前周期）</h3></div>
                                <div class="card-body">
                                    <div id="chart-protocols" style="min-height: 300px;"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">当前计费周期节点流量负载 Top 20</h3>
                                    <div class="card-actions d-flex align-items-center gap-2 flex-wrap" id="node-summary-badges"></div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-sm">
                                        <thead>
                                            <tr>
                                                <th>节点</th>
                                                <th>协议</th>
                                                <th class="text-end">在线</th>
                                                <th class="text-end">已用 / 限额</th>
                                                <th style="width:140px;">负载率</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl-nodes">
                                            <tr><td colspan="5" class="text-center text-secondary py-3">加载中...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════ Tab 4：留存与推广 ════════════════════ -->
                <div class="tab-pane" id="tab-retention" role="tabpanel">
                    <div class="row row-cards mb-3">
                        <div class="col-sm-6 col-lg-4">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">7 日内即将到期（付费用户）</div>
                                    <div class="h2 mb-1" id="ret-expiring-7d">—</div>
                                    <div class="text-warning small" id="ret-expiring-no-renew">—</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">付费沉默用户（超 7 天未使用）</div>
                                    <div class="h2 mb-1" id="ret-silent-paid">—</div>
                                    <div class="text-secondary small">等级有效但最近 7 天无连接流量</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-lg-4">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="subheader">期间邀请返利带单总额</div>
                                    <div class="h2 mb-1" id="ret-ref-sales">—</div>
                                    <div class="text-secondary small" id="ret-ref-detail">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">即将到期且未开启自动续费 Top 10</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-sm">
                                        <thead>
                                            <tr>
                                                <th>用户</th>
                                                <th>等级</th>
                                                <th>到期时间</th>
                                                <th class="text-end">未活跃天数</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl-expiring-users">
                                            <tr><td colspan="4" class="text-center text-secondary py-3">加载中...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-6">
                            <div class="card">
                                <div class="card-header"><h3 class="card-title">邀请返利排行 Top 10（按所选时期）</h3></div>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table table-sm">
                                        <thead>
                                            <tr>
                                                <th>推广用户</th>
                                                <th class="text-end">单数 / 人数</th>
                                                <th class="text-end">带单销售额</th>
                                                <th class="text-end">获得返利</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl-referrers">
                                            <tr><td colspan="4" class="text-center text-secondary py-3">加载中...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/libs/apexcharts/dist/apexcharts.min.js"></script>

    <script>
        let currentMode = 'day';
        let currentMonth = '{$current_month}';

        {literal}
        const chartInstances = {};
        let currentYear = new Date().getFullYear();
        let activeTab = 'overview';
        const loadedPeriod = { overview: '', revenue: '', traffic: '', retention: '' };
        let cachedOverviewData = null;
        let cachedTrafficData = null;
        let hourlyDisplayMode = 'avg'; // 'avg' | 'total'

        function escHtml(v) {
            return String(v ?? '').replace(/[&<>"']/g, ch => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[ch]));
        }

        function getPeriodKey() {
            return `${currentMode}:${currentMonth}:${currentYear}`;
        }

        function buildQuery() {
            return `mode=${encodeURIComponent(currentMode)}&month=${encodeURIComponent(currentMonth)}&year=${encodeURIComponent(currentYear)}`;
        }

        // ── 10色板，相邻等级颜色差异明显 ──────────────────────────────────
        const CLASS_COLORS = [
            '#6c757d', '#206bc4', '#2fb344', '#f76707', '#d63939',
            '#17a589', '#ae3ec9', '#fab005', '#e8368f', '#4263eb',
        ];
        function classColor(cls) {
            return CLASS_COLORS[Number(cls)] ?? CLASS_COLORS[CLASS_COLORS.length - 1];
        }

        // ── 自定义月份选择器（月视图 + 年视图）────────────────────────────
        const MONTHS_ZH = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
        let pickerYear = parseInt(currentMonth.split('-')[0], 10);
        let pickerView = 'month';
        let pickerDecadeStart = Math.floor(pickerYear / 12) * 12;

        function pickerFmtLabel(ym) {
            const [y, m] = ym.split('-');
            return y + '年' + parseInt(m, 10) + '月';
        }

        function hideMonthPicker() {
            const btn = document.getElementById('month-picker-btn');
            const panel = document.getElementById('month-picker-panel');
            let closed = false;
            try {
                const B = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null) || window.tabler?.bootstrap || window.tabler;
                const Dropdown = B?.Dropdown;
                if (Dropdown && btn) {
                    const inst = (Dropdown.getInstance ? Dropdown.getInstance(btn) : null)
                        || (Dropdown.getOrCreateInstance ? Dropdown.getOrCreateInstance(btn) : null);
                    if (inst && typeof inst.hide === 'function') {
                        inst.hide();
                        closed = true;
                    }
                }
            } catch (e) {
                console.warn('Error hiding dropdown via API:', e);
            }
            if (!closed && btn && panel) {
                if (panel.classList.contains('show')) btn.click();
                if (panel.classList.contains('show')) {
                    panel.classList.remove('show');
                    btn.classList.remove('show');
                    btn.setAttribute('aria-expanded', 'false');
                }
            }
        }

        function renderMonthView() {
            pickerView = 'month';
            const grid = document.getElementById('picker-month-grid');
            if (!grid) return;
            const selYear = parseInt(currentMonth.split('-')[0], 10);
            const selMon  = parseInt(currentMonth.split('-')[1], 10);
            grid.innerHTML = MONTHS_ZH.map((name, i) => {
                const mon = i + 1;
                const isSelected = pickerYear === selYear && mon === selMon;
                const btnCls = isSelected ? 'btn btn-primary btn-sm w-100' : 'btn btn-ghost-secondary btn-sm w-100';
                return `<div class="col-3 p-0"><button type="button" class="${btnCls} picker-mon-btn" data-mon="${mon}">${name}</button></div>`;
            }).join('');
            document.getElementById('picker-year-label').textContent = pickerYear + ' ▾';
            document.getElementById('picker-year-label').style.cursor = 'pointer';
            document.getElementById('picker-prev-year').onclick = () => { pickerYear--; renderMonthView(); };
            document.getElementById('picker-next-year').onclick = () => { pickerYear++; renderMonthView(); };
            grid.querySelectorAll('.picker-mon-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const m = String(btn.dataset.mon).padStart(2, '0');
                    currentMonth = pickerYear + '-' + m;
                    document.getElementById('month-picker-label').textContent = pickerFmtLabel(currentMonth);
                    hideMonthPicker();
                    reloadAll();
                });
            });
        }

        function renderYearView() {
            pickerView = 'year';
            const grid = document.getElementById('picker-month-grid');
            if (!grid) return;
            const selYear = parseInt(currentMonth.split('-')[0], 10);
            const years = Array.from({ length: 12 }, (_, i) => pickerDecadeStart + i);
            grid.innerHTML = years.map(y => {
                const btnCls = y === selYear ? 'btn btn-primary btn-sm w-100' : 'btn btn-ghost-secondary btn-sm w-100';
                return `<div class="col-3 p-0"><button type="button" class="${btnCls} picker-yr-btn" data-year="${y}">${y}</button></div>`;
            }).join('');
            document.getElementById('picker-year-label').textContent = pickerDecadeStart + '–' + (pickerDecadeStart + 11);
            document.getElementById('picker-year-label').style.cursor = 'pointer';
            document.getElementById('picker-prev-year').onclick = () => { pickerDecadeStart -= 12; renderYearView(); };
            document.getElementById('picker-next-year').onclick = () => { pickerDecadeStart += 12; renderYearView(); };
            grid.querySelectorAll('.picker-yr-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    pickerYear = parseInt(btn.dataset.year, 10);
                    pickerDecadeStart = Math.floor(pickerYear / 12) * 12;
                    renderMonthView();
                });
            });
        }

        document.getElementById('picker-year-label')?.addEventListener('click', () => {
            if (pickerView === 'month') {
                pickerDecadeStart = Math.floor(pickerYear / 12) * 12;
                renderYearView();
            } else {
                renderMonthView();
            }
        });
        document.getElementById('picker-this-month')?.addEventListener('click', () => {
            const now = new Date();
            pickerYear = now.getFullYear();
            pickerDecadeStart = Math.floor(pickerYear / 12) * 12;
            currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            document.getElementById('month-picker-label').textContent = pickerFmtLabel(currentMonth);
            renderMonthView();
            hideMonthPicker();
            reloadAll();
        });
        document.getElementById('picker-clear')?.addEventListener('click', () => hideMonthPicker());
        document.getElementById('month-picker-btn')?.addEventListener('show.bs.dropdown', () => {
            pickerYear = parseInt(currentMonth.split('-')[0], 10);
            pickerDecadeStart = Math.floor(pickerYear / 12) * 12;
            renderMonthView();
        });

        document.getElementById('month-picker-label').textContent = pickerFmtLabel(currentMonth);
        renderMonthView();

        function updateYearLabel() {
            const el = document.getElementById('year-label');
            if (el) el.textContent = currentYear + ' 年';
        }
        updateYearLabel();
        document.getElementById('year-prev')?.addEventListener('click', () => { currentYear--; updateYearLabel(); reloadAll(); });
        document.getElementById('year-next')?.addEventListener('click', () => { currentYear++; updateYearLabel(); reloadAll(); });

        function destroyAndRender(id, options) {
            const el = document.getElementById(id);
            if (!el || !window.ApexCharts) return;
            if (chartInstances[id]) { chartInstances[id].destroy(); }
            chartInstances[id] = new ApexCharts(el, options);
            chartInstances[id].render();
        }

        // ── 顶部 KPI 摘要更新 ────────────────────────────────────────────
        function updateTopKpi(data) {
            const sum = arr => (arr || []).reduce((a, b) => a + Number(b || 0), 0);
            const last = arr => (arr && arr.length > 0) ? Number(arr[arr.length - 1] || 0) : 0;

            document.getElementById('kpi-income').textContent  = '¥' + sum(data.income).toFixed(2);
            document.getElementById('kpi-users').textContent   = sum(data.newUsers) + ' 人';
            document.getElementById('kpi-traffic').textContent = sum(data.trafficGb).toFixed(2) + ' GB';
            document.getElementById('kpi-active').textContent  = last(data.activeUsers) + ' 人';
            document.getElementById('kpi-checkin').textContent = sum(data.checkinUsers) + ' 人次';

            const diffEl = document.getElementById('kpi-income-diff');
            const diff = data.incomeComparison?.diffPercent;
            if (diff === null || diff === undefined) {
                diffEl.classList.add('d-none');
            } else {
                const isUp = diff >= 0;
                diffEl.textContent = (isUp ? '+' : '') + diff + '% 较上期';
                diffEl.className = 'ms-1 badge ' + (isUp ? 'bg-green-lt text-green' : 'bg-red-lt text-red');
            }
        }

        // ── Tab 1: 总览趋势 ──────────────────────────────────────────────
        function renderOverviewTab(siteData, productItems) {
            const xLabels = currentMode === 'day' ? (siteData.dates || []) : (siteData.months || []);
            renderLineChart('chart-income', '收入 (¥)', xLabels, siteData.income || []);
            renderLineChart('chart-users', '新增用户', xLabels, siteData.newUsers || [], '#2fb344', v => Math.round(v) + ' 人');
            renderClassLineChart('chart-class', xLabels, siteData.classSeries || {});
            renderDualLineChart('chart-activity', xLabels, [
                { name: '活跃用户', data: siteData.activeUsers || [] },
                { name: '签到人数', data: siteData.checkinUsers || [] },
            ], ['#206bc4', '#17a589']);
            renderAreaChart('chart-traffic', '流量 (GB)', xLabels, siteData.trafficGb || []);

            const items = (productItems || []).slice(0, 10).reverse();
            const categories = items.map(p => p.product_name || ('商品 #' + p.product_id));
            const data = items.map(p => Number(p.order_count) || 0);
            destroyAndRender('chart-products', {
                chart: { type: 'bar', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, barHeight: '55%', borderRadius: 3 } },
                dataLabels: { enabled: true, formatter: v => v + ' 单', style: { colors: ['#fff'] } },
                series: [{ name: '销量', data }],
                colors: ['#206bc4'],
                grid: { strokeDashArray: 4, padding: { top: -20, right: 10, left: 10, bottom: 0 } },
                xaxis: { categories, labels: { formatter: v => Math.round(v) } },
                yaxis: { labels: { style: { fontSize: '12px' } } },
                tooltip: {
                    theme: 'dark',
                    y: { formatter: (val, opt) => {
                        const item = items[opt.dataPointIndex];
                        return item ? val + ' 单 (¥' + Number(item.revenue || 0).toFixed(2) + ')' : val + ' 单';
                    }}
                },
                noData: { text: '暂无销售数据' }
            });
        }

        async function loadOverview(renderCharts = true) {
            const key = getPeriodKey();
            try {
                if (renderCharts) {
                    const [siteData, productItems] = await Promise.all([
                        fetch(`/admin/analytics/ajax?type=site_stats&${buildQuery()}`).then(r => r.json()),
                        fetch(`/admin/analytics/ajax?type=product_sales&${buildQuery()}`).then(r => r.json()),
                    ]);
                    cachedOverviewData = { key, siteData };
                    updateTopKpi(siteData);
                    renderOverviewTab(siteData, productItems);
                    loadedPeriod.overview = key;
                } else if (!cachedOverviewData || cachedOverviewData.key !== key) {
                    const siteData = await fetch(`/admin/analytics/ajax?type=site_stats&${buildQuery()}`).then(r => r.json());
                    cachedOverviewData = { key, siteData };
                    updateTopKpi(siteData);
                }
            } catch (err) {
                console.error('Error loading overview:', err);
            }
        }

        // ── Tab 2: 营收与转化 ────────────────────────────────────────────
        async function loadRevenueTab() {
            const key = getPeriodKey();
            try {
                const res = await fetch(`/admin/analytics/ajax?type=revenue_analysis&${buildQuery()}`).then(r => r.json());
                const s = res.summary || {};
                const mix = res.purchaseMix || {};
                const ar = res.autoRenew || {};

                document.getElementById('rev-arppu').textContent = '¥' + Number(s.arppu || 0).toFixed(2);
                document.getElementById('rev-orders').textContent = `${s.paidOrders || 0} 单 / ${s.paidUsers || 0} 人（总额 ¥${Number(s.totalRevenue || 0).toFixed(2)}）`;

                document.getElementById('rev-conv-rate').textContent = Number(s.regConversionRate || 0).toFixed(1) + '%';
                document.getElementById('rev-conv-detail').textContent = `新注册 ${s.newRegUsers || 0} 人，期内付费 ${s.newRegPaidUsers || 0} 人`;

                const totalMix = (mix.newCount || 0) + (mix.renewalCount || 0);
                const newPct = totalMix > 0 ? Math.round((mix.newCount || 0) / totalMix * 100) : 0;
                document.getElementById('rev-mix-ratio').textContent = `新购 ${newPct}% / 续费 ${totalMix > 0 ? 100 - newPct : 0}%`;
                document.getElementById('rev-mix-detail').textContent = `新购 ${mix.newCount || 0} 单 (¥${Number(mix.newRevenue || 0).toFixed(2)}) · 续费 ${mix.renewalCount || 0} 单 (¥${Number(mix.renewalRevenue || 0).toFixed(2)})`;

                const failedCnt = Number(ar.failedCount || 0);
                const failedEl = document.getElementById('rev-autorenew-failed');
                failedEl.textContent = failedCnt + ' 笔扣款失败';
                failedEl.className = 'h2 mb-1 ' + (failedCnt > 0 ? 'text-danger' : 'text-success');
                document.getElementById('rev-autorenew-enabled').textContent = `当前生效中自动续费订单：${ar.enabledCount || 0} 笔`;

                renderDonutChart('chart-gateways', (res.gateways || []).map(g => g.gateway), (res.gateways || []).map(g => g.amount), (res.gateways || []).map(g => g.count), '单');
                renderDonutChart('chart-order-types', (res.orderTypes || []).map(o => o.label), (res.orderTypes || []).map(o => o.revenue), (res.orderTypes || []).map(o => o.count), '单');
                renderDonutChart('chart-invoice-methods', (res.invoiceMethods || []).map(m => m.label), (res.invoiceMethods || []).map(m => m.amount), (res.invoiceMethods || []).map(m => m.count), '笔');

                document.getElementById('rev-coupon-summary').textContent = `用码 ${s.couponOrders || 0} 单 (¥${Number(s.couponRevenue || 0).toFixed(2)})`;
                const coupons = res.topCoupons || [];
                document.getElementById('tbl-coupons').innerHTML = coupons.length === 0
                    ? '<tr><td colspan="3" class="text-center text-secondary py-3">期间暂无优惠码订单</td></tr>'
                    : coupons.map(c => `<tr>
                        <td><code>${escHtml(c.code)}</code></td>
                        <td class="text-end">${Number(c.order_count || 0)} 单</td>
                        <td class="text-end">¥${Number(c.revenue || 0).toFixed(2)}</td>
                    </tr>`).join('');

                const failedOrders = ar.failedOrders || [];
                document.getElementById('tbl-autorenew-failed').innerHTML = failedOrders.length === 0
                    ? '<tr><td colspan="5" class="text-center text-secondary py-3">当前无扣款失败订单</td></tr>'
                    : failedOrders.map(o => `<tr>
                        <td><a href="/admin/user/${o.user_id}/edit">${escHtml(o.user_name)}</a><div class="text-secondary small">${escHtml(o.email)}</div></td>
                        <td><a href="/admin/order/${o.order_id}/view">${escHtml(o.product_name)}</a><div class="text-secondary small">#${o.order_id}</div></td>
                        <td class="text-end">¥${Number(o.price || 0).toFixed(2)}</td>
                        <td class="text-end text-danger">¥${Number(o.money || 0).toFixed(2)}</td>
                        <td class="text-end small">${escHtml(o.class_expire)}</td>
                    </tr>`).join('');

                loadedPeriod.revenue = key;
            } catch (err) {
                console.error('Error loading revenue tab:', err);
            }
        }

        // ── Tab 3: 流量与客户端 ──────────────────────────────────────────
        function renderHourlyChart() {
            const res = cachedTrafficData;
            if (!res) return;

            const isAvg = hourlyDisplayMode === 'avg';
            document.getElementById('btn-hourly-avg').classList.toggle('active', isAvg);
            document.getElementById('btn-hourly-avg').classList.toggle('btn-primary', isAvg);
            document.getElementById('btn-hourly-avg').classList.toggle('btn-outline-primary', !isAvg);
            document.getElementById('btn-hourly-total').classList.toggle('active', !isAvg);
            document.getElementById('btn-hourly-total').classList.toggle('btn-primary', !isAvg);
            document.getElementById('btn-hourly-total').classList.toggle('btn-outline-primary', isAvg);

            const days = Number(res.daysCount || 0);
            const peakBadge = document.getElementById('peak-hour-badge');
            if (days === 0) {
                peakBadge.textContent = '暂无峰值数据';
            } else if (isAvg) {
                const hStr = String(res.peakHour ?? 0).padStart(2, '0') + ':00';
                peakBadge.textContent = `日均高峰 ${hStr} (${Number(res.peakAvgGb || 0).toFixed(2)} GB/时) · 统计 ${days} 天（日均 ${Number(res.avgDailyTotalGb || 0).toFixed(2)} GB/天）`;
            } else {
                const hStr = String(res.peakTotalHour ?? 0).padStart(2, '0') + ':00';
                peakBadge.textContent = `累计高峰 ${hStr} (${Number(res.peakGb || 0).toFixed(2)} GB) · ${days} 天总计 ${Number(res.totalGb || 0).toFixed(2)} GB`;
            }

            const srcNote = document.getElementById('hourly-source-note');
            srcNote.textContent = res.hasFallback ? '含近期日志补全' : '每日快照';

            const hourLabels = Array.from({ length: 24 }, (_, h) => String(h).padStart(2, '0') + ':00');
            const seriesData = isAvg ? (res.hourlyAvgGb || []) : (res.hourlyGb || []);
            const avgArr = res.hourlyAvgGb || [];
            const totArr = res.hourlyGb || [];

            destroyAndRender('chart-hourly', {
                chart: { type: 'bar', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                plotOptions: { bar: { columnWidth: '60%', borderRadius: 2 } },
                dataLabels: { enabled: false },
                series: [{ name: isAvg ? '日均流量 (GB/时)' : `累计流量 (${days}天 GB)`, data: seriesData }],
                colors: ['#f76707'],
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                xaxis: { categories: hourLabels, labels: { style: { fontSize: '11px' }, rotate: -45, rotateAlways: true, hideOverlappingLabels: false }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter: v => Number(v || 0).toFixed(1) + ' GB' } },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: (v, opt) => {
                            const i = opt.dataPointIndex;
                            return `日均 ${Number(avgArr[i] || 0).toFixed(2)} GB/时 · ${days}天累计 ${Number(totArr[i] || 0).toFixed(2)} GB`;
                        }
                    }
                },
                noData: { text: '暂无分时流量数据' }
            });
        }

        document.getElementById('btn-hourly-avg')?.addEventListener('click', () => {
            hourlyDisplayMode = 'avg';
            renderHourlyChart();
        });
        document.getElementById('btn-hourly-total')?.addEventListener('click', () => {
            hourlyDisplayMode = 'total';
            renderHourlyChart();
        });

        async function loadTrafficTab() {
            const key = getPeriodKey();
            try {
                const res = await fetch(`/admin/analytics/ajax?type=traffic_clients&${buildQuery()}`).then(r => r.json());
                const nodeRes = await fetch(`/admin/analytics/ajax?type=node_retention&${buildQuery()}`).then(r => r.json());

                cachedTrafficData = res;
                renderHourlyChart();

                const clients = res.clients || [];
                renderDonutChart('chart-clients', clients.map(c => c.name), clients.map(c => c.count), null, '人次', false);

                const ns = nodeRes.nodeStats || {};
                const sum = ns.summary || {};
                document.getElementById('node-summary-badges').innerHTML = `
                    <span class="badge bg-green-lt">在线 ${sum.onlineNodes || 0}/${sum.totalNodes || 0}</span>
                    <span class="badge bg-blue-lt">在线连接 ${sum.totalOnlineUsers || 0}</span>
                    <span class="badge bg-orange-lt">近限额(≥80%) ${sum.nearLimitCount || 0}</span>
                    <span class="badge bg-secondary-lt">闲置零流量 ${sum.idleNodesCount || 0}</span>
                `;

                const protocols = ns.protocols || [];
                renderDonutChart('chart-protocols', protocols.map(p => p.protocol), protocols.map(p => p.used_gb), protocols.map(p => p.nodes), '节点', false, ' GB');

                const nodes = ns.nodes || [];
                document.getElementById('tbl-nodes').innerHTML = nodes.length === 0
                    ? '<tr><td colspan="5" class="text-center text-secondary py-3">暂无启用节点</td></tr>'
                    : nodes.map(n => {
                        const dot = n.online_status === 1 ? 'bg-green' : (n.online_status === 0 ? 'bg-orange' : 'bg-red');
                        const pct = n.usage_percent;
                        const barCls = pct !== null && pct >= 90 ? 'bg-red' : (pct !== null && pct >= 80 ? 'bg-orange' : 'bg-blue');
                        const pctHtml = pct === null
                            ? '<span class="text-secondary small">不限额</span>'
                            : `<div class="d-flex align-items-center gap-2"><div class="progress progress-sm flex-fill"><div class="progress-bar ${barCls}" style="width:${Math.min(100, pct)}%"></div></div><span class="small">${pct}%</span></div>`;
                        return `<tr>
                            <td><span class="status-dot ${dot} me-1"></span><a href="/admin/node/${n.id}/edit">${escHtml(n.name)}</a></td>
                            <td><span class="badge bg-blue-lt">${escHtml(n.protocol)}</span></td>
                            <td class="text-end">${n.online_user}</td>
                            <td class="text-end text-nowrap">${Number(n.used_gb).toFixed(2)} / ${n.limit_gb > 0 ? Number(n.limit_gb).toFixed(0) + ' GB' : '∞'}</td>
                            <td>${pctHtml}</td>
                        </tr>`;
                    }).join('');

                loadedPeriod.traffic = key;
                loadedPeriod.retention = key; // node_retention returns both node & retention data
                renderRetentionData(nodeRes);
            } catch (err) {
                console.error('Error loading traffic tab:', err);
            }
        }

        // ── Tab 4: 留存与推广 ────────────────────────────────────────────
        function renderRetentionData(nodeRes) {
            const r = nodeRes.retention || {};
            const ref = nodeRes.referrals || {};
            const rs = ref.summary || {};

            document.getElementById('ret-expiring-7d').textContent = (r.expiring7DaysCount || 0) + ' 人';
            document.getElementById('ret-expiring-no-renew').textContent = `其中 ${r.expiringNoAutoRenewCount || 0} 人未开启自动续费`;
            document.getElementById('ret-silent-paid').textContent = (r.silentPaidCount || 0) + ' 人';
            document.getElementById('ret-ref-sales').textContent = '¥' + Number(rs.totalSales || 0).toFixed(2);
            document.getElementById('ret-ref-detail').textContent = `${rs.orderCount || 0} 单 (${rs.invitedBuyers || 0} 人)，返利支出 ¥${Number(rs.totalReward || 0).toFixed(2)}`;

            const expUsers = r.expiringUsers || [];
            document.getElementById('tbl-expiring-users').innerHTML = expUsers.length === 0
                ? '<tr><td colspan="4" class="text-center text-secondary py-3">未来 7 天暂无未续费到期用户</td></tr>'
                : expUsers.map(u => `<tr>
                    <td><a href="/admin/user/${u.id}/edit">${escHtml(u.user_name)}</a><div class="text-secondary small">${escHtml(u.email)}</div></td>
                    <td><span class="badge bg-azure-lt">Lv.${u.class}</span></td>
                    <td class="small text-nowrap">${escHtml(u.class_expire)}</td>
                    <td class="text-end small">${u.last_use_days === null ? '从未' : u.last_use_days + ' 天前'}</td>
                </tr>`).join('');

            const refs = ref.topReferrers || [];
            document.getElementById('tbl-referrers').innerHTML = refs.length === 0
                ? '<tr><td colspan="4" class="text-center text-secondary py-3">期间暂无邀请返利记录</td></tr>'
                : refs.map(rf => `<tr>
                    <td><a href="/admin/user/${rf.ref_by}/edit">${escHtml(rf.user_name)}</a><div class="text-secondary small">${escHtml(rf.email)}</div></td>
                    <td class="text-end">${rf.order_count} 单 / ${rf.invited_buyers} 人</td>
                    <td class="text-end">¥${Number(rf.total_sales || 0).toFixed(2)}</td>
                    <td class="text-end text-success">¥${Number(rf.total_reward || 0).toFixed(2)}</td>
                </tr>`).join('');
        }

        async function loadRetentionTab() {
            const key = getPeriodKey();
            try {
                const nodeRes = await fetch(`/admin/analytics/ajax?type=node_retention&${buildQuery()}`).then(r => r.json());
                renderRetentionData(nodeRes);
                loadedPeriod.retention = key;
            } catch (err) {
                console.error('Error loading retention tab:', err);
            }
        }

        // ── 切换控制 & 标签懒加载 ────────────────────────────────────────
        function loadActiveTab() {
            const key = getPeriodKey();
            if (activeTab === 'overview') {
                if (loadedPeriod.overview !== key) loadOverview(true);
                return;
            }
            // 其他 Tab 时也保证顶部全局 KPI 随时间同步
            loadOverview(false);
            if (activeTab === 'revenue' && loadedPeriod.revenue !== key) loadRevenueTab();
            if (activeTab === 'traffic' && loadedPeriod.traffic !== key) loadTrafficTab();
            if (activeTab === 'retention' && loadedPeriod.retention !== key) loadRetentionTab();
        }

        function reloadAll() {
            Object.keys(loadedPeriod).forEach(k => { loadedPeriod[k] = ''; });
            cachedOverviewData = null;
            loadActiveTab();
        }

        function switchMode(mode) {
            currentMode = mode;
            const isDay   = mode === 'day';
            const isMonth = mode === 'month';
            const isYear  = mode === 'year';
            document.getElementById('btn-day').classList.toggle('active', isDay);
            document.getElementById('btn-day').classList.toggle('btn-primary', isDay);
            document.getElementById('btn-day').classList.toggle('btn-outline-primary', !isDay);
            document.getElementById('btn-month').classList.toggle('active', isMonth);
            document.getElementById('btn-month').classList.toggle('btn-primary', isMonth);
            document.getElementById('btn-month').classList.toggle('btn-outline-primary', !isMonth);
            document.getElementById('btn-year').classList.toggle('active', isYear);
            document.getElementById('btn-year').classList.toggle('btn-primary', isYear);
            document.getElementById('btn-year').classList.toggle('btn-outline-primary', !isYear);
            document.getElementById('day-shortcuts').classList.toggle('d-none', !isDay);
            document.getElementById('month-shortcuts').classList.toggle('d-none', !isMonth);
            document.getElementById('year-shortcuts').classList.toggle('d-none', !isYear);
            reloadAll();
        }

        document.getElementById('btn-day').addEventListener('click', () => switchMode('day'));
        document.getElementById('btn-month').addEventListener('click', () => switchMode('month'));
        document.getElementById('btn-year').addEventListener('click', () => switchMode('year'));

        document.querySelectorAll('.day-shortcut').forEach(btn => {
            btn.addEventListener('click', () => {
                currentMonth = btn.dataset.month;
                document.getElementById('month-picker-label').textContent = pickerFmtLabel(currentMonth);
                reloadAll();
            });
        });

        document.querySelectorAll('#analytics-tabs [data-bs-toggle="tab"]').forEach(tabEl => {
            tabEl.addEventListener('shown.bs.tab', e => {
                activeTab = e.target.dataset.tab || 'overview';
                loadActiveTab();
            });
        });

        // ── 图表渲染函数 ─────────────────────────────────────────────────
        function renderLineChart(id, name, labels, data, color = '#206bc4', formatter = v => '¥' + Number(v || 0).toFixed(2)) {
            destroyAndRender(id, {
                chart: { type: 'line', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                series: [{ name, data }],
                colors: [color],
                xaxis: { categories: labels, labels: { style: { fontSize: '12px' }, rotate: -45 }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter } },
                tooltip: { theme: 'dark', y: { formatter } },
                noData: { text: '暂无数据' }
            });
        }

        function renderDualLineChart(id, labels, series, colors) {
            destroyAndRender(id, {
                chart: { type: 'line', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                series,
                colors,
                xaxis: { categories: labels, labels: { style: { fontSize: '12px' }, rotate: -45 }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter: v => Math.round(v) } },
                tooltip: { theme: 'dark', y: { formatter: v => Math.round(v) + ' 人' } },
                legend: { position: 'top', horizontalAlign: 'right' },
                noData: { text: '暂无数据' }
            });
        }

        function renderAreaChart(id, name, labels, data) {
            destroyAndRender(id, {
                chart: { type: 'area', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] } },
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                series: [{ name, data }],
                colors: ['#f76707'],
                xaxis: { categories: labels, labels: { style: { fontSize: '12px' }, rotate: -45 }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter: v => Number(v || 0).toFixed(2) + ' GB' } },
                tooltip: { theme: 'dark', y: { formatter: v => Number(v || 0).toFixed(2) + ' GB' } },
                noData: { text: '暂无数据' }
            });
        }

        function renderClassLineChart(id, labels, classSeries) {
            const sorted = Object.keys(classSeries || {}).sort((a, b) => Number(a) - Number(b));
            destroyAndRender(id, {
                chart: { type: 'line', fontFamily: 'inherit', height: 350, parentHeightOffset: 0, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                dataLabels: { enabled: false },
                series: sorted.map(cls => ({ name: '等级 ' + cls, data: classSeries[cls] || [] })),
                colors: sorted.map(cls => classColor(cls)),
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                xaxis: { categories: labels, labels: { style: { fontSize: '12px' }, rotate: -45 }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter: v => Math.round(v) } },
                tooltip: { theme: 'dark', y: { formatter: v => v + ' 人' } },
                legend: { position: 'top', horizontalAlign: 'right' },
                noData: { text: '暂无数据' }
            });
        }

        function renderDonutChart(id, labels, values, extraCounts = null, countUnit = '单', isCurrency = true, suffix = '') {
            const total = (values || []).reduce((a, b) => a + Number(b || 0), 0);
            const hasData = (values || []).length > 0 && total > 0;
            destroyAndRender(id, {
                chart: { type: 'donut', fontFamily: 'inherit', height: 280, parentHeightOffset: 0 },
                series: hasData ? values : [],
                labels: hasData ? labels : [],
                colors: ['#206bc4', '#2fb344', '#f76707', '#ae3ec9', '#17a589', '#fab005', '#d63939', '#4263eb', '#6c757d'],
                legend: { position: 'bottom' },
                dataLabels: { enabled: true },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: (v, opt) => {
                            const main = isCurrency
                                ? '¥' + Number(v || 0).toFixed(2)
                                : (suffix ? Number(v || 0).toFixed(2) + suffix : Math.round(v) + ' ' + countUnit);
                            if (extraCounts && extraCounts[opt.seriesIndex] !== undefined) {
                                return `${main} (${extraCounts[opt.seriesIndex]} ${countUnit})`;
                            }
                            return main;
                        }
                    }
                },
                noData: { text: '暂无数据' }
            });
        }

        // ── 初始化 ───────────────────────────────────────────────────────
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', reloadAll);
        } else {
            reloadAll();
        }
        {/literal}
    </script>

    {include file='admin/footer.tpl'}
