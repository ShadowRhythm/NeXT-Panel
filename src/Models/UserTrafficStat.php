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
 * @property int         $id                   记录ID
 * @property int         $user_id              用户ID
 * @property string      $daily_last_date      daily最后更新日期
 * @property string      $daily_usage          过去30天用量(JSON数组，未乘倍率)
 * @property string|null $daily_billed_usage   过去30天计费用量(JSON数组，已乘倍率)
 * @property string      $monthly_last_month   monthly最后更新月份(YYYY-MM)
 * @property string      $monthly_usage        过去12个月用量(JSON数组，未乘倍率)
 * @property string|null $monthly_billed_usage 过去12个月计费用量(JSON数组，已乘倍率)
 *
 * @mixin Builder
 */
final class UserTrafficStat extends Model
{
    protected $connection = 'default';
    protected $table = 'user_traffic_stat';

    /**
     * @return array<int, int>
     */
    public static function decodeUsageArray(?string $json, int $length, ?array $fallback = null): array
    {
        if ($json === null) {
            return $fallback ?? array_fill(0, $length, 0);
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded) || count($decoded) !== $length) {
            return $fallback ?? array_fill(0, $length, 0);
        }

        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[$i] = (int) ($decoded[$i] ?? 0);
        }

        return $result;
    }

    public static function getOrUpdate(int $user_id): self
    {
        $stat = self::where('user_id', $user_id)->first();
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($stat === null) {
            $stat = new self();
            $stat->user_id = $user_id;
            $stat->daily_last_date = '1970-01-01';
            $stat->daily_usage = json_encode(array_fill(0, 30, 0));
            $stat->daily_billed_usage = json_encode(array_fill(0, 30, 0));
            $stat->monthly_last_month = '1970-01';
            $stat->monthly_usage = json_encode(array_fill(0, 12, 0));
            $stat->monthly_billed_usage = json_encode(array_fill(0, 12, 0));
        }

        if ($stat->daily_last_date < $yesterday) {
            $daily_usage = self::decodeUsageArray($stat->daily_usage, 30);
            $daily_billed_usage = self::decodeUsageArray($stat->daily_billed_usage, 30, $daily_usage);

            $monthly_usage = self::decodeUsageArray($stat->monthly_usage, 12);
            $monthly_billed_usage = self::decodeUsageArray($stat->monthly_billed_usage, 12, $monthly_usage);

            $monthly_last_month = $stat->monthly_last_month;

            if ($stat->daily_last_date === '1970-01-01') {
                $minDate = HourlyUsage::where('user_id', $user_id)->min('date');
                if ($minDate === null) {
                    $stat->daily_last_date = $yesterday;
                    $stat->monthly_last_month = date('Y-m', strtotime($yesterday));
                    $stat->daily_billed_usage = json_encode($daily_billed_usage);
                    $stat->monthly_billed_usage = json_encode($monthly_billed_usage);
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
                $daily_billed_usage = array_fill(0, 30, 0);
                $monthly_usage = array_fill(0, 12, 0);
                $monthly_billed_usage = array_fill(0, 12, 0);
                $monthly_last_month = date('Y-m', strtotime($startDate));
            }

            if ($startDate <= $yesterday) {
                $records = HourlyUsage::where('user_id', $user_id)
                    ->whereBetween('date', [$startDate, $yesterday])
                    ->get();

                $usageByDate = [];
                $billedByDate = [];
                foreach ($records as $record) {
                    $usageByDate[$record->date] = $record->usage;
                    $billedByDate[$record->date] = $record->billed_usage ?? $record->usage;
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

                    $billedDaySum = $daySum;
                    if (isset($billedByDate[$currentDate])) {
                        $billedHours = json_decode($billedByDate[$currentDate], true);
                        if (is_array($billedHours)) {
                            $billedDaySum = (int) array_sum($billedHours);
                        }
                    }

                    array_shift($daily_usage);
                    $daily_usage[] = $daySum;
                    array_shift($daily_billed_usage);
                    $daily_billed_usage[] = $billedDaySum;

                    if ($monthly_last_month === '1970-01') {
                        $monthly_last_month = $currentMonth;
                    } elseif ($monthly_last_month !== $currentMonth) {
                        array_shift($monthly_usage);
                        $monthly_usage[] = 0;
                        array_shift($monthly_billed_usage);
                        $monthly_billed_usage[] = 0;
                        $monthly_last_month = $currentMonth;
                    }
                    $monthly_usage[11] += $daySum;
                    $monthly_billed_usage[11] += $billedDaySum;

                    $currentTimestamp = strtotime('+1 day', $currentTimestamp);
                }

                $stat->daily_last_date = $yesterday;
                $stat->daily_usage = json_encode($daily_usage);
                $stat->daily_billed_usage = json_encode($daily_billed_usage);
                $stat->monthly_last_month = $monthly_last_month;
                $stat->monthly_usage = json_encode($monthly_usage);
                $stat->monthly_billed_usage = json_encode($monthly_billed_usage);
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
        $billedDaySum = 0;
        if ($hourly_record) {
            $hours = json_decode($hourly_record->usage, true);
            if (is_array($hours)) {
                $daySum = (int) array_sum($hours);
            }
            $billedHours = $hourly_record->billed_usage !== null
                ? json_decode((string) $hourly_record->billed_usage, true)
                : $hours;
            $billedDaySum = is_array($billedHours) ? (int) array_sum($billedHours) : $daySum;
        }

        $daily_usage = self::decodeUsageArray($this->daily_usage, 30);
        $daily_billed_usage = self::decodeUsageArray($this->daily_billed_usage, 30, $daily_usage);
        array_shift($daily_usage);
        $daily_usage[] = $daySum;
        array_shift($daily_billed_usage);
        $daily_billed_usage[] = $billedDaySum;

        $monthly_usage = self::decodeUsageArray($this->monthly_usage, 12);
        $monthly_billed_usage = self::decodeUsageArray($this->monthly_billed_usage, 12, $monthly_usage);
        $month = date('Y-m', strtotime($date));
        if ($this->monthly_last_month === '1970-01') {
            $this->monthly_last_month = $month;
        } elseif ($this->monthly_last_month !== $month) {
            array_shift($monthly_usage);
            $monthly_usage[] = 0;
            array_shift($monthly_billed_usage);
            $monthly_billed_usage[] = 0;
            $this->monthly_last_month = $month;
        }
        $monthly_usage[11] += $daySum;
        $monthly_billed_usage[11] += $billedDaySum;

        $this->daily_usage = json_encode($daily_usage);
        $this->daily_billed_usage = json_encode($daily_billed_usage);
        $this->monthly_usage = json_encode($monthly_usage);
        $this->monthly_billed_usage = json_encode($monthly_billed_usage);
        $this->daily_last_date = $date;
        $this->save();
    }
}
