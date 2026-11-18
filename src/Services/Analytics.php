<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HourlyUsage;
use App\Models\Node;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\SiteDailyStat;
use App\Models\User;
use App\Models\UserTrafficStat;
use App\Utils\Tools;
use DateTime;
use function array_fill;
use function array_shift;
use function array_sum;
use function compact;
use function count;
use function date;
use function floatval;
use function is_array;
use function is_null;
use function json_decode;
use function round;
use function strtotime;
use function substr;
use function time;

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

    /**
     * 日视图：查某月每天的全站统计
     * $month 格式 'YYYY-MM'，返回该月每天一条记录
     */
    public static function getSiteDailyByMonth(string $month): array
    {
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start)); // 该月最后一天

        $rows = SiteDailyStat::whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        $dates       = [];
        $income      = [];
        $newUsers    = [];
        $activeUsers = [];
        $trafficGb   = [];
        $classSeries = []; // ['0' => [n,n,...], '1' => [n,n,...]]

        foreach ($rows as $row) {
            $dates[]       = $row->date;
            $income[]      = (float) $row->income;
            $newUsers[]    = (int)   $row->new_users;
            $activeUsers[] = (int)   $row->active_users;
            $trafficGb[]   = round($row->traffic / (1024 ** 3), 2);

            $snapshot = json_decode($row->class_snapshot, true) ?: [];
            foreach ($snapshot as $cls => $cnt) {
                $classSeries[(string) $cls][] = (int) $cnt;
            }
        }

        return compact('dates', 'income', 'newUsers', 'activeUsers', 'trafficGb', 'classSeries');
    }

    /**
     * 月视图：最近12个月汇总
     * 返回每月 SUM(income), SUM(new_users), SUM(traffic)，class_snapshot 取该月最后一天
     */
    public static function getSiteMonthlyStats(): array
    {
        $rows = SiteDailyStat::where('date', '>=', date('Y-m-d', strtotime('-12 months')))
            ->orderBy('date')
            ->get();

        $monthly = [];
        foreach ($rows as $row) {
            $ym = substr($row->date, 0, 7); // 'YYYY-MM'
            if (!isset($monthly[$ym])) {
                $monthly[$ym] = [
                    'income'      => 0.0,
                    'new_users'   => 0,
                    'traffic'     => 0,
                    'class_snapshot' => [],
                ];
            }
            $monthly[$ym]['income']    += (float) $row->income;
            $monthly[$ym]['new_users'] += (int)   $row->new_users;
            $monthly[$ym]['traffic']   += (int)   $row->traffic;
            // 取该月最后一天的快照（后面的会覆盖前面的，最终是月末值）
            $monthly[$ym]['class_snapshot'] = json_decode($row->class_snapshot, true) ?: [];
        }

        $months      = [];
        $income      = [];
        $newUsers    = [];
        $trafficGb   = [];
        $classSeries = [];

        foreach ($monthly as $ym => $data) {
            $months[]    = $ym;
            $income[]    = round($data['income'], 2);
            $newUsers[]  = $data['new_users'];
            $trafficGb[] = round($data['traffic'] / (1024 ** 3), 2);
            foreach ($data['class_snapshot'] as $cls => $cnt) {
                $classSeries[(string) $cls][] = (int) $cnt;
            }
        }

        return compact('months', 'income', 'newUsers', 'trafficGb', 'classSeries');
    }

    /**
     * 年视图：指定年份全年12个月汇总
     */
    public static function getSiteYearlyStats(string $year): array
    {
        $rows = SiteDailyStat::where('date', '>=', $year . '-01-01')
            ->where('date', '<=', $year . '-12-31')
            ->orderBy('date')
            ->get();

        $monthly = [];
        foreach ($rows as $row) {
            $ym = substr($row->date, 0, 7);
            if (!isset($monthly[$ym])) {
                $monthly[$ym] = ['income' => 0.0, 'new_users' => 0, 'traffic' => 0, 'class_snapshot' => []];
            }
            $monthly[$ym]['income']    += (float) $row->income;
            $monthly[$ym]['new_users'] += (int)   $row->new_users;
            $monthly[$ym]['traffic']   += (int)   $row->traffic;
            $monthly[$ym]['class_snapshot'] = json_decode($row->class_snapshot, true) ?: [];
        }

        $months = $income = $newUsers = $trafficGb = [];
        $classSeries = [];
        for ($m = 1; $m <= 12; $m++) {
            $ym = $year . '-' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $months[]    = $ym;
            $d = $monthly[$ym] ?? ['income' => 0.0, 'new_users' => 0, 'traffic' => 0, 'class_snapshot' => []];
            $income[]    = round($d['income'], 2);
            $newUsers[]  = $d['new_users'];
            $trafficGb[] = round($d['traffic'] / (1024 ** 3), 2);
            foreach ($d['class_snapshot'] as $cls => $cnt) {
                $classSeries[(string) $cls][] = (int) $cnt;
            }
        }

        return compact('months', 'income', 'newUsers', 'trafficGb', 'classSeries');
    }

    /**
     * 本月 vs 上月同期收入对比
     */
    public static function getMonthlyIncomeComparison(): array
    {
        $thisMonthStart          = strtotime('first day of this month 00:00:00');
        $now                     = time();
        $elapsed                 = $now - $thisMonthStart;
        $lastMonthStart          = strtotime('first day of last month 00:00:00');
        $lastMonthSamePeriodEnd  = $lastMonthStart + $elapsed;

        $paylist = new Paylist();

        $thisMonth = (float) ($paylist->where('status', 1)
            ->whereBetween('datetime', [$thisMonthStart, $now])
            ->sum('total') ?? 0);

        $lastMonthSame = (float) ($paylist->where('status', 1)
            ->whereBetween('datetime', [$lastMonthStart, $lastMonthSamePeriodEnd])
            ->sum('total') ?? 0);

        $diffPercent = $lastMonthSame > 0
            ? round(($thisMonth - $lastMonthSame) / $lastMonthSame * 100, 1)
            : 0.0;

        return [
            'this_month'             => round($thisMonth, 2),
            'last_month_same_period' => round($lastMonthSame, 2),
            'diff_percent'           => $diffPercent,
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

    /**
     * 商品销量排行（直接查 order 表）
     * $month: 'YYYY-MM' 或 null（查全部）
     */
    public static function getProductSalesRanking(?string $month = null): array
    {
        $query = (new Order())
            ->whereIn('status', ['activated', 'expired', 'superseded'])
            ->groupBy('product_id', 'product_name', 'product_type')
            ->selectRaw('product_id, product_name, product_type, COUNT(*) as order_count, SUM(price) as revenue')
            ->orderByDesc('order_count')
            ->limit(10);

        if ($month !== null) {
            $start = strtotime($month . '-01 00:00:00');
            $end   = strtotime('last day of ' . $month . ' 23:59:59');
            $query->whereBetween('create_time', [$start, $end]);
        }

        return $query->get()->toArray();
    }

    /**
     * 年视图：指定年份商品销量排行（直接查 order 表）
     */
    public static function getProductSalesRankingByYear(string $year): array
    {
        $start = strtotime($year . '-01-01 00:00:00');
        $end   = strtotime($year . '-12-31 23:59:59');

        return (new Order())
            ->whereIn('status', ['activated', 'expired', 'superseded'])
            ->whereBetween('create_time', [$start, $end])
            ->groupBy('product_id', 'product_name', 'product_type')
            ->selectRaw('product_id, product_name, product_type, COUNT(*) as order_count, SUM(price) as revenue')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
