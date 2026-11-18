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
            <!-- 顶部 KPI 卡片（4列） -->
            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-info text-white avatar">
                                        <i class="ti ti-calendar-event icon"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        ￥{$today_income}
                                    </div>
                                    <div class="text-secondary">
                                        今日流水
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-warning text-white avatar">
                                        <i class="ti ti-calendar-stats icon"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        ￥{$this_month_income}
                                        {if isset($monthly_comparison.diff_percent)}
                                            {if $monthly_comparison.diff_percent >= 0}
                                                <span class="text-green d-inline-flex align-items-center lh-1 ms-1" title="较上月同期">
                                                    +{$monthly_comparison.diff_percent}% ↑
                                                </span>
                                            {else}
                                                <span class="text-danger d-inline-flex align-items-center lh-1 ms-1" title="较上月同期">
                                                    {$monthly_comparison.diff_percent}% ↓
                                                </span>
                                            {/if}
                                        {/if}
                                    </div>
                                    <div class="text-secondary">
                                        本月流水
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-blue text-white avatar">
                                        <i class="ti ti-users icon"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        {$total_user}
                                    </div>
                                    <div class="text-secondary">
                                        总用户数
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-green text-white avatar">
                                        <i class="ti ti-user-check icon"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium">
                                        {$active_user}
                                    </div>
                                    <div class="text-secondary">
                                        活跃用户
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 时间维度切换器（KPI 卡片下方） -->
            <div class="card mb-3">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center gap-3">
                        <!-- 维度切换 -->
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-primary active" id="btn-day">日</button>
                            <button type="button" class="btn btn-outline-primary" id="btn-month">月</button>
                        </div>
                        <!-- 日视图快捷 -->
                        <div id="day-shortcuts" class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary day-shortcut" data-month="{$current_month}">本月</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary day-shortcut" data-month="{$last_month}">上月</button>
                            <input type="month" class="form-control form-control-sm" id="month-picker" style="width:150px" value="{$current_month}">
                        </div>
                        <!-- 月视图快捷（默认隐藏） -->
                        <div id="month-shortcuts" class="d-flex align-items-center gap-2 d-none">
                            <span class="text-secondary small">最近12个月</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 图表区域（3行） -->
            <!-- 第一行（各占50%）：左 收入趋势折线图，右 新增用户柱状图 -->
            <div class="row row-cards mb-3">
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">收入趋势</h3>
                        </div>
                        <div class="card-body">
                            <div id="chart-income" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">新增用户</h3>
                        </div>
                        <div class="card-body">
                            <div id="chart-users" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 第二行（全宽）：用户等级分布堆叠柱状图 -->
            <div class="row row-cards mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">用户等级分布</h3>
                        </div>
                        <div class="card-body">
                            <div id="chart-class" style="min-height: 350px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 第三行（各占50%）：左 流量趋势面积图，右 商品销量排行横向柱状图 -->
            <div class="row row-cards mb-3">
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">流量趋势</h3>
                        </div>
                        <div class="card-body">
                            <div id="chart-traffic" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">商品销量排行</h3>
                        </div>
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
        const rawProductSales = {if !empty($product_sales)}{$product_sales|json_encode}{else}[]{/if};

        {literal}
        const chartInstances = {};

        function getOrRenderChart(id, options) {
            const el = document.getElementById(id);
            if (!el || !window.ApexCharts) return;
            if (chartInstances[id]) {
                chartInstances[id].destroy();
            }
            chartInstances[id] = new ApexCharts(el, options);
            chartInstances[id].render();
        }

        // 请求图表数据并渲染
        async function loadCharts() {
            try {
                const url = `/admin/analytics/ajax?type=site_stats&mode=${currentMode}&month=${currentMonth}`;
                const resp = await fetch(url);
                if (!resp.ok) {
                    console.error('Failed to fetch analytics data:', resp.statusText);
                    return;
                }
                const data = await resp.json();

                const xLabels = currentMode === 'day' ? (data.dates || []) : (data.months || []);

                // 收入折线
                renderLineChart('chart-income', '收入 (¥)', xLabels, data.income || []);
                // 用户柱状
                renderBarChart('chart-users', '新增用户', xLabels, data.newUsers || []);
                // 流量面积
                renderAreaChart('chart-traffic', '流量 (GB)', xLabels, data.trafficGb || []);
                // 等级堆叠
                renderStackedBar('chart-class', xLabels, data.classSeries || {});
            } catch (err) {
                console.error('Error loading charts:', err);
            }
        }

        // 切换维度
        const btnDay = document.getElementById('btn-day');
        const btnMonth = document.getElementById('btn-month');
        const dayShortcuts = document.getElementById('day-shortcuts');
        const monthShortcuts = document.getElementById('month-shortcuts');
        const monthPicker = document.getElementById('month-picker');

        if (btnDay) {
            btnDay.addEventListener('click', () => {
                currentMode = 'day';
                btnDay.classList.add('active', 'btn-primary');
                btnDay.classList.remove('btn-outline-primary');
                btnMonth.classList.remove('active', 'btn-primary');
                btnMonth.classList.add('btn-outline-primary');
                dayShortcuts.classList.remove('d-none');
                monthShortcuts.classList.add('d-none');
                loadCharts();
            });
        }

        if (btnMonth) {
            btnMonth.addEventListener('click', () => {
                currentMode = 'month';
                btnMonth.classList.add('active', 'btn-primary');
                btnMonth.classList.remove('btn-outline-primary');
                btnDay.classList.remove('active', 'btn-primary');
                btnDay.classList.add('btn-outline-primary');
                dayShortcuts.classList.add('d-none');
                monthShortcuts.classList.remove('d-none');
                loadCharts();
            });
        }

        // 月份选择器
        if (monthPicker) {
            monthPicker.addEventListener('change', (e) => {
                currentMonth = e.target.value;
                loadCharts();
            });
        }

        // 日视图快捷按钮
        document.querySelectorAll('.day-shortcut').forEach(btn => {
            btn.addEventListener('click', () => {
                currentMonth = btn.dataset.month;
                if (monthPicker) {
                    monthPicker.value = currentMonth;
                }
                loadCharts();
            });
        });

        // 收入折线图
        function renderLineChart(id, name, labels, data) {
            const options = {
                chart: {
                    type: 'line',
                    fontFamily: 'inherit',
                    height: 300,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                grid: {
                    strokeDashArray: 4,
                    padding: { top: -20, right: 0, left: 10, bottom: 0 }
                },
                series: [{
                    name: name,
                    data: data
                }],
                colors: ['#206bc4'],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: { fontSize: '12px' },
                        rotate: -45,
                        rotateAlways: false
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return '¥' + (val !== null && val !== undefined ? Number(val).toFixed(2) : '0');
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return '¥' + (val !== null && val !== undefined ? Number(val).toFixed(2) : '0');
                        }
                    }
                },
                noData: {
                    text: '暂无数据'
                }
            };
            getOrRenderChart(id, options);
        }

        // 新增用户柱状图
        function renderBarChart(id, name, labels, data) {
            const options = {
                chart: {
                    type: 'bar',
                    fontFamily: 'inherit',
                    height: 300,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 3
                    }
                },
                dataLabels: { enabled: false },
                grid: {
                    strokeDashArray: 4,
                    padding: { top: -20, right: 0, left: 10, bottom: 0 }
                },
                series: [{
                    name: name,
                    data: data
                }],
                colors: ['#2fb344'],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: { fontSize: '12px' },
                        rotate: -45,
                        rotateAlways: false
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return Math.round(val);
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return val + ' 人';
                        }
                    }
                },
                noData: {
                    text: '暂无数据'
                }
            };
            getOrRenderChart(id, options);
        }

        // 流量面积图
        function renderAreaChart(id, name, labels, data) {
            const options = {
                chart: {
                    type: 'area',
                    fontFamily: 'inherit',
                    height: 300,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    strokeDashArray: 4,
                    padding: { top: -20, right: 0, left: 10, bottom: 0 }
                },
                series: [{
                    name: name,
                    data: data
                }],
                colors: ['#f76707'],
                xaxis: {
                    categories: labels,
                    labels: {
                        style: { fontSize: '12px' },
                        rotate: -45,
                        rotateAlways: false
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return (val !== null && val !== undefined ? Number(val).toFixed(2) : '0') + ' GB';
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return (val !== null && val !== undefined ? Number(val).toFixed(2) : '0') + ' GB';
                        }
                    }
                },
                noData: {
                    text: '暂无数据'
                }
            };
            getOrRenderChart(id, options);
        }

        // 等级堆叠柱状图
        function renderStackedBar(id, labels, classSeries) {
            const classColorMap = {
                '0': '#6c757d',
                '1': '#206bc4',
                '2': '#2fb344',
                '3': '#f76707'
            };
            function getClassColor(cls) {
                if (classColorMap[cls]) return classColorMap[cls];
                return '#ae3ec9';
            }

            const sortedClasses = Object.keys(classSeries || {}).sort((a, b) => Number(a) - Number(b));
            const series = sortedClasses.map(cls => ({
                name: '等级 ' + cls,
                data: classSeries[cls] || []
            }));
            const colors = sortedClasses.map(cls => getClassColor(cls));

            const options = {
                chart: {
                    type: 'bar',
                    stacked: true,
                    fontFamily: 'inherit',
                    height: 350,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%',
                        borderRadius: 2
                    }
                },
                dataLabels: { enabled: false },
                series: series,
                colors: colors.length > 0 ? colors : ['#6c757d', '#206bc4', '#2fb344', '#f76707', '#ae3ec9'],
                grid: {
                    strokeDashArray: 4,
                    padding: { top: -20, right: 0, left: 10, bottom: 0 }
                },
                xaxis: {
                    categories: labels,
                    labels: {
                        style: { fontSize: '12px' },
                        rotate: -45,
                        rotateAlways: false
                    },
                    tooltip: { enabled: false }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return Math.round(val);
                        }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val) {
                            return val + ' 人';
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 100
                    }
                },
                noData: {
                    text: '暂无数据'
                }
            };
            getOrRenderChart(id, options);
        }

        // 商品销量（静态渲染，来自 Smarty 变量）
        function renderProductChart() {
            const items = (rawProductSales || []).slice(0, 10).reverse();
            const categories = items.map(p => p.product_name || ('商品 #' + p.product_id));
            const data = items.map(p => Number(p.order_count) || 0);

            const options = {
                chart: {
                    type: 'bar',
                    fontFamily: 'inherit',
                    height: 300,
                    parentHeightOffset: 0,
                    toolbar: { show: false },
                    animations: { enabled: true }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: '55%',
                        borderRadius: 3
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val + ' 单';
                    },
                    style: {
                        colors: ['#fff']
                    }
                },
                series: [{
                    name: '销量',
                    data: data
                }],
                colors: ['#206bc4'],
                grid: {
                    strokeDashArray: 4,
                    padding: { top: -20, right: 10, left: 10, bottom: 0 }
                },
                xaxis: {
                    categories: categories,
                    labels: {
                        formatter: function (val) {
                            return Math.round(val);
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: { fontSize: '12px' }
                    }
                },
                tooltip: {
                    theme: 'dark',
                    y: {
                        formatter: function (val, opt) {
                            const idx = opt.dataPointIndex;
                            const item = items[idx];
                            if (item && item.revenue !== undefined) {
                                return val + ' 单 (销售额: ￥' + Number(item.revenue).toFixed(2) + ')';
                            }
                            return val + ' 单';
                        }
                    }
                },
                noData: {
                    text: '暂无销售数据'
                }
            };
            getOrRenderChart('chart-products', options);
        }

        // 页面加载时初始化
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                loadCharts();
                renderProductChart();
            });
        } else {
            loadCharts();
            renderProductChart();
        }
        {/literal}
    </script>

    {include file='admin/footer.tpl'}
