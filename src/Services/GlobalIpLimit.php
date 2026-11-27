<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OnlineLog;
use App\Models\User;
use Redis;
use Throwable;
use function array_chunk;
use function array_keys;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function is_array;
use function sort;
use function sprintf;
use function time;

final class GlobalIpLimit
{
    public const ACTIVE_WINDOW_SECONDS = 90;
    public const KICK_COOLDOWN_SECONDS = 90;
    public const KICK_QUEUE_TTL_SECONDS = 180;

    private const KICK_QUEUE_KEY_PREFIX = 'next:ip_limit:kick_ips:';
    private const COOLDOWN_KEY_PREFIX = 'next:ip_limit:cooldown:';

    /**
     * Record a node's reported online IPs and enforce global per-user IP limits.
     *
     * @param array<int, object|null> $logs
     */
    public static function recordAndEnforce(
        int $nodeId,
        array $logs,
        ?object $redis = null,
        ?int $now = null
    ): void {
        $now ??= time();
        $redis ??= Cache::client();

        $normalized = OnlineLog::normalizeRows($nodeId, $logs, $now);
        if ($normalized === []) {
            return;
        }

        $cooldownSkip = self::filterCooldownEntries($nodeId, $normalized, $redis);
        if ($cooldownSkip['rebound_kicks'] !== []) {
            self::enqueueKicksAndCooldowns($redis, [$nodeId => $cooldownSkip['rebound_kicks']], []);
        }

        $userIds = OnlineLog::recordNormalizedRows($normalized, $cooldownSkip['skip'], $now);

        if ($userIds === []) {
            return;
        }

        $limits = User::whereIn('id', $userIds)
            ->where('node_iplimit', '>', 0)
            ->pluck('node_iplimit', 'id')
            ->map(static fn ($limit): int => (int) $limit)
            ->toArray();

        if ($limits === []) {
            return;
        }

        $activeRows = OnlineLog::whereIn('user_id', array_keys($limits))
            ->where('last_time', '>', $now - self::ACTIVE_WINDOW_SECONDS)
            ->where('kicked_at', 0)
            ->orderByDesc('session_time')
            ->orderByDesc('first_time')
            ->orderByDesc('last_time')
            ->orderByDesc('id')
            ->get(['id', 'user_id', 'node_id', 'ip', 'first_time', 'session_time', 'last_time']);

        if ($activeRows->isEmpty()) {
            return;
        }

        $rowsByUser = [];
        foreach ($activeRows as $row) {
            $rowsByUser[(int) $row->user_id][] = $row;
        }

        $evictIds = [];
        $kicksByNode = [];
        $cooldownNodes = [];

        foreach ($rowsByUser as $userId => $rows) {
            $limit = $limits[$userId] ?? 0;
            if ($limit <= 0 || count($rows) <= $limit) {
                continue;
            }

            $evicted = array_slice($rows, $limit);
            foreach ($evicted as $row) {
                $evictIds[] = (int) $row->id;
                $humanIp = $row->ip();
                $targetNodeId = (int) $row->node_id;
                $storedKey = $userId . '|' . (string) $row->ip;
                if ($targetNodeId > 0 && ($targetNodeId !== $nodeId || isset($normalized[$storedKey]))) {
                    $kicksByNode[$targetNodeId][$userId . '|' . $humanIp] = [
                        'user_id' => $userId,
                        'ip' => $humanIp,
                    ];
                    $cooldownNodes[self::cooldownKey($userId, $humanIp)] = (string) $targetNodeId;
                }
            }
        }

        if ($evictIds === []) {
            return;
        }

        foreach (array_chunk($evictIds, 500) as $chunk) {
            OnlineLog::whereIn('id', $chunk)->update(['kicked_at' => $now]);
        }

        self::enqueueKicksAndCooldowns($redis, $kicksByNode, $cooldownNodes);
    }

    /**
     * Queue an explicit IP kick directive for a node and place the IP on cooldown.
     */
    public static function kickNodeIp(
        int $nodeId,
        int $userId,
        string $humanIp,
        ?object $redis = null
    ): void {
        if ($userId <= 0 || $humanIp === '') {
            return;
        }

        $redis ??= Cache::client();
        $humanIp = OnlineLog::formatHumanIp($humanIp);

        $kicksByNode = [];
        if ($nodeId > 0) {
            $kicksByNode[$nodeId][$userId . '|' . $humanIp] = [
                'user_id' => $userId,
                'ip' => $humanIp,
            ];
        }

        self::enqueueKicksAndCooldowns(
            $redis,
            $kicksByNode,
            [self::cooldownKey($userId, $humanIp) => (string) $nodeId]
        );
    }

    /**
     * Atomically read and clear queued kick directives for a node.
     *
     * @return array<int, array{user_id: int, ip: string}>
     */
    public static function drainKickIps(int $nodeId, ?object $redis = null): array
    {
        if ($nodeId <= 0) {
            return [];
        }

        $redis ??= Cache::client();
        if ($redis === null) {
            return [];
        }

        $queueKey = self::queueKey($nodeId);

        try {
            if ($redis instanceof Redis) {
                $result = $redis->multi()
                    ->hGetAll($queueKey)
                    ->del($queueKey)
                    ->exec();
                $raw = is_array($result) && is_array($result[0] ?? null) ? $result[0] : [];
            } else {
                $raw = $redis->hGetAll($queueKey);
                $redis->del($queueKey);
            }
        } catch (Throwable) {
            return [];
        }

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $fields = array_keys($raw);
        sort($fields);

        $kickIps = [];
        foreach ($fields as $field) {
            $parts = explode('|', (string) $field, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $userId = (int) $parts[0];
            $ip = $parts[1];
            if ($userId <= 0 || $ip === '') {
                continue;
            }
            $kickIps[] = [
                'user_id' => $userId,
                'ip' => $ip,
            ];
        }

        return $kickIps;
    }

    /**
     * @param array<string, array{row: array<string, int|string>, human_ip: string}> $normalized
     *
     * @return array{skip: array<string, bool>, rebound_kicks: array<string, array{user_id: int, ip: string}>}
     */
    private static function filterCooldownEntries(int $nodeId, array $normalized, ?object $redis): array
    {
        $empty = ['skip' => [], 'rebound_kicks' => []];
        if ($redis === null || $normalized === []) {
            return $empty;
        }

        $keys = [];
        $keyMap = [];
        foreach ($normalized as $entryKey => $entry) {
            $redisKey = self::cooldownKey((int) $entry['row']['user_id'], $entry['human_ip']);
            $keys[] = $redisKey;
            $keyMap[] = $entryKey;
        }

        $uniqueKeys = array_values(array_unique($keys));

        try {
            $values = $redis->mGet($uniqueKeys);
        } catch (Throwable) {
            return $empty;
        }

        if (! is_array($values)) {
            return $empty;
        }

        $activeCooldownSourceNode = [];
        foreach ($uniqueKeys as $idx => $redisKey) {
            $val = $values[$idx] ?? false;
            if ($val !== false && $val !== null) {
                $activeCooldownSourceNode[$redisKey] = (string) $val;
            }
        }

        if ($activeCooldownSourceNode === []) {
            return $empty;
        }

        $skip = [];
        $reboundKicks = [];
        foreach ($keys as $idx => $redisKey) {
            if (! isset($activeCooldownSourceNode[$redisKey])) {
                continue;
            }
            $entryKey = $keyMap[$idx];
            $skip[$entryKey] = true;

            // If a cooled-down IP hops to another node before cooldown expires, kick it there too.
            if ($nodeId > 0 && $activeCooldownSourceNode[$redisKey] !== (string) $nodeId) {
                $userId = (int) $normalized[$entryKey]['row']['user_id'];
                $humanIp = $normalized[$entryKey]['human_ip'];
                $reboundKicks[$userId . '|' . $humanIp] = [
                    'user_id' => $userId,
                    'ip' => $humanIp,
                ];
            }
        }

        return [
            'skip' => $skip,
            'rebound_kicks' => $reboundKicks,
        ];
    }

    /**
     * @param array<int, array<string, array{user_id: int, ip: string}>> $kicksByNode
     * @param array<string, string>                                      $cooldownNodes
     */
    private static function enqueueKicksAndCooldowns(?object $redis, array $kicksByNode, array $cooldownNodes): void
    {
        if ($redis === null) {
            return;
        }

        try {
            foreach ($kicksByNode as $targetNodeId => $entries) {
                $queueKey = self::queueKey($targetNodeId);
                $fields = [];
                foreach ($entries as $field => $entry) {
                    $fields[$field] = '1';
                }
                if ($fields !== []) {
                    $redis->hMSet($queueKey, $fields);
                    $redis->expire($queueKey, self::KICK_QUEUE_TTL_SECONDS);
                }
            }

            foreach ($cooldownNodes as $cooldownKey => $sourceNodeId) {
                $redis->setex($cooldownKey, self::KICK_COOLDOWN_SECONDS, $sourceNodeId);
            }
        } catch (Throwable) {
            // Redis failure must not break online reporting.
        }
    }

    private static function queueKey(int $nodeId): string
    {
        return self::KICK_QUEUE_KEY_PREFIX . $nodeId;
    }

    private static function cooldownKey(int $userId, string $humanIp): string
    {
        return self::COOLDOWN_KEY_PREFIX . sprintf('%d:%s', $userId, $humanIp);
    }
}
