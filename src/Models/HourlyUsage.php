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

/**
 * @property int $id           记录ID
 * @property int $user_id      用户ID
 * @property string $date      记录日期
 * @property string $usage     流量用量
 *
 * @mixin Builder
 */
final class HourlyUsage extends Model
{
    protected $connection = 'default';
    protected $table = 'hourly_usage';

    public function add(int $user_id, int $usage): void
    {
        if ($user_id <= 0 || $usage <= 0) {
            return;
        }

        self::addBatch([$user_id => $usage]);
    }

    /**
     * Batch-accumulate hourly usage for multiple users using a single lookup query.
     *
     * @param array<int, int> $userUsages Map of user_id => bytes
     */
    public static function addBatch(array $userUsages): void
    {
        $filtered = [];
        foreach ($userUsages as $userId => $usage) {
            $uid = (int) $userId;
            $bytes = (int) $usage;
            if ($uid > 0 && $bytes > 0) {
                $filtered[$uid] = ($filtered[$uid] ?? 0) + $bytes;
            }
        }

        if ($filtered === []) {
            return;
        }

        $hour = (int) date('H');
        $date = date('Y-m-d');
        $existing = self::where('date', $date)
            ->whereIn('user_id', array_keys($filtered))
            ->get()
            ->keyBy('user_id');

        $inserts = [];
        foreach ($filtered as $userId => $usage) {
            $record = $existing->get($userId);
            if ($record === null) {
                $usageArray = array_fill(0, 24, 0);
                $usageArray[$hour] = $usage;
                $inserts[] = [
                    'user_id' => $userId,
                    'date' => $date,
                    'usage' => json_encode($usageArray),
                ];
            } else {
                $usageArray = json_decode((string) $record->usage, true);
                if (! is_array($usageArray) || count($usageArray) !== 24) {
                    $usageArray = array_fill(0, 24, 0);
                }
                $usageArray[$hour] = (int) ($usageArray[$hour] ?? 0) + $usage;
                self::where('id', $record->id)->update([
                    'usage' => json_encode($usageArray),
                ]);
            }
        }

        if ($inserts !== []) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                self::insert($chunk);
            }
        }
    }
}
