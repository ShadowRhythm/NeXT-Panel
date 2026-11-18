<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HourlyUsage;
use App\Models\Node;
use App\Models\Paylist;
use App\Models\User;
use App\Models\UserTrafficStat;
use App\Utils\Tools;
use DateTime;
use function array_fill;
use function array_shift;
use function array_sum;
use function count;
use function date;
use function floatval;
use function is_array;
use function is_null;
use function json_decode;
use function round;
use function strtotime;
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
}
