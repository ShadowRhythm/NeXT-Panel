<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Tools;
use Illuminate\Database\Query\Builder;
use function array_chunk;
use function array_values;
use function substr;
use function time;

/**
 * @property int    $id         记录ID
 * @property int    $user_id    用户ID
 * @property string $ip         IP地址
 * @property int    $node_id    节点ID
 * @property int    $first_time 首次在线时间
 * @property int    $last_time  最后在线时间
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
        $ip = $this->attributes['ip'];

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
     */
    public static function recordBatch(int $nodeId, array $logs): void
    {
        $now = time();
        $rows = [];

        foreach ($logs as $log) {
            $ip = (string) ($log?->ip ?? '');
            $userId = (int) ($log?->user_id ?? 0);

            if ($userId <= 0) {
                continue;
            }

            if (Tools::isIPv4($ip)) {
                // convert IPv4 Address to IPv4-mapped IPv6 Address
                $ip = '::ffff:' . $ip;
            } elseif (! Tools::isIPv6($ip)) {
                // either IPv4 or IPv6 Address
                continue;
            }

            $rows[$userId . '|' . $ip] = [
                'user_id' => $userId,
                'ip' => $ip,
                'node_id' => $nodeId,
                'first_time' => $now,
                'last_time' => $now,
            ];
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            (new self())->upsert($chunk, ['user_id', 'ip'], ['node_id', 'last_time']);
        }
    }

    public function nodeName(): string
    {
        return (new Node())->where('id', $this->node_id)->value('name');
    }
}
