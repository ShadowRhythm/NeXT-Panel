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
                        <span class="home-subtitle">站点运营状态与趋势分析</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">

            <!-- 时间维度切换器 -->
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
                                    <!-- 年份导航 -->
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <button class="btn btn-sm btn-ghost-secondary px-2" id="picker-prev-year" type="button">
                                            <i class="ti ti-chevron-left"></i>
                                        </button>
                                        <span class="fw-semibold fs-4" id="picker-year-label">2026</span>
                                        <button class="btn btn-sm btn-ghost-secondary px-2" id="picker-next-year" type="button">
                                            <i class="ti ti-chevron-right"></i>
                                        </button>
                                    </div>
                                    <!-- 月份网格 -->
                                    <div class="row g-1" id="picker-month-grid"></div>
                                    <!-- 底部操作 -->
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
                        <div class="ms-auto d-flex align-items-center gap-4 text-nowrap">
                            <div>
                                <span class="text-secondary small">期间流水</span>
                                <span class="ms-1 fw-medium" id="kpi-income">—</span>
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- 第一行：收入 + 新增用户 -->
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

            <!-- 第二行：用户等级分布（全宽堆叠柱状） -->
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

            <!-- 第三行：流量 + 商品销量 -->
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
    </div>

    <script src="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/libs/apexcharts/dist/apexcharts.min.js"></script>

    <script>
        let currentMode = 'day';
        let currentMonth = '{$current_month}';

        {literal}
        const chartInstances = {};

        // ── 10色板，相邻等级颜色差异明显 ──────────────────────────────────
        const CLASS_COLORS = [
            '#6c757d',  // 0: 灰
            '#206bc4',  // 1: 蓝
            '#2fb344',  // 2: 绿
            '#f76707',  // 3: 橙
            '#d63939',  // 4: 红
            '#17a589',  // 5: 青绿
            '#ae3ec9',  // 6: 紫
            '#fab005',  // 7: 黄
            '#e8368f',  // 8: 玫红
            '#4263eb',  // 9: 靛蓝
        ];
        function classColor(cls) {
            return CLASS_COLORS[Number(cls)] ?? CLASS_COLORS[CLASS_COLORS.length - 1];
        }

        // ── 自定义月份选择器（月视图 + 年视图）────────────────────────────
        const MONTHS_ZH = ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'];
        let pickerYear  = parseInt(currentMonth.split('-')[0]);
        let pickerView  = 'month'; // 'month' | 'year'
        let pickerDecadeStart = Math.floor(pickerYear / 12) * 12;

        function pickerFmtLabel(ym) {
            const [y, m] = ym.split('-');
            return y + '年' + parseInt(m) + '月';
        }

        // ── 月份网格 ──────────────────────────────────────────────────────
        function renderMonthView() {
            pickerView = 'month';
            const grid = document.getElementById('picker-month-grid');
            if (!grid) return;
            const selYear = parseInt(currentMonth.split('-')[0]);
            const selMon  = parseInt(currentMonth.split('-')[1]);
            grid.innerHTML = MONTHS_ZH.map((name, i) => {
                const mon = i + 1;
                const isSelected = pickerYear === selYear && mon === selMon;
                const btnCls = isSelected
                    ? 'btn btn-primary btn-sm w-100'
                    : 'btn btn-ghost-secondary btn-sm w-100';
                return `<div class="col-3 p-0"><button type="button" class="${btnCls} picker-mon-btn" data-mon="${mon}">${name}</button></div>`;
            }).join('');
            // 年份标题：可点击切换到年格视图
            document.getElementById('picker-year-label').textContent = pickerYear + ' ▾';
            document.getElementById('picker-year-label').style.cursor = 'pointer';
            // 箭头：切换年
            document.getElementById('picker-prev-year').onclick = () => { pickerYear--; renderMonthView(); };
            document.getElementById('picker-next-year').onclick = () => { pickerYear++; renderMonthView(); };
            grid.querySelectorAll('.picker-mon-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const m = String(btn.dataset.mon).padStart(2, '0');
                    currentMonth = pickerYear + '-' + m;
                    document.getElementById('month-picker-label').textContent = pickerFmtLabel(currentMonth);
                    const dd = bootstrap.Dropdown.getInstance(document.getElementById('month-picker-btn'));
                    if (dd) dd.hide();
                    reloadAll();
                });
            });
        }

        // ── 年份网格（12格一组） ──────────────────────────────────────────
        function renderYearView() {
            pickerView = 'year';
            const grid = document.getElementById('picker-month-grid');
            if (!grid) return;
            const selYear = parseInt(currentMonth.split('-')[0]);
            const years = Array.from({ length: 12 }, (_, i) => pickerDecadeStart + i);
            grid.innerHTML = years.map(y => {
                const isSelected = y === selYear;
                const isFaded    = y < pickerDecadeStart || y > pickerDecadeStart + 11;
                const btnCls = isSelected
                    ? 'btn btn-primary btn-sm w-100'
                    : (isFaded ? 'btn btn-ghost-secondary btn-sm w-100 text-muted' : 'btn btn-ghost-secondary btn-sm w-100');
                return `<div class="col-3 p-0"><button type="button" class="${btnCls} picker-yr-btn" data-year="${y}">${y}</button></div>`;
            }).join('');
            // 年份标题：显示范围
            document.getElementById('picker-year-label').textContent = pickerDecadeStart + '–' + (pickerDecadeStart + 11);
            document.getElementById('picker-year-label').style.cursor = 'default';
            // 箭头：切换 12 年组
            document.getElementById('picker-prev-year').onclick = () => { pickerDecadeStart -= 12; renderYearView(); };
            document.getElementById('picker-next-year').onclick = () => { pickerDecadeStart += 12; renderYearView(); };
            grid.querySelectorAll('.picker-yr-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    pickerYear = parseInt(btn.dataset.year);
                    pickerDecadeStart = Math.floor(pickerYear / 12) * 12;
                    renderMonthView();
                });
            });
        }

        // 点击年份标题 → 切换视图
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
            const dd = bootstrap.Dropdown.getInstance(document.getElementById('month-picker-btn'));
            if (dd) dd.hide();
            reloadAll();
        });
        document.getElementById('picker-clear')?.addEventListener('click', () => {
            const dd = bootstrap.Dropdown.getInstance(document.getElementById('month-picker-btn'));
            if (dd) dd.hide();
        });
        document.getElementById('month-picker-btn')?.addEventListener('show.bs.dropdown', () => {
            pickerYear = parseInt(currentMonth.split('-')[0]);
            pickerDecadeStart = Math.floor(pickerYear / 12) * 12;
            renderMonthView();
        });

        // 初始渲染 label
        document.getElementById('month-picker-label').textContent = pickerFmtLabel(currentMonth);

        function destroyAndRender(id, options) {
            const el = document.getElementById(id);
            if (!el || !window.ApexCharts) return;
            if (chartInstances[id]) { chartInstances[id].destroy(); }
            chartInstances[id] = new ApexCharts(el, options);
            chartInstances[id].render();
        }

        // ── KPI 摘要更新 ────────────────────────────────────────────────
        function updateKpi(income, newUsers, trafficGb, activeUsers) {
            const sum = arr => (arr || []).reduce((a, b) => a + b, 0);
            const last = arr => (arr && arr.length > 0) ? arr[arr.length - 1] : 0;

            const totalIncome  = sum(income);
            const totalUsers   = sum(newUsers);
            const totalTraffic = sum(trafficGb);
            const lastActive   = last(activeUsers);

            document.getElementById('kpi-income').textContent  = '¥' + totalIncome.toFixed(2);
            document.getElementById('kpi-users').textContent   = totalUsers + ' 人';
            document.getElementById('kpi-traffic').textContent = totalTraffic.toFixed(2) + ' GB';
            document.getElementById('kpi-active').textContent  = lastActive + ' 人';
        }

        // ── 趋势图加载（收入/用户/流量/等级） ───────────────────────────
        async function loadCharts() {
            try {
                const url = `/admin/analytics/ajax?type=site_stats&mode=${currentMode}&month=${currentMonth}&year=${currentYear}`;
                const data = await fetch(url).then(r => r.json());
                const xLabels = currentMode === 'day' ? (data.dates || []) : (data.months || []);

                renderLineChart('chart-income', '收入 (¥)', xLabels, data.income || []);
                renderBarChart('chart-users', '新增用户', xLabels, data.newUsers || []);
                renderAreaChart('chart-traffic', '流量 (GB)', xLabels, data.trafficGb || []);
                renderStackedBar('chart-class', xLabels, data.classSeries || {});

                // 更新右侧 KPI 摘要
                updateKpi(data.income, data.newUsers, data.trafficGb, data.activeUsers);
            } catch (err) {
                console.error('Error loading charts:', err);
            }
        }

        // ── 商品销量（随日期切换 AJAX 刷新） ────────────────────────────
        async function loadProductChart() {
            try {
                const url = `/admin/analytics/ajax?type=product_sales&mode=${currentMode}&month=${currentMonth}&year=${currentYear}`;
                const items = (await fetch(url).then(r => r.json()) || []).slice(0, 10).reverse();

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
            } catch (err) {
                console.error('Error loading product chart:', err);
            }
        }

        // ── 年视图年份 ───────────────────────────────────────────────────
        let currentYear = new Date().getFullYear();
        function updateYearLabel() {
            const el = document.getElementById('year-label');
            if (el) el.textContent = currentYear + ' 年';
        }
        updateYearLabel();
        document.getElementById('year-prev')?.addEventListener('click', () => { currentYear--; updateYearLabel(); reloadAll(); });
        document.getElementById('year-next')?.addEventListener('click', () => { currentYear++; updateYearLabel(); reloadAll(); });

        // ── 切换维度 ─────────────────────────────────────────────────────
        function switchMode(mode) {
            currentMode = mode;
            const isDay   = mode === 'day';
            const isMonth = mode === 'month';
            const isYear  = mode === 'year';
            // btn-day
            document.getElementById('btn-day').classList.toggle('active', isDay);
            document.getElementById('btn-day').classList.toggle('btn-primary', isDay);
            document.getElementById('btn-day').classList.toggle('btn-outline-primary', !isDay);
            // btn-month
            document.getElementById('btn-month').classList.toggle('active', isMonth);
            document.getElementById('btn-month').classList.toggle('btn-primary', !isMonth);
            document.getElementById('btn-month').classList.toggle('btn-outline-primary', !isMonth);
            // btn-year
            document.getElementById('btn-year').classList.toggle('active', isYear);
            document.getElementById('btn-year').classList.toggle('btn-primary', isYear);
            document.getElementById('btn-year').classList.toggle('btn-outline-primary', !isYear);
            // 显示/隐藏快捷栏
            document.getElementById('day-shortcuts').classList.toggle('d-none', !isDay);
            document.getElementById('month-shortcuts').classList.toggle('d-none', !isMonth);
            document.getElementById('year-shortcuts').classList.toggle('d-none', !isYear);
            reloadAll();
        }

        function reloadAll() {
            loadCharts();
            loadProductChart();
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

        // ── 图表渲染函数 ─────────────────────────────────────────────────
        function renderLineChart(id, name, labels, data) {
            destroyAndRender(id, {
                chart: { type: 'line', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 2 },
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                series: [{ name, data }],
                colors: ['#206bc4'],
                xaxis: { categories: labels, labels: { style: { fontSize: '12px' }, rotate: -45 }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter: v => '¥' + Number(v || 0).toFixed(2) } },
                tooltip: { theme: 'dark', y: { formatter: v => '¥' + Number(v || 0).toFixed(2) } },
                noData: { text: '暂无数据' }
            });
        }

        function renderBarChart(id, name, labels, data) {
            destroyAndRender(id, {
                chart: { type: 'bar', fontFamily: 'inherit', height: 300, parentHeightOffset: 0, toolbar: { show: false } },
                plotOptions: { bar: { columnWidth: '50%', borderRadius: 3 } },
                dataLabels: { enabled: false },
                grid: { strokeDashArray: 4, padding: { top: -20, right: 0, left: 10, bottom: 0 } },
                series: [{ name, data }],
                colors: ['#2fb344'],
                xaxis: { categories: labels, labels: { style: { fontSize: '12px' }, rotate: -45 }, tooltip: { enabled: false } },
                yaxis: { labels: { formatter: v => Math.round(v) } },
                tooltip: { theme: 'dark', y: { formatter: v => v + ' 人' } },
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

        function renderStackedBar(id, labels, classSeries) {
            const sorted = Object.keys(classSeries || {}).sort((a, b) => Number(a) - Number(b));
            destroyAndRender(id, {
                chart: { type: 'bar', stacked: true, fontFamily: 'inherit', height: 350, parentHeightOffset: 0, toolbar: { show: false } },
                plotOptions: { bar: { columnWidth: '50%', borderRadius: 2 } },
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

        // ── 初始化 ───────────────────────────────────────────────────────
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', reloadAll);
        } else {
            reloadAll();
        }
        {/literal}
    </script>

    {include file='admin/footer.tpl'}
