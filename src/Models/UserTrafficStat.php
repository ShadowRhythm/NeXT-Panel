<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;
use function array_fill;
use function array_shift;
use function array_sum;
use function count;
use function date;
use function is_array;
use function json_decode;
use function json_encode;
use function strtotime;

/**
 * @property int $id                        记录ID
 * @property int $user_id                   用户ID
 * @property string $daily_last_date        daily最后更新日期
 * @property string $daily_usage            过去30天用量(JSON数组)
 * @property string $monthly_last_month     monthly最后更新月份(YYYY-MM)
 * @property string $monthly_usage          过去12个月用量(JSON数组)
 *
 * @mixin Builder
 */
final class UserTrafficStat extends Model
{
    protected $connection = 'default';
    protected $table = 'user_traffic_stat';

    public static function getOrUpdate(int $user_id): self
    {
        $stat = self::where('user_id', $user_id)->first();
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($stat === null) {
            $stat = new self();
            $stat->user_id = $user_id;
            $stat->daily_last_date = '1970-01-01';
            $stat->daily_usage = json_encode(array_fill(0, 30, 0));
            $stat->monthly_last_month = '1970-01';
            $stat->monthly_usage = json_encode(array_fill(0, 12, 0));
        }

        if ($stat->daily_last_date < $yesterday) {
            $daily_usage = json_decode($stat->daily_usage, true);
            if (! is_array($daily_usage) || count($daily_usage) !== 30) {
                $daily_usage = array_fill(0, 30, 0);
            }

            $monthly_usage = json_decode($stat->monthly_usage, true);
            if (! is_array($monthly_usage) || count($monthly_usage) !== 12) {
                $monthly_usage = array_fill(0, 12, 0);
            }

            $monthly_last_month = $stat->monthly_last_month;

            if ($stat->daily_last_date === '1970-01-01') {
                $minDate = HourlyUsage::where('user_id', $user_id)->min('date');
                if ($minDate === null) {
                    $stat->daily_last_date = $yesterday;
                    $stat->monthly_last_month = date('Y-m', strtotime($yesterday));
                    $stat->save();

                    return $stat;
                }
                $startDate = (string) $minDate;
            } else {
                $startDate = date('Y-m-d', strtotime($stat->daily_last_date . ' +1 day'));
            }

            $maxPast = date('Y-m-d', strtotime('-365 days'));
            if ($startDate < $maxPast) {
                $startDate = $maxPast;
                $daily_usage = array_fill(0, 30, 0);
                $monthly_usage = array_fill(0, 12, 0);
                $monthly_last_month = date('Y-m', strtotime($startDate));
            }

            if ($startDate <= $yesterday) {
                $records = HourlyUsage::where('user_id', $user_id)
                    ->whereBetween('date', [$startDate, $yesterday])
                    ->get();

                $usageByDate = [];
                foreach ($records as $record) {
                    $usageByDate[$record->date] = $record->usage;
                }

                $currentTimestamp = strtotime($startDate);
                $endTimestamp = strtotime($yesterday);

                while ($currentTimestamp <= $endTimestamp) {
                    $currentDate = date('Y-m-d', $currentTimestamp);
                    $currentMonth = date('Y-m', $currentTimestamp);

                    $daySum = 0;
                    if (isset($usageByDate[$currentDate])) {
                        $hours = json_decode($usageByDate[$currentDate], true);
                        if (is_array($hours)) {
                            $daySum = (int) array_sum($hours);
                        }
                    }

                    array_shift($daily_usage);
                    $daily_usage[] = $daySum;

                    if ($monthly_last_month === '1970-01') {
                        $monthly_last_month = $currentMonth;
                    } elseif ($monthly_last_month !== $currentMonth) {
                        array_shift($monthly_usage);
                        $monthly_usage[] = 0;
                        $monthly_last_month = $currentMonth;
                    }
                    $monthly_usage[11] += $daySum;

                    $currentTimestamp = strtotime('+1 day', $currentTimestamp);
                }

                $stat->daily_last_date = $yesterday;
                $stat->daily_usage = json_encode($daily_usage);
                $stat->monthly_last_month = $monthly_last_month;
                $stat->monthly_usage = json_encode($monthly_usage);
                $stat->save();
            }
        }

        return $stat;
    }

    public function archiveDaily(string $date): void
    {
        if ($this->daily_last_date >= $date) {
            return;
        }

        $hourly_record = HourlyUsage::where('user_id', $this->user_id)->where('date', $date)->first();
        $daySum = 0;
        if ($hourly_record) {
            $hours = json_decode($hourly_record->usage, true);
            if (is_array($hours)) {
                $daySum = (int) array_sum($hours);
            }
        }

        $daily_usage = json_decode($this->daily_usage, true);
        if (! is_array($daily_usage) || count($daily_usage) !== 30) {
            $daily_usage = array_fill(0, 30, 0);
        }
        array_shift($daily_usage);
        $daily_usage[] = $daySum;

        $monthly_usage = json_decode($this->monthly_usage, true);
        if (! is_array($monthly_usage) || count($monthly_usage) !== 12) {
            $monthly_usage = array_fill(0, 12, 0);
        }
        $month = date('Y-m', strtotime($date));
        if ($this->monthly_last_month === '1970-01') {
            $this->monthly_last_month = $month;
        } elseif ($this->monthly_last_month !== $month) {
            array_shift($monthly_usage);
            $monthly_usage[] = 0;
            $this->monthly_last_month = $month;
        }
        $monthly_usage[11] += $daySum;

        $this->daily_usage = json_encode($daily_usage);
        $this->monthly_usage = json_encode($monthly_usage);
        $this->daily_last_date = $date;
        $this->save();
    }
}
