<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Tools;
use Illuminate\Database\Query\Builder;
use function array_chunk;
use function array_keys;
use function array_unique;
use function array_values;
use function inet_ntop;
use function inet_pton;
use function substr;
use function time;

/**
 * @property int    $id           记录ID
 * @property int    $user_id      用户ID
 * @property string $ip           IP地址
 * @property int    $node_id      节点ID
 * @property int    $first_time   首次在线时间
 * @property int    $session_time 本轮连续上线开始时间
 * @property int    $last_time    最后在线时间
 * @property int    $kicked_at    最近踢出时间（0 表示活跃正常）
 *
 * @mixin Builder
 */
final class OnlineLog extends Model
{
    protected $connection = 'default';
    protected $table = 'online_log';

    /**
     * Get human-readable IPv4 or IPv6 address
     *
     * Unlike `$this->ip`, this method would convert IPv4-mapped IPv6 Address to IPv4 Address.
     *
     * @return string Example: IPv4 Address: `1.1.1.1`; IPv6 Address: `2606:4700:4700::1111`
     */
    public function ip(): string
    {
        return self::formatHumanIp((string) $this->attributes['ip']);
    }

    public static function formatHumanIp(string $ip): string
    {
        if (str_starts_with($ip, '::ffff:')) {
            $v4 = substr($ip, 7);
            // Mix hexadecimal and dot decimal notations: https://www.rfc-editor.org/rfc/rfc5952#section-5
            //
            // IPv4-translated address: https://www.rfc-editor.org/rfc/rfc2765.html#section-2.1
            if (str_contains($v4, '.') && ! str_contains($v4, ':')) {
                return $v4;
            }
        }

        return $ip;
    }

    /**
     * Fetch active IP counts per user within the recent window in a single grouped query.
     *
     * @return array<int, int>
     */
    public static function getActiveIpCounts(int $windowSeconds = 90): array
    {
        return self::where('last_time', '>', time() - $windowSeconds)
            ->where('kicked_at', 0)
            ->selectRaw('user_id, count(*) as ip_count')
            ->groupBy('user_id')
            ->pluck('ip_count', 'user_id')
            ->map(static fn ($count): int => (int) $count)
            ->toArray();
    }

    /**
     * Normalize, deduplicate, and batch-upsert online IP records for a node.
     *
     * @param array<int, object|null> $logs
     *
     * @return array<int, int>
     */
    public static function recordBatch(int $nodeId, array $logs, ?int $now = null): array
    {
        $now ??= time();
        $normalized = self::normalizeRows($nodeId, $logs, $now);

        return self::recordNormalizedRows($normalized, [], $now);
    }

    /**
     * @param array<int, object|null> $logs
     *
     * @return array<string, array{row: array<string, int|string>, human_ip: string}>
     */
    public static function normalizeRows(int $nodeId, array $logs, int $now): array
    {
        $entries = [];

        foreach ($logs as $log) {
            $rawIp = (string) ($log?->ip ?? '');
            $userId = (int) ($log?->user_id ?? 0);

            if ($userId <= 0) {
                continue;
            }

            if (Tools::isIPv4($rawIp)) {
                // convert IPv4 Address to IPv4-mapped IPv6 Address
                $storedIp = '::ffff:' . $rawIp;
                $humanIp = $rawIp;
            } elseif (Tools::isIPv6($rawIp)) {
                $packed = inet_pton($rawIp);
                $canonical = $packed !== false ? inet_ntop($packed) : false;
                $storedIp = $canonical !== false ? $canonical : $rawIp;
                $humanIp = self::formatHumanIp($storedIp);
            } else {
                continue;
            }

            $entries[$userId . '|' . $storedIp] = [
                'row' => [
                    'user_id' => $userId,
                    'ip' => $storedIp,
                    'node_id' => $nodeId,
                    'first_time' => $now,
                    'session_time' => $now,
                    'last_time' => $now,
                    'kicked_at' => 0,
                ],
                'human_ip' => $humanIp,
            ];
        }

        return $entries;
    }

    /**
     * @param array<string, array{row: array<string, int|string>, human_ip: string}> $normalized
     * @param array<string, bool>                                                   $skipKeys
     *
     * @return array<int, int>
     */
    public static function recordNormalizedRows(
        array $normalized,
        array $skipKeys = [],
        ?int $now = null,
        int $staleAfterSeconds = 90
    ): array {
        $now ??= time();
        $rows = [];
        $userIds = [];

        foreach ($normalized as $key => $entry) {
            if (isset($skipKeys[$key])) {
                continue;
            }
            $rows[$key] = $entry['row'];
            $userIds[(int) $entry['row']['user_id']] = true;
        }

        if ($rows === []) {
            return [];
        }

        $touchedUserIds = array_keys($userIds);

        // Preserve existing rows (and first_time) instead of deleting; only keep
        // session_time for uninterrupted active sessions and reset it when stale or kicked.
        $existingMap = [];
        $existingRows = self::whereIn('user_id', $touchedUserIds)
            ->get(['user_id', 'ip', 'session_time', 'last_time', 'kicked_at']);
        foreach ($existingRows as $existingRow) {
            $existingMap[(int) $existingRow->user_id . '|' . (string) $existingRow->ip] = $existingRow;
        }

        $activeCutoff = $now - $staleAfterSeconds;
        foreach ($rows as $key => &$row) {
            $prev = $existingMap[$key] ?? null;
            if ($prev !== null && (int) $prev->last_time > $activeCutoff && (int) $prev->kicked_at === 0) {
                $prevSessionTime = (int) $prev->session_time;
                $row['session_time'] = $prevSessionTime > 0 ? $prevSessionTime : $now;
            } else {
                $row['session_time'] = $now;
            }
            $row['kicked_at'] = 0;
        }
        unset($row);

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            (new self())->upsert($chunk, ['user_id', 'ip'], ['node_id', 'last_time', 'session_time', 'kicked_at']);
        }

        return $touchedUserIds;
    }

    public function nodeName(): string
    {
        return (new Node())->where('id', $this->node_id)->value('name');
    }
}
