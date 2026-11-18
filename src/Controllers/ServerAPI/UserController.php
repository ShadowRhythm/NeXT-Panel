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
use function count;
use function date;
use function is_array;
use function is_string;
use function json_decode;
use function max;
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

        foreach ($users_raw as $user_raw) {
            if ($user_raw->transfer_enable <= $user_raw->u + $user_raw->d) {
                if (Config::obtain('keep_connect')) {
                    // 流量耗尽用户限速至 1Mbps
                    $user_raw->node_speedlimit = 1;
                } else {
                    continue;
                }
            }

            if ($user_raw->node_iplimit !== 0 &&
                $user_raw->node_iplimit <
                (new OnlineLog())
                    ->where('user_id', $user_raw->id)
                    ->where('last_time', '>', time() - 90)
                    ->count()
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

        foreach ($data as $log) {
            $u = $log?->u;
            $d = $log?->d;
            $user_id = $log?->user_id;

            if ($user_id) {
                $billed_u = $u * $rate;
                $billed_d = $d * $rate;

                User::where('id', $user_id)->incrementEach([
                    'u' => $billed_u,
                    'd' => $billed_d,
                    'transfer_total' => $u + $d,
                    'transfer_today' => $billed_u + $billed_d,
                ], ['last_use_time' => time()]);
            }

            if ($is_traffic_log) {
                (new HourlyUsage())->add((int) $user_id, (int) ($u + $d));
            }

            $sum += $u + $d;
        }

        Node::where('id', $node->id)->increment('node_bandwidth', $sum, [
            'online_user' => max(0, count($data) - 1),
        ]);

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

        $data = $body->online;

        foreach ($data as $log) {
            $ip = (string) ($log?->ip ?? '');
            $user_id = (int) ($log?->user_id ?? 0);

            if (Tools::isIPv4($ip)) {
                // convert IPv4 Address to IPv4-mapped IPv6 Address
                $ip = '::ffff:' . $ip;
            } elseif (! Tools::isIPv6($ip)) {
                // either IPv4 or IPv6 Address
                continue;
            }

            (new OnlineLog())->upsert(
                [
                    'user_id' => $user_id,
                    'ip' => $ip,
                    'node_id' => $node->id,
                    'first_time' => time(),
                    'last_time' => time(),
                ],
                ['user_id', 'ip'],
                ['node_id', 'last_time']
            );
        }

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

        $data = $body->logs;

        foreach ($data as $log) {
            $list_id = (int) ($log?->rule_id ?? 0);
            $user_id = (int) ($log?->user_id ?? 0);

            (new DetectLog())->insert([
                'user_id' => $user_id,
                'list_id' => $list_id,
                'node_id' => $node->id,
                'datetime' => time(),
            ]);
        }

        return $response->withJson([
            'data' => 'ok',
        ]);
    }
}
