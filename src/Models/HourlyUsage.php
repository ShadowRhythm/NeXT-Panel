<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;
use function array_chunk;
use function array_fill;
use function array_keys;
use function count;
use function date;
use function is_array;
use function json_decode;
use function json_encode;
use function max;

/**
 * @property int         $id           记录ID
 * @property int         $user_id      用户ID
 * @property string      $date         记录日期
 * @property string      $usage        流量用量（未乘倍率）
 * @property string|null $billed_usage 计费流量用量（已乘倍率）
 *
 * @mixin Builder
 */
final class HourlyUsage extends Model
{
    protected $connection = 'default';
    protected $table = 'hourly_usage';

    public function add(int $user_id, int $usage, ?int $billedUsage = null): void
    {
        if ($user_id <= 0 || ($usage <= 0 && ($billedUsage ?? 0) <= 0)) {
            return;
        }

        self::addBatch(
            [$user_id => $usage],
            $billedUsage !== null ? [$user_id => $billedUsage] : []
        );
    }

    /**
     * Batch-accumulate hourly usage (raw and billed) for multiple users using a single lookup query.
     *
     * @param array<int, int> $userUsages       Map of user_id => raw bytes
     * @param array<int, int> $userBilledUsages Map of user_id => billed bytes
     */
    public static function addBatch(array $userUsages, array $userBilledUsages = []): void
    {
        $filteredRaw = [];
        $filteredBilled = [];
        foreach ($userUsages as $userId => $usage) {
            $uid = (int) $userId;
            $bytes = (int) $usage;
            $billed = (int) ($userBilledUsages[$uid] ?? $bytes);
            if ($uid > 0 && ($bytes > 0 || $billed > 0)) {
                $filteredRaw[$uid] = ($filteredRaw[$uid] ?? 0) + max(0, $bytes);
                $filteredBilled[$uid] = ($filteredBilled[$uid] ?? 0) + max(0, $billed);
            }
        }
        foreach ($userBilledUsages as $userId => $billed) {
            $uid = (int) $userId;
            $billedBytes = (int) $billed;
            if ($uid > 0 && $billedBytes > 0 && ! isset($filteredRaw[$uid])) {
                $filteredRaw[$uid] = 0;
                $filteredBilled[$uid] = ($filteredBilled[$uid] ?? 0) + $billedBytes;
            }
        }

        if ($filteredRaw === []) {
            return;
        }

        $hour = (int) date('H');
        $date = date('Y-m-d');
        $existing = self::where('date', $date)
            ->whereIn('user_id', array_keys($filteredRaw))
            ->get()
            ->keyBy('user_id');

        $inserts = [];
        foreach ($filteredRaw as $userId => $usage) {
            $billed = $filteredBilled[$userId] ?? $usage;
            $record = $existing->get($userId);
            if ($record === null) {
                $usageArray = array_fill(0, 24, 0);
                $usageArray[$hour] = $usage;
                $billedArray = array_fill(0, 24, 0);
                $billedArray[$hour] = $billed;
                $inserts[] = [
                    'user_id' => $userId,
                    'date' => $date,
                    'usage' => json_encode($usageArray),
                    'billed_usage' => json_encode($billedArray),
                ];
            } else {
                $usageArray = self::decodeHourlyArray($record->usage);
                $billedArray = $record->billed_usage !== null
                    ? self::decodeHourlyArray((string) $record->billed_usage)
                    : $usageArray;
                $usageArray[$hour] = (int) ($usageArray[$hour] ?? 0) + $usage;
                $billedArray[$hour] = (int) ($billedArray[$hour] ?? 0) + $billed;
                self::where('id', $record->id)->update([
                    'usage' => json_encode($usageArray),
                    'billed_usage' => json_encode($billedArray),
                ]);
            }
        }

        if ($inserts !== []) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                self::insert($chunk);
            }
        }
    }

    /**
     * @return array<int, int>
     */
    public static function decodeHourlyArray(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded) || count($decoded) !== 24) {
            return array_fill(0, 24, 0);
        }

        $result = [];
        for ($h = 0; $h < 24; $h++) {
            $result[$h] = (int) ($decoded[$h] ?? 0);
        }

        return $result;
    }
}
