<?php

declare(strict_types=1);

namespace App\Controllers\WebAPI;

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
use function json_decode;
use function max;
use function round;
use function time;

final class UserController extends BaseController
{
    /**
     * GET /mod_mu/users
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        $node->update(['node_heartbeat' => time()]);

        if ($node->node_bandwidth_limit !== 0 && $node->node_bandwidth_limit <= $node->node_bandwidth) {
            return ResponseHelper::error($response, 'Node out of bandwidth.');
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
            'method',
            'port',
            'passwd',
            'uuid',
        ]);

        $keys_unset = match ($node->sort) {
            14, 11 => ['u', 'd', 'transfer_enable', 'method', 'port', 'passwd', 'node_iplimit'],
            2 => ['u', 'd', 'transfer_enable', 'method', 'port', 'node_iplimit'],
            1, 4, 5 => ['u', 'd', 'transfer_enable', 'method', 'port', 'uuid', 'node_iplimit'],
            default => ['u', 'd', 'transfer_enable', 'uuid', 'node_iplimit']
        };

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

            if ($node->sort === 1) {
                $method = Tools::decodeCustomConfig($node->custom_config)['method'] ?? '2022-blake3-aes-128-gcm';
                $user_pk = Tools::genSs2022UserPk($user_raw->passwd, $method);

                if (! $user_pk) {
                    continue;
                }

                $user_raw->passwd = $user_pk;
            }

            foreach ($keys_unset as $key) {
                unset($user_raw->$key);
            }

            $users[] = $user_raw;
        }

        return ResponseHelper::successWithDataEtag($request, $response, $users);
    }

    /**
     * POST /mod_mu/users/traffic
     */
    public function addTraffic(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->__toString());

        if (! $data || ! is_array($data->data)) {
            return ResponseHelper::error($response, 'Invalid data.');
        }

        $data = $data->data;

        foreach ($data as $log) {
            $u = $log?->u ?? 0;
            $d = $log?->d ?? 0;

            if (! is_numeric($u) || ! is_numeric($d) || $u < 0 || $d < 0) {
                return ResponseHelper::error($response, 'Invalid data.');
            }
        }

        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
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
        $hourlyBilledUsages = [];
        $now = time();

        foreach ($data as $log) {
            $u = $log?->u ?? 0;
            $d = $log?->d ?? 0;
            $user_id = (int) ($log?->user_id ?? 0);
            $bytes = (int) ($u + $d);

            if ($user_id > 0 && $bytes > 0) {
                $billed_u = $u * $rate;
                $billed_d = $d * $rate;
                $billed_bytes = (int) round($billed_u + $billed_d);

                User::where('id', $user_id)->incrementEach([
                    'u' => $billed_u,
                    'd' => $billed_d,
                    'transfer_total' => $bytes,
                    'transfer_today' => $billed_u + $billed_d,
                ], ['last_use_time' => $now, 'is_inactive' => 0]);

                if ($is_traffic_log) {
                    $hourlyUsages[$user_id] = ($hourlyUsages[$user_id] ?? 0) + $bytes;
                    $hourlyBilledUsages[$user_id] = ($hourlyBilledUsages[$user_id] ?? 0) + $billed_bytes;
                }
            }

            $sum += $bytes;
        }

        if ($is_traffic_log && $hourlyUsages !== []) {
            HourlyUsage::addBatch($hourlyUsages, $hourlyBilledUsages);
        }

        Node::where('id', $node->id)->increment('node_bandwidth', $sum, [
            'online_user' => max(0, count($data) - 1),
        ]);

        return ResponseHelper::success($response, 'ok');
    }

    /**
     * POST /mod_mu/users/aliveip
     */
    public function addAliveIp(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->__toString());

        if (! $data || ! is_array($data->data)) {
            return ResponseHelper::error($response, 'Invalid data.');
        }

        $data = $data->data;
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        OnlineLog::recordBatch((int) $node->id, $data);

        return ResponseHelper::success($response, 'ok');
    }

    /**
     * POST /mod_mu/users/detectlog
     */
    public function addDetectLog(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $data = json_decode($request->getBody()->__toString());

        if (! $data || ! is_array($data->data)) {
            return ResponseHelper::error($response, 'Invalid data.');
        }

        $data = $data->data;
        $node_id = $request->getQueryParam('node_id');
        $node = (new Node())->find($node_id);

        if ($node === null) {
            return ResponseHelper::error($response, 'Node not found.');
        }

        if ($node->type === 0) {
            return ResponseHelper::error($response, 'Node is not enabled.');
        }

        $now = time();
        $rows = [];

        foreach ($data as $log) {
            $list_id = (int) ($log?->list_id ?? 0);
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

        return ResponseHelper::success($response, 'ok');
    }
}
