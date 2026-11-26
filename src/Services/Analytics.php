<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use App\Models\HourlyUsage;
use App\Models\Invoice;
use App\Models\Node;
use App\Models\Order;
use App\Models\Payback;
use App\Models\Paylist;
use App\Models\SiteDailyStat;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Models\UserTrafficStat;
use App\Utils\Tools;
use DateTime;
use function array_chunk;
use function array_fill;
use function array_keys;
use function array_map;
use function array_shift;
use function array_slice;
use function array_sum;
use function array_values;
use function arsort;
use function checkdate;
use function compact;
use function count;
use function date;
use function floatval;
use function is_array;
use function is_null;
use function json_decode;
use function json_encode;
use function max;
use function min;
use function preg_match;
use function round;
use function sort;
use function str_contains;
use function str_pad;
use function strtolower;
use function strtotime;
use function substr;
use function time;
use function trim;
use function uasort;
use const SORT_NUMERIC;
use const STR_PAD_LEFT;

final class Analytics
{
    /**
     * 获取累计收入
     */
    public static function getIncome(string $req): float
    {
        $today = strtotime('00:00:00');
        $paylist = new Paylist();
        $number = match ($req) {
            'today' => $paylist->where('status', 1)
                ->whereBetween('datetime', [$today, time()])
                ->sum('total'),
            'yesterday' => $paylist->where('status', 1)
                ->whereBetween('datetime', [strtotime('-1 day', $today), $today])
                ->sum('total'),
            'this month' => $paylist->where('status', 1)
                ->whereBetween('datetime', [strtotime('first day of this month 00:00:00'), time()])
                ->sum('total'),
            default => $paylist->where('status', 1)->sum('total'),
        };

        return is_null($number) ? 0.00 : round(floatval($number), 2);
    }

    public static function getTotalUser(): int
    {
        return (new User())->count();
    }

    public static function getCheckinUser(): int
    {
        return (new User())->where('last_check_in_time', '>', 0)->count();
    }

    public static function getTodayCheckinUser(): int
    {
        return (new User())->where('last_check_in_time', '>', strtotime('today'))->count();
    }

    public static function getTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('u') + (new User())->sum('d'));
    }

    public static function getTodayTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('transfer_today'));
    }

    public static function getRawTodayTrafficUsage(): int
    {
        return (new User())->sum('transfer_today');
    }

    public static function getRawGbTodayTrafficUsage(): float
    {
        return Tools::bToGB((new User())->sum('transfer_today'));
    }

    public static function getLastTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('u') + (new User())->sum('d') - (new User())->sum('transfer_today'));
    }

    public static function getRawLastTrafficUsage(): int
    {
        return (new User())->sum('u') + (new User())->sum('d') - (new User())->sum('transfer_today');
    }

    public static function getRawGbLastTrafficUsage(): float
    {
        return Tools::bToGB((new User())->sum('u') + (new User())->sum('d') - (new User())->sum('transfer_today'));
    }

    public static function getUnusedTrafficUsage(): string
    {
        return Tools::autoBytes((new User())->sum('transfer_enable') - (new User())->sum('u') - (new User())->sum('d'));
    }

    public static function getRawUnusedTrafficUsage(): int
    {
        return (new User())->sum('transfer_enable') - (new User())->sum('u') - (new User())->sum('d');
    }

    public static function getRawGbUnusedTrafficUsage(): float
    {
        return Tools::bToGB((new User())->sum('transfer_enable') - (new User())->sum('u') - (new User())->sum('d'));
    }

    public static function getTotalTraffic(): string
    {
        return Tools::autoBytes((new User())->sum('transfer_enable'));
    }

    public static function getRawTotalTraffic(): int
    {
        return (new User())->sum('transfer_enable');
    }

    public static function getRawGbTotalTraffic(): float
    {
        return Tools::bToGB((new User())->sum('transfer_enable'));
    }

    public static function getTotalNode(): int
    {
        return (new Node())->where('node_heartbeat', '>', 0)->count();
    }

    public static function getAliveNode(): int
    {
        return (new Node())->where('node_heartbeat', '>', time() - 90)->count();
    }

    public static function getInactiveUser(): int
    {
        return (new User())->where('is_inactive', 1)->count();
    }

    public static function getActiveUser(): int
    {
        return (new User())->where('is_inactive', 0)->count();
    }

    public static function getUserHourlyUsage(int $user_id, string $date): array
    {
        $hourly_usage = (new HourlyUsage())->where('user_id', $user_id)->where('date', $date)->first();

        return $hourly_usage ? json_decode($hourly_usage->usage, true) : array_fill(0, 24, 0);
    }

    public static function getUserTodayHourlyUsage(int $user_id): array
    {
        $date = date('Y-m-d');

        return self::getUserHourlyUsage($user_id, $date);
    }

    public static function getUserPast24HoursUsage(int $user_id): array
    {
        $usage_cache = [];
        $usage = [];
        $labels = [];

        for ($i = 23; $i >= 0; $i--) {
            $timestamp = time() - $i * 3600;
            $date = date('Y-m-d', $timestamp);
            $hour = (int) date('H', $timestamp);
            $labels[] = date('H', $timestamp);

            if (! isset($usage_cache[$date])) {
                $usage_cache[$date] = self::getUserHourlyUsage($user_id, $date);
            }

            $usage[] = $usage_cache[$date][$hour] ?? 0;
        }

        return [
            'usage' => $usage,
            'labels' => $labels,
        ];
    }

    public static function getUserPast30DaysUsage(int $user_id): array
    {
        $stat = UserTrafficStat::getOrUpdate($user_id);
        $daily_usage = json_decode($stat->daily_usage, true);
        if (! is_array($daily_usage) || count($daily_usage) !== 30) {
            $daily_usage = array_fill(0, 30, 0);
        }

        array_shift($daily_usage);
        $daily_usage[] = 0;

        $today_traffic = (int) array_sum(self::getUserTodayHourlyUsage($user_id));
        $daily_usage[29] += $today_traffic;

        $labels = [];
        for ($i = 29; $i >= 0; $i--) {
            $labels[] = date('m-d', strtotime("-{$i} days"));
        }

        return [
            'usage' => $daily_usage,
            'labels' => $labels,
        ];
    }

    public static function getUserPast12MonthsUsage(int $user_id): array
    {
        $stat = UserTrafficStat::getOrUpdate($user_id);
        $monthly_usage = json_decode($stat->monthly_usage, true);
        if (! is_array($monthly_usage) || count($monthly_usage) !== 12) {
            $monthly_usage = array_fill(0, 12, 0);
        }

        if ($stat->monthly_last_month !== date('Y-m')) {
            array_shift($monthly_usage);
            $monthly_usage[] = 0;
        }

        $today_traffic = (int) array_sum(self::getUserTodayHourlyUsage($user_id));
        $monthly_usage[11] += $today_traffic;

        $labels = [];
        $dt = new DateTime('first day of this month');
        $dt->modify('-11 months');
        for ($i = 0; $i < 12; $i++) {
            $labels[] = $dt->format('Y-m');
            $dt->modify('+1 month');
        }

        return [
            'usage' => $monthly_usage,
            'labels' => $labels,
        ];
    }

    private const PAID_ORDER_STATUSES = ['activated', 'expired', 'superseded'];

    /**
     * 归一化用户等级快照为对齐长度的时间序列
     *
     * @param array<int, array<string|int, int>> $snapshots
     *
     * @return array<string, array<int, int>>
     */
    private static function normalizeClassSeries(array $snapshots): array
    {
        $allClasses = [];
        foreach ($snapshots as $snapshot) {
            foreach ($snapshot as $cls => $_cnt) {
                $allClasses[(string) $cls] = true;
            }
        }

        $sortedClasses = array_keys($allClasses);
        sort($sortedClasses, SORT_NUMERIC);

        $classSeries = [];
        foreach ($sortedClasses as $cls) {
            $series = [];
            foreach ($snapshots as $snapshot) {
                $series[] = (int) ($snapshot[$cls] ?? 0);
            }
            $classSeries[$cls] = $series;
        }

        return $classSeries;
    }

    /**
     * 解析日/月/年视图对应的时间区间及上期对比区间
     *
     * @return array{startDate: string, endDate: string, startTs: int, endTs: int, prevStartTs: int, prevEndTs: int}
     */
    private static function resolvePeriod(string $mode, string $month, string $year, string $date = ''): array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))
            || (int) substr($date, 0, 4) < 1970
            || (int) substr($date, 0, 4) > 2100
        ) {
            $date = date('Y-m-d');
        }
        if (! preg_match('/^\d{4}-\d{2}$/', $month)
            || ! checkdate((int) substr($month, 5, 2), 1, (int) substr($month, 0, 4))
            || (int) substr($month, 0, 4) < 1970
            || (int) substr($month, 0, 4) > 2100
        ) {
            $month = date('Y-m');
        }
        if (! preg_match('/^\d{4}$/', $year) || (int) $year < 1970 || (int) $year > 2100) {
            $year = date('Y');
        }

        $now = time();

        if ($mode === 'day') {
            $startTs = strtotime($date . ' 00:00:00');
            $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
            $prevStartTs = strtotime($prevDate . ' 00:00:00');
            $prevDayEndTs = strtotime($prevDate . ' 23:59:59');

            if ($date === date('Y-m-d')) {
                $endTs = $now;
                $prevEndTs = min($prevDayEndTs, $prevStartTs + max(0, $now - $startTs));
            } else {
                $endTs = strtotime($date . ' 23:59:59');
                $prevEndTs = $prevDayEndTs;
            }

            return [
                'startDate'   => $date,
                'endDate'     => $date,
                'startTs'     => $startTs,
                'endTs'       => $endTs,
                'prevStartTs' => $prevStartTs,
                'prevEndTs'   => $prevEndTs,
            ];
        }

        if ($mode === 'year') {
            $startDate = $year . '-01-01';
            $endDate = $year . '-12-31';
            $startTs = strtotime($startDate . ' 00:00:00');
            $prevYear = (string) ((int) $year - 1);
            $prevStartTs = strtotime($prevYear . '-01-01 00:00:00');
            $prevYearEndTs = strtotime($prevYear . '-12-31 23:59:59');

            if ($year === date('Y')) {
                $endTs = $now;
                $prevEndTs = min($prevYearEndTs, $prevStartTs + max(0, $now - $startTs));
            } else {
                $endTs = strtotime($endDate . ' 23:59:59');
                $prevEndTs = $prevYearEndTs;
            }

            return [
                'startDate'   => $startDate,
                'endDate'     => $endDate,
                'startTs'     => $startTs,
                'endTs'       => $endTs,
                'prevStartTs' => $prevStartTs,
                'prevEndTs'   => $prevEndTs,
            ];
        }

        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $startTs = strtotime($startDate . ' 00:00:00');
        $prevMonthStart = strtotime($startDate . ' -1 month');
        $prevMonthEndTs = strtotime(date('Y-m-t', $prevMonthStart) . ' 23:59:59');

        if ($month === date('Y-m')) {
            $endTs = $now;
            $prevStartTs = $prevMonthStart;
            $prevEndTs = min($prevMonthEndTs, $prevMonthStart + max(0, $now - $startTs));
        } else {
            $endTs = strtotime($endDate . ' 23:59:59');
            $prevStartTs = $prevMonthStart;
            $prevEndTs = $prevMonthEndTs;
        }

        return [
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'startTs'     => $startTs,
            'endTs'       => $endTs,
            'prevStartTs' => $prevStartTs,
            'prevEndTs'   => $prevEndTs,
        ];
    }

    /**
     * 计算当期与上期已支付流水的环比
     *
     * @return array{current: float, previous: float, diffPercent: float|null}
     */
    private static function computeIncomeComparison(int $startTs, int $endTs, int $prevStartTs, int $prevEndTs): array
    {
        $current = (float) (Paylist::where('status', 1)
            ->whereBetween('datetime', [$startTs, $endTs])
            ->sum('total') ?? 0);

        $previous = (float) (Paylist::where('status', 1)
            ->whereBetween('datetime', [$prevStartTs, $prevEndTs])
            ->sum('total') ?? 0);

        $diffPercent = $previous > 0
            ? round(($current - $previous) / $previous * 100, 1)
            : null;

        return [
            'current'     => round($current, 2),
            'previous'    => round($previous, 2),
            'diffPercent' => $diffPercent,
        ];
    }

    /**
     * 汇总指定日期 24 小时全站流量字节数
     *
     * @return array<int, int>
     */
    private static function sumHourlyUsageBytes(string $date): array
    {
        $hourlyBytes = array_fill(0, 24, 0);
        HourlyUsage::where('date', $date)->chunk(500, static function ($records) use (&$hourlyBytes): void {
            foreach ($records as $record) {
                $hours = json_decode((string) $record->usage, true);
                if (! is_array($hours)) {
                    continue;
                }
                for ($h = 0; $h < 24; $h++) {
                    $hourlyBytes[$h] += (int) ($hours[$h] ?? 0);
                }
            }
        });

        return $hourlyBytes;
    }

    /**
     * 为尚未落盘 SiteDailyStat 的当日生成实时统计行
     */
    private static function buildLiveDayRow(string $date): object
    {
        $dayStart = strtotime($date . ' 00:00:00');
        $dayEnd = time();
        $hourlyBytes = self::sumHourlyUsageBytes($date);

        $income = (float) (Paylist::where('status', 1)
            ->whereBetween('datetime', [$dayStart, $dayEnd])
            ->sum('total') ?? 0);
        $newUsers = User::whereBetween('reg_date', [$date . ' 00:00:00', $date . ' 23:59:59'])->count();
        $checkinUsers = User::whereBetween('last_check_in_time', [$dayStart, $dayEnd])->count();
        $activeUsers = User::where('is_inactive', 0)->count();
        $classSnapshot = self::getUserClassDistribution();

        return (object) [
            'date'           => $date,
            'income'         => round($income, 2),
            'new_users'      => $newUsers,
            'active_users'   => $activeUsers,
            'checkin_users'  => $checkinUsers,
            'traffic'        => array_sum($hourlyBytes),
            'class_snapshot' => json_encode($classSnapshot === [] ? (object) [] : $classSnapshot),
        ];
    }

    /**
     * 若区间覆盖今天且今天尚未落盘 SiteDailyStat，则追加今日实时数据行
     *
     * @param iterable<int, object> $rows
     *
     * @return array<int, object>
     */
    private static function appendTodayIfMissing(iterable $rows, string $startDate, string $endDate): array
    {
        $list = [];
        $today = date('Y-m-d');
        $hasToday = false;

        foreach ($rows as $row) {
            $list[] = $row;
            if ((string) $row->date === $today) {
                $hasToday = true;
            }
        }

        if (! $hasToday && $today >= $startDate && $today <= $endDate) {
            $list[] = self::buildLiveDayRow($today);
        }

        return $list;
    }

    /**
     * 日视图：查看指定某天的 24 小时分时明细与当日快照
     */
    public static function getSiteDailyStats(string $date): array
    {
        $period = self::resolvePeriod('day', date('Y-m'), date('Y'), $date);
        $resolvedDate = $period['startDate'];
        $dayStart = $period['startTs'];
        $dayEnd = $period['endTs'];
        $today = date('Y-m-d');

        $stat = SiteDailyStat::where('date', $resolvedDate)->first();

        $hourlyIncome = array_fill(0, 24, 0.0);
        $paylists = Paylist::where('status', 1)
            ->whereBetween('datetime', [$dayStart, $dayEnd])
            ->get(['total', 'datetime']);
        foreach ($paylists as $p) {
            $h = (int) date('H', (int) $p->datetime);
            $hourlyIncome[$h] += (float) $p->total;
        }
        $hourlyIncome = array_map(static fn (float $v): float => round($v, 2), $hourlyIncome);

        $hourlyNewUsers = array_fill(0, 24, 0);
        $regRows = User::whereBetween('reg_date', [$resolvedDate . ' 00:00:00', $resolvedDate . ' 23:59:59'])
            ->get(['reg_date']);
        foreach ($regRows as $u) {
            $h = (int) substr((string) $u->reg_date, 11, 2);
            if ($h >= 0 && $h < 24) {
                $hourlyNewUsers[$h]++;
            }
        }

        $hourlyBytes = null;
        if ($stat !== null && $stat->hourly_traffic !== null) {
            $decoded = json_decode((string) $stat->hourly_traffic, true);
            if (is_array($decoded) && count($decoded) === 24) {
                $hourlyBytes = array_map(static fn ($v): int => (int) $v, $decoded);
            }
        }
        if ($hourlyBytes === null) {
            $hourlyBytes = self::sumHourlyUsageBytes($resolvedDate);
        }
        $trafficGb = array_map(static fn (int $b): float => round($b / (1024 ** 3), 2), $hourlyBytes);

        if ($stat !== null) {
            $activeCount = (int) $stat->active_users;
            $checkinCount = (int) ($stat->checkin_users ?? 0);
            $snapshot = json_decode((string) $stat->class_snapshot, true) ?: [];
        } elseif ($resolvedDate === $today) {
            $activeCount = User::where('is_inactive', 0)->count();
            $checkinCount = User::whereBetween('last_check_in_time', [$dayStart, $dayEnd])->count();
            $snapshot = self::getUserClassDistribution();
        } else {
            $activeCount = 0;
            $checkinCount = 0;
            $snapshot = [];
        }

        $summaryIncome = ($paylists->isEmpty() && $stat !== null && (float) $stat->income > 0)
            ? round((float) $stat->income, 2)
            : round(array_sum($hourlyIncome), 2);
        $summaryNewUsers = ($regRows->isEmpty() && $stat !== null && (int) $stat->new_users > 0)
            ? (int) $stat->new_users
            : (int) array_sum($hourlyNewUsers);
        $hourlySumBytes = array_sum($hourlyBytes);
        $summaryTrafficGb = ($hourlySumBytes === 0 && $stat !== null && (int) $stat->traffic > 0)
            ? round((int) $stat->traffic / (1024 ** 3), 2)
            : round($hourlySumBytes / (1024 ** 3), 2);

        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = str_pad((string) $h, 2, '0', STR_PAD_LEFT) . ':00';
        }

        $incomeComparison = self::computeIncomeComparison(
            $period['startTs'],
            $period['endTs'],
            $period['prevStartTs'],
            $period['prevEndTs']
        );

        return [
            'date'             => $resolvedDate,
            'hours'            => $hours,
            'income'           => $hourlyIncome,
            'newUsers'         => $hourlyNewUsers,
            'trafficGb'        => $trafficGb,
            'activeUsers'      => [$activeCount],
            'checkinUsers'     => [$checkinCount],
            'classSnapshot'    => (object) $snapshot,
            'isLive'           => $stat === null && $resolvedDate === $today,
            'summary'          => [
                'income'       => $summaryIncome,
                'newUsers'     => $summaryNewUsers,
                'trafficGb'    => $summaryTrafficGb,
                'activeUsers'  => $activeCount,
                'checkinUsers' => $checkinCount,
            ],
            'incomeComparison' => $incomeComparison,
        ];
    }

    /**
     * 月视图：查指定月份每天的全站统计（若为本月则补充今日实时数据）
     * $month 格式 'YYYY-MM'
     */
    public static function getSiteDailyByMonth(string $month): array
    {
        $period = self::resolvePeriod('month', $month, date('Y'));
        $rawRows = SiteDailyStat::whereBetween('date', [$period['startDate'], $period['endDate']])
            ->orderBy('date')
            ->get();
        $rows = self::appendTodayIfMissing($rawRows, $period['startDate'], $period['endDate']);

        $dates        = [];
        $income       = [];
        $newUsers     = [];
        $activeUsers  = [];
        $checkinUsers = [];
        $trafficGb    = [];
        $snapshots    = [];

        foreach ($rows as $row) {
            $dates[]        = $row->date;
            $income[]       = (float) $row->income;
            $newUsers[]     = (int) $row->new_users;
            $activeUsers[]  = (int) $row->active_users;
            $checkinUsers[] = (int) ($row->checkin_users ?? 0);
            $trafficGb[]    = round($row->traffic / (1024 ** 3), 2);
            $snapshots[]    = json_decode((string) $row->class_snapshot, true) ?: [];
        }

        $classSeries = self::normalizeClassSeries($snapshots);
        $incomeComparison = self::computeIncomeComparison(
            $period['startTs'],
            $period['endTs'],
            $period['prevStartTs'],
            $period['prevEndTs']
        );

        return compact('dates', 'income', 'newUsers', 'activeUsers', 'checkinUsers', 'trafficGb', 'classSeries', 'incomeComparison');
    }

    /**
     * 按 YYYY-MM 聚合多天站点统计记录
     *
     * @param iterable<int, object> $rows
     *
     * @return array<string, array{income: float, new_users: int, active_users: int, checkin_users: int, traffic: int, class_snapshot: array<string, int>}>
     */
    private static function aggregateRowsByMonth(iterable $rows): array
    {
        $monthly = [];
        foreach ($rows as $row) {
            $ym = substr($row->date, 0, 7);
            if (! isset($monthly[$ym])) {
                $monthly[$ym] = [
                    'income'         => 0.0,
                    'new_users'      => 0,
                    'active_users'   => 0,
                    'checkin_users'  => 0,
                    'traffic'        => 0,
                    'class_snapshot' => [],
                ];
            }
            $monthly[$ym]['income']         += (float) $row->income;
            $monthly[$ym]['new_users']      += (int) $row->new_users;
            $monthly[$ym]['active_users']    = (int) $row->active_users;
            $monthly[$ym]['checkin_users']  += (int) ($row->checkin_users ?? 0);
            $monthly[$ym]['traffic']        += (int) $row->traffic;
            $monthly[$ym]['class_snapshot']  = json_decode((string) $row->class_snapshot, true) ?: [];
        }

        return $monthly;
    }

    /**
     * 年视图：指定年份全年12个月汇总（若为今年则补充今日实时数据）
     */
    public static function getSiteYearlyStats(string $year): array
    {
        $period = self::resolvePeriod('year', date('Y-m'), $year);
        $rawRows = SiteDailyStat::whereBetween('date', [$period['startDate'], $period['endDate']])
            ->orderBy('date')
            ->get();
        $rows = self::appendTodayIfMissing($rawRows, $period['startDate'], $period['endDate']);

        $monthly = self::aggregateRowsByMonth($rows);

        $months       = [];
        $income       = [];
        $newUsers     = [];
        $activeUsers  = [];
        $checkinUsers = [];
        $trafficGb    = [];
        $snapshots    = [];

        $resolvedYear = substr($period['startDate'], 0, 4);
        for ($m = 1; $m <= 12; $m++) {
            $ym = $resolvedYear . '-' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $d = $monthly[$ym] ?? [
                'income'         => 0.0,
                'new_users'      => 0,
                'active_users'   => 0,
                'checkin_users'  => 0,
                'traffic'        => 0,
                'class_snapshot' => [],
            ];
            $months[]       = $ym;
            $income[]       = round($d['income'], 2);
            $newUsers[]     = $d['new_users'];
            $activeUsers[]  = $d['active_users'];
            $checkinUsers[] = $d['checkin_users'];
            $trafficGb[]    = round($d['traffic'] / (1024 ** 3), 2);
            $snapshots[]    = $d['class_snapshot'];
        }

        $classSeries = self::normalizeClassSeries($snapshots);
        $incomeComparison = self::computeIncomeComparison(
            $period['startTs'],
            $period['endTs'],
            $period['prevStartTs'],
            $period['prevEndTs']
        );

        return compact('months', 'income', 'newUsers', 'activeUsers', 'checkinUsers', 'trafficGb', 'classSeries', 'incomeComparison');
    }

    /**
     * 本月 vs 上月同期收入对比
     */
    public static function getMonthlyIncomeComparison(): array
    {
        $thisMonthStart         = strtotime('first day of this month 00:00:00');
        $now                    = time();
        $elapsed                = $now - $thisMonthStart;
        $lastMonthStart         = strtotime('first day of last month 00:00:00');
        $lastMonthSamePeriodEnd = $lastMonthStart + $elapsed;

        $comp = self::computeIncomeComparison($thisMonthStart, $now, $lastMonthStart, $lastMonthSamePeriodEnd);

        return [
            'this_month'             => $comp['current'],
            'last_month_same_period' => $comp['previous'],
            'diff_percent'           => $comp['diffPercent'] ?? 0.0,
        ];
    }

    /**
     * 各等级用户数（实时）
     */
    public static function getUserClassDistribution(): array
    {
        $rows = (new User())->groupBy('class')
            ->selectRaw('`class`, COUNT(*) as cnt')
            ->orderBy('class')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row->class] = (int) $row->cnt;
        }
        return $result;
    }

    private static function queryProductSalesRanking(?int $startTs, ?int $endTs): array
    {
        $query = (new Order())
            ->whereIn('status', self::PAID_ORDER_STATUSES)
            ->groupBy('product_id', 'product_name', 'product_type')
            ->selectRaw('product_id, product_name, product_type, COUNT(*) as order_count, SUM(price) as revenue')
            ->orderByDesc('order_count')
            ->limit(10);

        if ($startTs !== null && $endTs !== null) {
            $query->whereBetween('create_time', [$startTs, $endTs]);
        }

        return $query->get()->toArray();
    }

    /**
     * 日视图：指定某天商品销量排行（直接查 order 表）
     */
    public static function getProductSalesRankingByDate(string $date): array
    {
        $period = self::resolvePeriod('day', date('Y-m'), date('Y'), $date);

        return self::queryProductSalesRanking($period['startTs'], $period['endTs']);
    }

    /**
     * 月视图：指定月份或全部时间商品销量排行（直接查 order 表）
     * $month: 'YYYY-MM' 或 null（查全部）
     */
    public static function getProductSalesRanking(?string $month = null): array
    {
        if ($month === null) {
            return self::queryProductSalesRanking(null, null);
        }

        $period = self::resolvePeriod('month', $month, date('Y'));

        return self::queryProductSalesRanking($period['startTs'], $period['endTs']);
    }

    /**
     * 年视图：指定年份商品销量排行（直接查 order 表）
     */
    public static function getProductSalesRankingByYear(string $year): array
    {
        $period = self::resolvePeriod('year', date('Y-m'), $year);

        return self::queryProductSalesRanking($period['startTs'], $period['endTs']);
    }

    /**
     * 将订阅 User-Agent 与订阅格式归一为常见客户端名称
     */
    public static function detectClientName(string $ua, string $type): string
    {
        $lowerUa = strtolower(trim($ua));
        if ($lowerUa !== '') {
            $uaRules = [
                'clash-verge'  => 'Clash Verge',
                'clashverge'   => 'Clash Verge',
                'flclash'      => 'FlClash',
                'mihomo.party' => 'Mihomo Party',
                'mihomoparty'  => 'Mihomo Party',
                'stash'        => 'Stash',
                'shadowrocket' => 'Shadowrocket',
                'quantumult'   => 'Quantumult X',
                'loon'         => 'Loon',
                'surge'        => 'Surge',
                'egern'        => 'Egern',
                'karing'       => 'Karing',
                'hiddify'      => 'Hiddify',
                'nekobox'      => 'NekoBox',
                'nekoray'      => 'NekoBox',
                'sing-box'     => 'sing-box',
                'sfi/'         => 'sing-box',
                'sfa/'         => 'sing-box',
                'sfm/'         => 'sing-box',
                'sft/'         => 'sing-box',
                'v2rayng'      => 'v2rayNG',
                'v2rayn'       => 'v2rayN',
                'surfboard'    => 'Surfboard',
                'openclash'    => 'OpenClash',
                'passwall'     => 'PassWall',
                'clashx'       => 'ClashX',
                'mihomo'       => 'Mihomo / Meta',
                'clash.meta'   => 'Mihomo / Meta',
                'clashmeta'    => 'Mihomo / Meta',
                'clash'        => 'Clash (Other)',
            ];

            foreach ($uaRules as $needle => $label) {
                if (str_contains($lowerUa, $needle)) {
                    return $label;
                }
            }
        }

        $typeMap = [
            'clash'     => 'Clash 订阅',
            'singbox'   => 'sing-box 订阅',
            'qx'        => 'Quantumult X',
            'hysteria2' => 'Hysteria2',
            'hysteria'  => 'Hysteria2',
            'hy2'       => 'Hysteria2',
            'v2ray'     => 'V2Ray',
            'v2rayjson' => 'V2Ray',
            'trojan'    => 'Trojan',
            'ss'        => 'Shadowsocks',
            'sip002'    => 'Shadowsocks',
            'sip008'    => 'Shadowsocks',
            'json'      => 'JSON 订阅',
        ];

        $lowerType = strtolower(trim($type));

        return $typeMap[$lowerType] ?? ($lowerType !== '' ? '其他 (' . $lowerType . ')' : '未知客户端');
    }

    /**
     * 统计指定时间窗口内的唯一订阅客户端分布（按用户去重）
     *
     * @return array<string, int>
     */
    public static function aggregateSubscribeClients(int $start, int $end): array
    {
        $seen = [];
        $rows = SubscribeLog::whereBetween('request_time', [$start, $end])
            ->select(['user_id', 'type', 'request_user_agent'])
            ->distinct()
            ->cursor();

        foreach ($rows as $row) {
            $client = self::detectClientName((string) $row->request_user_agent, (string) $row->type);
            $seen[$client][(int) $row->user_id] = true;
        }

        $counts = [];
        foreach ($seen as $client => $users) {
            $counts[$client] = count($users);
        }
        arsort($counts);

        return $counts;
    }

    /**
     * 营收拆解与转化质量分析（支付网关、商品构成、新购/复购、优惠码、自动续费健康度）
     */
    public static function getRevenueAnalysis(string $mode, string $month, string $year, string $date = ''): array
    {
        $period = self::resolvePeriod($mode, $month, $year, $date);
        $startTs = $period['startTs'];
        $endTs = $period['endTs'];

        $gateways = self::buildGatewayBreakdown($startTs, $endTs);
        $orderTypes = self::buildOrderTypeBreakdown($startTs, $endTs);
        $invoiceMethods = self::buildInvoiceMethodBreakdown($startTs, $endTs);
        $mixAndSummary = self::buildConversionAndPurchaseMix($period['startDate'], $period['endDate'], $startTs, $endTs);
        $topCoupons = self::buildTopCoupons($startTs, $endTs);
        $autoRenew = self::buildAutoRenewSnapshot();

        return [
            'gateways'       => $gateways,
            'orderTypes'     => $orderTypes,
            'invoiceMethods' => $invoiceMethods,
            'purchaseMix'    => $mixAndSummary['purchaseMix'],
            'summary'        => $mixAndSummary['summary'],
            'topCoupons'     => $topCoupons,
            'autoRenew'      => $autoRenew,
        ];
    }

    private static function buildGatewayBreakdown(int $startTs, int $endTs): array
    {
        $rows = Paylist::where('status', 1)
            ->whereBetween('datetime', [$startTs, $endTs])
            ->groupBy('gateway')
            ->selectRaw('gateway, COUNT(*) as cnt, COALESCE(SUM(total), 0) as amount')
            ->orderByDesc('amount')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $name = trim((string) $row->gateway);
            $result[] = [
                'gateway' => $name !== '' ? $name : '未知渠道',
                'count'   => (int) $row->cnt,
                'amount'  => round((float) $row->amount, 2),
            ];
        }

        return $result;
    }

    private static function buildOrderTypeBreakdown(int $startTs, int $endTs): array
    {
        $labels = [
            'tabp'      => '时间流量包',
            'time'      => '时间套餐',
            'bandwidth' => '流量包',
            'topup'     => '余额充值',
        ];

        $rows = Order::whereIn('status', self::PAID_ORDER_STATUSES)
            ->whereBetween('create_time', [$startTs, $endTs])
            ->groupBy('product_type')
            ->selectRaw('product_type, COUNT(*) as cnt, COALESCE(SUM(price), 0) as revenue')
            ->orderByDesc('revenue')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $type = (string) $row->product_type;
            $result[] = [
                'type'    => $type,
                'label'   => $labels[$type] ?? ($type !== '' ? $type : '其他'),
                'count'   => (int) $row->cnt,
                'revenue' => round((float) $row->revenue, 2),
            ];
        }

        return $result;
    }

    private static function buildInvoiceMethodBreakdown(int $startTs, int $endTs): array
    {
        $labels = [
            'paid_gateway'     => '在线网关支付',
            'paid_balance'     => '账户余额支付',
            'paid_admin'       => '管理员手动入账',
            'refunded_balance' => '已退回余额',
        ];

        $rows = Invoice::whereIn('status', array_keys($labels))
            ->whereBetween('pay_time', [$startTs, $endTs])
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(price), 0) as amount')
            ->orderByDesc('amount')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $status = (string) $row->status;
            $result[] = [
                'status' => $status,
                'label'  => $labels[$status] ?? $status,
                'count'  => (int) $row->cnt,
                'amount' => round((float) $row->amount, 2),
            ];
        }

        return $result;
    }

    private static function buildConversionAndPurchaseMix(string $startDate, string $endDate, int $startTs, int $endTs): array
    {
        $agg = Order::whereIn('status', self::PAID_ORDER_STATUSES)
            ->whereBetween('create_time', [$startTs, $endTs])
            ->selectRaw('COUNT(*) as cnt, COUNT(DISTINCT user_id) as users, COALESCE(SUM(price), 0) as rev')
            ->first();

        $paidOrders = (int) ($agg->cnt ?? 0);
        $paidUsers = (int) ($agg->users ?? 0);
        $totalRevenue = round((float) ($agg->rev ?? 0), 2);
        $arppu = $paidUsers > 0 ? round($totalRevenue / $paidUsers, 2) : 0.0;

        $couponAgg = Order::whereIn('status', self::PAID_ORDER_STATUSES)
            ->whereBetween('create_time', [$startTs, $endTs])
            ->where('coupon', '!=', '')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(price), 0) as rev')
            ->first();

        $regStart = $startDate . ' 00:00:00';
        $regEnd = $endDate . ' 23:59:59';
        $newRegUsers = User::whereBetween('reg_date', [$regStart, $regEnd])->count();

        $periodUserIds = Order::whereIn('status', self::PAID_ORDER_STATUSES)
            ->whereBetween('create_time', [$startTs, $endTs])
            ->distinct()
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $newBuyStats = self::countFirstTimeOrders($periodUserIds, $startTs, $endTs);
        $newRegPaidUsers = self::countNewRegisteredBuyers($periodUserIds, $regStart, $regEnd);

        $newCount = $newBuyStats['count'];
        $newRevenue = $newBuyStats['revenue'];

        return [
            'purchaseMix' => [
                'newCount'       => $newCount,
                'newRevenue'     => round($newRevenue, 2),
                'renewalCount'   => max(0, $paidOrders - $newCount),
                'renewalRevenue' => max(0.0, round($totalRevenue - $newRevenue, 2)),
            ],
            'summary' => [
                'paidOrders'        => $paidOrders,
                'paidUsers'         => $paidUsers,
                'totalRevenue'      => $totalRevenue,
                'arppu'             => $arppu,
                'newRegUsers'       => $newRegUsers,
                'newRegPaidUsers'   => $newRegPaidUsers,
                'regConversionRate' => $newRegUsers > 0 ? round($newRegPaidUsers / $newRegUsers * 100, 1) : 0.0,
                'couponOrders'      => (int) ($couponAgg->cnt ?? 0),
                'couponRevenue'     => round((float) ($couponAgg->rev ?? 0), 2),
            ],
        ];
    }

    /**
     * 统计所选期内“用户历史首笔已支付订单”落在期内的单数与金额
     *
     * @param array<int, int> $userIds
     *
     * @return array{count: int, revenue: float}
     */
    private static function countFirstTimeOrders(array $userIds, int $startTs, int $endTs): array
    {
        if ($userIds === []) {
            return ['count' => 0, 'revenue' => 0.0];
        }

        $firstOrderIds = [];
        foreach (array_chunk($userIds, 500) as $chunk) {
            $rows = Order::whereIn('status', self::PAID_ORDER_STATUSES)
                ->whereIn('user_id', $chunk)
                ->groupBy('user_id')
                ->selectRaw('MIN(id) as first_id')
                ->get();
            foreach ($rows as $row) {
                $firstOrderIds[] = (int) $row->first_id;
            }
        }

        $count = 0;
        $revenue = 0.0;
        foreach (array_chunk($firstOrderIds, 500) as $idChunk) {
            $agg = Order::whereIn('id', $idChunk)
                ->whereBetween('create_time', [$startTs, $endTs])
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(price), 0) as rev')
                ->first();
            $count += (int) ($agg->cnt ?? 0);
            $revenue += (float) ($agg->rev ?? 0);
        }

        return ['count' => $count, 'revenue' => $revenue];
    }

    /**
     * @param array<int, int> $periodUserIds
     */
    private static function countNewRegisteredBuyers(array $periodUserIds, string $regStart, string $regEnd): int
    {
        if ($periodUserIds === []) {
            return 0;
        }

        $count = 0;
        foreach (array_chunk($periodUserIds, 500) as $chunk) {
            $count += User::whereIn('id', $chunk)
                ->whereBetween('reg_date', [$regStart, $regEnd])
                ->count();
        }

        return $count;
    }

    private static function buildTopCoupons(int $startTs, int $endTs): array
    {
        return Order::whereIn('status', self::PAID_ORDER_STATUSES)
            ->whereBetween('create_time', [$startTs, $endTs])
            ->where('coupon', '!=', '')
            ->groupBy('coupon')
            ->selectRaw('coupon as code, COUNT(*) as order_count, COALESCE(SUM(price), 0) as revenue')
            ->orderByDesc('order_count')
            ->limit(8)
            ->get()
            ->toArray();
    }

    private static function buildAutoRenewSnapshot(): array
    {
        $enabledCount = Order::where('status', 'activated')
            ->where('auto_renew', 1)
            ->count();

        $failedCount = Order::where('status', 'activated')
            ->where('auto_renew', 2)
            ->count();

        $failedRows = Order::where('status', 'activated')
            ->where('auto_renew', 2)
            ->orderByDesc('update_time')
            ->limit(10)
            ->get(['id', 'user_id', 'product_name', 'price', 'update_time']);

        $userIds = $failedRows->pluck('user_id')->unique()->filter()->all();
        $users = $userIds === []
            ? []
            : User::whereIn('id', $userIds)->get(['id', 'user_name', 'email', 'money', 'class_expire'])->keyBy('id');

        $failedOrders = [];
        foreach ($failedRows as $row) {
            $u = $users[$row->user_id] ?? null;
            $failedOrders[] = [
                'order_id'     => (int) $row->id,
                'user_id'      => (int) $row->user_id,
                'user_name'    => (string) ($u->user_name ?? ('#' . $row->user_id)),
                'email'        => (string) ($u->email ?? ''),
                'money'        => round((float) ($u->money ?? 0), 2),
                'class_expire' => (string) ($u->class_expire ?? ''),
                'product_name' => (string) $row->product_name,
                'price'        => round((float) $row->price, 2),
            ];
        }

        return [
            'enabledCount' => $enabledCount,
            'failedCount'  => $failedCount,
            'failedOrders' => $failedOrders,
        ];
    }

    /**
     * 24小时全站高峰时段分布 & 订阅客户端占比（优先读 site_daily_stat，近期未入库日期补查保留日志）
     */
    public static function getTrafficAndClientStats(string $mode, string $month, string $year, string $date = ''): array
    {
        $period = self::resolvePeriod($mode, $month, $year, $date);
        $startDate = $period['startDate'];
        $endDate = $period['endDate'];

        $dailyRows = SiteDailyStat::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get(['date', 'hourly_traffic', 'client_snapshot']);

        $hourlyBytes = array_fill(0, 24, 0);
        $hourDays = array_fill(0, 24, 0);
        $hasHourlyDates = [];
        $includedDates = [];
        $clientCounts = [];
        $hasClientDates = [];
        $today = date('Y-m-d');
        $currentHour = (int) date('H');

        foreach ($dailyRows as $row) {
            $hours = $row->hourly_traffic !== null ? json_decode((string) $row->hourly_traffic, true) : null;
            if (is_array($hours) && count($hours) === 24) {
                $hasHourlyDates[$row->date] = true;
                $includedDates[$row->date] = true;
                $maxH = $row->date === $today ? $currentHour : 23;
                for ($h = 0; $h < 24; $h++) {
                    $hourlyBytes[$h] += (int) ($hours[$h] ?? 0);
                    if ($h <= $maxH) {
                        $hourDays[$h]++;
                    }
                }
            }

            $clients = $row->client_snapshot !== null ? json_decode((string) $row->client_snapshot, true) : null;
            if (is_array($clients)) {
                $hasClientDates[$row->date] = true;
                foreach ($clients as $name => $cnt) {
                    $clientCounts[$name] = ($clientCounts[$name] ?? 0) + (int) $cnt;
                }
            }
        }

        $usedHourlyFallback = self::mergeHourlyUsageFallback(
            $startDate,
            $endDate,
            $hasHourlyDates,
            $hourlyBytes,
            $hourDays,
            $includedDates
        );
        $usedClientFallback = self::mergeSubscribeClientFallback($startDate, $endDate, $hasClientDates, $clientCounts);

        $hourlyGb = array_map(static fn (int $b): float => round($b / (1024 ** 3), 2), $hourlyBytes);
        $hourlyAvgGb = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyAvgGb[] = $hourDays[$h] > 0
                ? round($hourlyBytes[$h] / $hourDays[$h] / (1024 ** 3), 2)
                : 0.0;
        }

        $peakHour = 0;
        $peakAvgGb = 0.0;
        $peakTotalHour = 0;
        $peakGb = 0.0;
        for ($h = 0; $h < 24; $h++) {
            if ($hourlyAvgGb[$h] > $peakAvgGb) {
                $peakAvgGb = $hourlyAvgGb[$h];
                $peakHour = $h;
            }
            if ($hourlyGb[$h] > $peakGb) {
                $peakGb = $hourlyGb[$h];
                $peakTotalHour = $h;
            }
        }

        return [
            'hourlyAvgGb'     => $hourlyAvgGb,
            'hourlyGb'        => $hourlyGb,
            'daysCount'       => count($includedDates),
            'avgDailyTotalGb' => round(array_sum($hourlyAvgGb), 2),
            'totalGb'         => round(array_sum($hourlyGb), 2),
            'peakHour'        => $peakHour,
            'peakAvgGb'       => $peakAvgGb,
            'peakTotalHour'   => $peakTotalHour,
            'peakGb'          => $peakGb,
            'hasFallback'     => $usedHourlyFallback || $usedClientFallback,
            'clients'         => self::formatClientTopList($clientCounts, 8),
        ];
    }

    /**
     * @param array<string, bool> $skipDates
     * @param array<int, int>     $hourlyBytes
     * @param array<int, int>     $hourDays
     * @param array<string, bool> $includedDates
     */
    private static function mergeHourlyUsageFallback(
        string $startDate,
        string $endDate,
        array $skipDates,
        array &$hourlyBytes,
        array &$hourDays,
        array &$includedDates
    ): bool {
        $retentionDays = max(1, (int) Config::obtain('traffic_log_retention_days'));
        $minRetained = date('Y-m-d', strtotime("-{$retentionDays} days"));
        $today = date('Y-m-d');
        $fallbackStart = max($startDate, $minRetained);
        $fallbackEnd = min($endDate, $today);

        if ($fallbackStart > $fallbackEnd) {
            return false;
        }

        $fallbackDates = [];
        HourlyUsage::whereBetween('date', [$fallbackStart, $fallbackEnd])
            ->chunk(500, static function ($records) use ($skipDates, &$hourlyBytes, &$fallbackDates): void {
                foreach ($records as $record) {
                    if (isset($skipDates[$record->date])) {
                        continue;
                    }
                    $hours = json_decode((string) $record->usage, true);
                    if (! is_array($hours)) {
                        continue;
                    }
                    $fallbackDates[$record->date] = true;
                    for ($h = 0; $h < 24; $h++) {
                        $hourlyBytes[$h] += (int) ($hours[$h] ?? 0);
                    }
                }
            });

        $currentHour = (int) date('H');
        foreach ($fallbackDates as $dateStr => $_present) {
            $includedDates[$dateStr] = true;
            $maxH = $dateStr === $today ? $currentHour : 23;
            for ($h = 0; $h <= $maxH; $h++) {
                $hourDays[$h]++;
            }
        }

        return $fallbackDates !== [];
    }

    /**
     * @param array<string, bool> $skipDates
     * @param array<string, int>  $clientCounts
     */
    private static function mergeSubscribeClientFallback(
        string $startDate,
        string $endDate,
        array $skipDates,
        array &$clientCounts
    ): bool {
        $retentionDays = max(1, (int) Config::obtain('subscribe_log_retention_days'));
        $minRetained = date('Y-m-d', strtotime("-{$retentionDays} days"));
        $today = date('Y-m-d');
        $fallbackStart = max($startDate, $minRetained);
        $fallbackEnd = min($endDate, $today);

        if ($fallbackStart > $fallbackEnd) {
            return false;
        }

        $seen = [];
        $used = false;
        $rows = SubscribeLog::whereBetween('request_time', [
            strtotime($fallbackStart . ' 00:00:00'),
            strtotime($fallbackEnd . ' 23:59:59'),
        ])->select(['user_id', 'type', 'request_user_agent', 'request_time'])->cursor();

        foreach ($rows as $row) {
            $rowDate = date('Y-m-d', (int) $row->request_time);
            if (isset($skipDates[$rowDate])) {
                continue;
            }
            $client = self::detectClientName((string) $row->request_user_agent, (string) $row->type);
            $key = $rowDate . ':' . $client . ':' . (int) $row->user_id;
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $clientCounts[$client] = ($clientCounts[$client] ?? 0) + 1;
                $used = true;
            }
        }

        return $used;
    }

    /**
     * @param array<string, int> $clientCounts
     *
     * @return array<int, array{name: string, count: int}>
     */
    private static function formatClientTopList(array $clientCounts, int $limit): array
    {
        arsort($clientCounts);
        $list = [];
        $otherSum = 0;
        $idx = 0;

        foreach ($clientCounts as $name => $count) {
            if ($idx < $limit) {
                $list[] = ['name' => (string) $name, 'count' => (int) $count];
            } else {
                $otherSum += (int) $count;
            }
            $idx++;
        }

        if ($otherSum > 0) {
            $list[] = ['name' => '其他客户端', 'count' => $otherSum];
        }

        return $list;
    }

    /**
     * 当前周期节点负载排行 + 用户留存/流失预警 + 邀请返利排行
     */
    public static function getNodeAndRetentionStats(string $mode, string $month, string $year, string $date = ''): array
    {
        $period = self::resolvePeriod($mode, $month, $year, $date);

        return [
            'nodeStats' => self::buildCurrentNodeLoad(),
            'retention' => self::buildRetentionAlerts(),
            'referrals' => self::buildReferralRanking($period['startTs'], $period['endTs']),
        ];
    }

    private static function buildCurrentNodeLoad(): array
    {
        $nodes = (new Node())->where('type', 1)->orderByDesc('node_bandwidth')->get();

        $list = [];
        $protocolAgg = [];
        $onlineNodes = 0;
        $totalUsedBytes = 0;
        $totalOnlineUsers = 0;
        $idleNodesCount = 0;
        $nearLimitCount = 0;

        foreach ($nodes as $node) {
            $bw = (int) $node->node_bandwidth;
            $limit = (int) $node->node_bandwidth_limit;
            $online = (int) $node->online_user;
            $status = $node->getNodeOnlineStatus();
            $protocol = $node->sort();
            $usagePercent = $limit > 0 ? round($bw / $limit * 100, 1) : null;

            if ($status === 1) {
                $onlineNodes++;
            }
            if ($bw === 0 && $online === 0) {
                $idleNodesCount++;
            }
            if ($usagePercent !== null && $usagePercent >= 80.0) {
                $nearLimitCount++;
            }

            $totalUsedBytes += $bw;
            $totalOnlineUsers += $online;

            $list[] = [
                'id'            => (int) $node->id,
                'name'          => (string) $node->name,
                'protocol'      => $protocol,
                'node_class'    => (int) $node->node_class,
                'traffic_rate'  => (float) $node->traffic_rate,
                'online_user'   => $online,
                'used_gb'       => round($bw / (1024 ** 3), 2),
                'limit_gb'      => $limit > 0 ? round($limit / (1024 ** 3), 2) : 0.0,
                'usage_percent' => $usagePercent,
                'online_status' => $status,
            ];

            if (! isset($protocolAgg[$protocol])) {
                $protocolAgg[$protocol] = ['protocol' => $protocol, 'nodes' => 0, 'used_bytes' => 0, 'online_users' => 0];
            }
            $protocolAgg[$protocol]['nodes']++;
            $protocolAgg[$protocol]['used_bytes'] += $bw;
            $protocolAgg[$protocol]['online_users'] += $online;
        }

        $protocols = [];
        foreach ($protocolAgg as $p) {
            $protocols[] = [
                'protocol'     => $p['protocol'],
                'nodes'        => $p['nodes'],
                'used_gb'      => round($p['used_bytes'] / (1024 ** 3), 2),
                'online_users' => $p['online_users'],
            ];
        }
        uasort($protocols, static fn (array $a, array $b): int => $b['used_gb'] <=> $a['used_gb']);

        return [
            'summary' => [
                'totalNodes'       => count($list),
                'onlineNodes'      => $onlineNodes,
                'totalUsedGb'      => round($totalUsedBytes / (1024 ** 3), 2),
                'totalOnlineUsers' => $totalOnlineUsers,
                'idleNodesCount'   => $idleNodesCount,
                'nearLimitCount'   => $nearLimitCount,
            ],
            'nodes'     => array_slice($list, 0, 20),
            'protocols' => array_values($protocols),
        ];
    }

    private static function buildRetentionAlerts(): array
    {
        $now = time();
        $nowStr = date('Y-m-d H:i:s', $now);
        $in7DaysStr = date('Y-m-d H:i:s', $now + 7 * 86400);

        $expiringRows = User::where('class', '>', 0)
            ->whereBetween('class_expire', [$nowStr, $in7DaysStr])
            ->orderBy('class_expire')
            ->get(['id', 'user_name', 'email', 'class', 'class_expire', 'last_use_time']);

        $expiringIds = $expiringRows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $autoRenewMap = [];
        foreach (array_chunk($expiringIds, 500) as $chunk) {
            $uids = Order::whereIn('user_id', $chunk)
                ->where('status', 'activated')
                ->where('auto_renew', 1)
                ->pluck('user_id')
                ->all();
            foreach ($uids as $uid) {
                $autoRenewMap[(int) $uid] = true;
            }
        }

        $expiringNoAutoRenewList = [];
        $expiringNoAutoRenewCount = 0;
        foreach ($expiringRows as $u) {
            if (isset($autoRenewMap[(int) $u->id])) {
                continue;
            }
            $expiringNoAutoRenewCount++;
            if (count($expiringNoAutoRenewList) < 10) {
                $expiringNoAutoRenewList[] = [
                    'id'            => (int) $u->id,
                    'user_name'     => (string) $u->user_name,
                    'email'         => (string) $u->email,
                    'class'         => (int) $u->class,
                    'class_expire'  => (string) $u->class_expire,
                    'last_use_days' => (int) $u->last_use_time > 0
                        ? (int) (($now - (int) $u->last_use_time) / 86400)
                        : null,
                ];
            }
        }

        $silentCutoff = $now - 7 * 86400;
        $silentPaidCount = User::where('class', '>', 0)
            ->where('class_expire', '>', $nowStr)
            ->where(static function ($q) use ($silentCutoff): void {
                $q->where('last_use_time', '<', $silentCutoff)
                    ->orWhere('last_use_time', 0);
            })
            ->count();

        return [
            'expiring7DaysCount'       => $expiringRows->count(),
            'expiringNoAutoRenewCount' => $expiringNoAutoRenewCount,
            'silentPaidCount'          => $silentPaidCount,
            'expiringUsers'            => $expiringNoAutoRenewList,
        ];
    }

    private static function buildReferralRanking(int $startTs, int $endTs): array
    {
        $summaryAgg = Payback::whereBetween('datetime', [$startTs, $endTs])
            ->selectRaw('COUNT(*) as cnt, COUNT(DISTINCT userid) as buyers, COALESCE(SUM(total), 0) as total_sales, COALESCE(SUM(ref_get), 0) as total_reward')
            ->first();

        $topRows = Payback::whereBetween('datetime', [$startTs, $endTs])
            ->groupBy('ref_by')
            ->selectRaw('ref_by, COUNT(*) as order_count, COUNT(DISTINCT userid) as invited_buyers, COALESCE(SUM(total), 0) as total_sales, COALESCE(SUM(ref_get), 0) as total_reward')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();

        $refIds = $topRows->pluck('ref_by')->filter()->map(static fn ($id): int => (int) $id)->all();
        $users = $refIds === []
            ? []
            : User::whereIn('id', $refIds)->get(['id', 'user_name', 'email'])->keyBy('id');

        $topReferrers = [];
        foreach ($topRows as $row) {
            $refId = (int) $row->ref_by;
            $u = $users[$refId] ?? null;
            $topReferrers[] = [
                'ref_by'         => $refId,
                'user_name'      => (string) ($u->user_name ?? ('用户 #' . $refId)),
                'email'          => (string) ($u->email ?? ''),
                'order_count'    => (int) $row->order_count,
                'invited_buyers' => (int) $row->invited_buyers,
                'total_sales'    => round((float) $row->total_sales, 2),
                'total_reward'   => round((float) $row->total_reward, 2),
            ];
        }

        return [
            'summary' => [
                'orderCount'    => (int) ($summaryAgg->cnt ?? 0),
                'invitedBuyers' => (int) ($summaryAgg->buyers ?? 0),
                'totalSales'    => round((float) ($summaryAgg->total_sales ?? 0), 2),
                'totalReward'   => round((float) ($summaryAgg->total_reward ?? 0), 2),
            ],
            'topReferrers' => $topReferrers,
        ];
    }
}
