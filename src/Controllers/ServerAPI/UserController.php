<?php

declare(strict_types=1);

namespace App\Controllers\ServerAPI;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\DetectLog;
use App\Models\HourlyUsage;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\User;
use App\Services\DynamicRate;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function array_chunk;
use function count;
use function date;
use function is_array;
use function is_numeric;
use function is_string;
use function json_decode;
use function time;

final class UserController extends BaseController
{
    /**
     * GET /api/server/v1/users
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        /** @var Node|null $node */
        $node = $request->getAttribute('node');

        if ($node === null) {
            return $response->withStatus(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        if ($node->node_bandwidth_limit !== 0 && $node->node_bandwidth_limit <= $node->node_bandwidth) {
            return $response->withStatus(400)->withJson([
                'error' => [
                    'code' => 'out_of_bandwidth',
                    'message' => 'Node out of bandwidth.',
                ],
            ]);
        }

        $users_raw = (new User())->where(static function ($query) use ($node): void {
            $query->where('is_banned', 0)
                ->where(static function ($query) use ($node): void {
                    $query->where('class', '>=', $node->node_class)
                        ->where(static function ($query) use ($node): void {
                            if ($node->node_group !== 0) {
                                $query->where('node_group', $node->node_group);
                            }
                        });
                })
                ->orWhere(static function ($query): void {
                    $query->where('is_admin', 1)->where('is_banned', 0);
                });
        })->get([
            'id',
            'u',
            'd',
            'transfer_enable',
            'node_speedlimit',
            'node_iplimit',
            'passwd',
            'uuid',
        ]);

        $users = [];
        $hasIpLimit = $users_raw->contains(static fn (User $u): bool => (int) $u->node_iplimit !== 0);
        $onlineIpCounts = $hasIpLimit ? OnlineLog::getActiveIpCounts() : [];

        foreach ($users_raw as $user_raw) {
            if ($user_raw->transfer_enable <= $user_raw->u + $user_raw->d) {
                if (Config::obtain('keep_connect')) {
                    // 流量耗尽用户限速至 1Mbps
                    $user_raw->node_speedlimit = 1;
                } else {
                    continue;
                }
            }

            if ((int) $user_raw->node_iplimit !== 0 &&
                (int) $user_raw->node_iplimit < ($onlineIpCounts[$user_raw->id] ?? 0)
            ) {
                continue;
            }

            // sort=1: SS2022 — passwd only (derived key), sort=4: Hysteria2, sort=5: AnyTLS — passwd only
            // sort=2: TUIC — uuid + passwd, sort=11: VMess, sort=14: Trojan — uuid only
            if ($node->sort === 1) {
                $custom_config = is_string($node->custom_config)
                    ? json_decode($node->custom_config)
                    : (object) $node->custom_config;
                $method = $custom_config->method ?? '2022-blake3-aes-128-gcm';
                $user_pk = Tools::genSs2022UserPk($user_raw->passwd, $method);

                if (! $user_pk) {
                    continue;
                }

                $user_raw->passwd = $user_pk;
            }

            $user_entry = ['id' => $user_raw->id, 'node_speedlimit' => $user_raw->node_speedlimit];

            $user_entry = match ($node->sort) {
                1, 4, 5 => $user_entry + ['passwd' => $user_raw->passwd],
                2       => $user_entry + ['uuid' => $user_raw->uuid, 'passwd' => $user_raw->passwd],
                default => $user_entry + ['uuid' => $user_raw->uuid],  // VMess(11), Trojan(14)
            };

            $users[] = $user_entry;
        }

        return ResponseHelper::successWithDataEtag($request, $response, $users);
    }

    /**
     * POST /api/server/v1/users/traffic
     */
    public function addTraffic(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        /** @var Node|null $node */
        $node = $request->getAttribute('node');

        if ($node === null) {
            return $response->withStatus(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $body = json_decode($request->getBody()->__toString());

        if (! $body || ! isset($body->traffic) || ! is_array($body->traffic)) {
            return $response->withStatus(400)->withJson([
                'error' => [
                    'code' => 'invalid_data',
                    'message' => 'Invalid data.',
                ],
            ]);
        }

        $data = $body->traffic;

        foreach ($data as $log) {
            $u = $log?->u ?? 0;
            $d = $log?->d ?? 0;

            if (! is_numeric($u) || ! is_numeric($d) || $u < 0 || $d < 0) {
                return $response->withStatus(400)->withJson([
                    'error' => [
                        'code' => 'invalid_data',
                        'message' => 'Invalid traffic data.',
                    ],
                ]);
            }
        }

        $rate = 1;

        if ($node->is_dynamic_rate) {
            $dynamic_rate_config = json_decode($node->dynamic_rate_config);

            $dynamic_rate_type = match ($node->dynamic_rate_type) {
                1 => 'linear',
                default => 'logistic',
            };

            $rate = DynamicRate::getRateByTime(
                (float) $dynamic_rate_config?->max_rate,
                (int) $dynamic_rate_config?->max_rate_time,
                (float) $dynamic_rate_config?->min_rate,
                (int) $dynamic_rate_config?->min_rate_time,
                (int) date('H'),
                $dynamic_rate_type
            );
        } else {
            $rate = $node->traffic_rate;
        }

        $sum = 0;
        $is_traffic_log = Config::obtain('traffic_log');
        $hourlyUsages = [];
        $now = time();

        foreach ($data as $log) {
            $u = $log?->u ?? 0;
            $d = $log?->d ?? 0;
            $user_id = (int) ($log?->user_id ?? 0);
            $bytes = (int) ($u + $d);

            if ($user_id > 0 && $bytes > 0) {
                $billed_u = $u * $rate;
                $billed_d = $d * $rate;

                User::where('id', $user_id)->incrementEach([
                    'u' => $billed_u,
                    'd' => $billed_d,
                    'transfer_total' => $bytes,
                    'transfer_today' => $billed_u + $billed_d,
                ], ['last_use_time' => $now, 'is_inactive' => 0]);

                if ($is_traffic_log) {
                    $hourlyUsages[$user_id] = ($hourlyUsages[$user_id] ?? 0) + $bytes;
                }
            }

            $sum += $bytes;
        }

        if ($is_traffic_log && $hourlyUsages !== []) {
            HourlyUsage::addBatch($hourlyUsages);
        }

        if ($sum > 0) {
            Node::where('id', $node->id)->increment('node_bandwidth', $sum);
        }

        return $response->withJson([
            'data' => 'ok',
        ]);
    }

    /**
     * POST /api/server/v1/users/online
     */
    public function addOnline(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        /** @var Node|null $node */
        $node = $request->getAttribute('node');

        if ($node === null) {
            return $response->withStatus(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $body = json_decode($request->getBody()->__toString());

        if (! $body || ! isset($body->online) || ! is_array($body->online)) {
            return $response->withStatus(400)->withJson([
                'error' => [
                    'code' => 'invalid_data',
                    'message' => 'Invalid data.',
                ],
            ]);
        }

        OnlineLog::recordBatch((int) $node->id, $body->online);

        return $response->withJson([
            'data' => 'ok',
        ]);
    }

    /**
     * POST /api/server/v1/users/detect_logs
     */
    public function addDetectLogs(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        /** @var Node|null $node */
        $node = $request->getAttribute('node');

        if ($node === null) {
            return $response->withStatus(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $body = json_decode($request->getBody()->__toString());

        if (! $body || ! isset($body->logs) || ! is_array($body->logs)) {
            return $response->withStatus(400)->withJson([
                'error' => [
                    'code' => 'invalid_data',
                    'message' => 'Invalid data.',
                ],
            ]);
        }

        $now = time();
        $rows = [];

        foreach ($body->logs as $log) {
            $list_id = (int) ($log?->rule_id ?? 0);
            $user_id = (int) ($log?->user_id ?? 0);

            if ($user_id <= 0 || $list_id <= 0) {
                continue;
            }

            $rows[] = [
                'user_id' => $user_id,
                'list_id' => $list_id,
                'node_id' => $node->id,
                'datetime' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            (new DetectLog())->insert($chunk);
        }

        return $response->withJson([
            'data' => 'ok',
        ]);
    }
}
