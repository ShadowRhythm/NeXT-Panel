<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Ann;
use App\Models\Config;
use App\Services\Analytics;
use App\Models\Node;
use App\Models\StreamMedia;
use App\Services\Cache;
use App\Services\DB;
use App\Services\Auth;
use App\Services\Captcha;
use App\Services\Config\ClientConfig;
use App\Services\I18n;
use App\Services\Reward;
use App\Services\Subscribe;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function json_encode;
use function strtotime;
use function time;

final class UserController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $captcha = [];
        $traffic_logs = [];
        $raw_traffic_logs = [];
        $traffic_logs_labels = [];
        $traffic_unit = 'MB';
        $daily_traffic_logs = [];
        $raw_daily_traffic_logs = [];
        $daily_traffic_labels = [];
        $daily_traffic_unit = 'MB';
        $monthly_traffic_logs = [];
        $raw_monthly_traffic_logs = [];
        $monthly_traffic_labels = [];
        $monthly_traffic_unit = 'GB';
        $class_expire_days = $this->user->class > 0 ?
            round((strtotime($this->user->class_expire) - time()) / 86400) : 0;
        $ann = (new Ann())->where('status', '>', 0)
            ->orderBy('status', 'desc')
            ->orderBy('sort')
            ->orderBy('date', 'desc')->first();

        if (Config::obtain('enable_checkin') &&
            Config::obtain('enable_checkin_captcha') &&
            $this->user->isAbleToCheckin()) {
            $captcha = Captcha::generate();
        }

        if (Config::obtain('traffic_log')) {
            $chartData = Cache::remember('user_dashboard_traffic_v3_' . $this->user->id, 60, function (): array {
                $uid = $this->user->id;
                $buildSeries = static function (array $data): array {
                    $billedBytes = $data['billed_usage'] ?? [];
                    $rawBytes = $data['usage'] ?? [];
                    $maxBytes = 0;

                    foreach ($billedBytes as $bytes) {
                        $val = (int) $bytes;
                        if ($val > $maxBytes) {
                            $maxBytes = $val;
                        }
                    }

                    $useGb = $maxBytes >= 1073741824;
                    $billed = [];
                    foreach ($billedBytes as $bytes) {
                        $billed[] = $useGb ? Tools::bToGB((int) $bytes) : Tools::bToMB((int) $bytes);
                    }
                    $raw = [];
                    foreach ($rawBytes as $bytes) {
                        $raw[] = $useGb ? Tools::bToGB((int) $bytes) : Tools::bToMB((int) $bytes);
                    }

                    return [
                        'billed' => $billed,
                        'raw' => $raw,
                        'labels' => $data['labels'] ?? [],
                        'unit' => $useGb ? 'GB' : 'MB',
                    ];
                };

                $hourly = $buildSeries(Analytics::getUserPast24HoursUsage($uid));
                $daily = $buildSeries(Analytics::getUserPast30DaysUsage($uid));
                $monthly = $buildSeries(Analytics::getUserPast12MonthsUsage($uid));

                return [
                    'traffic_logs' => $hourly['billed'],
                    'raw_traffic_logs' => $hourly['raw'],
                    'traffic_logs_labels' => $hourly['labels'],
                    'traffic_unit' => $hourly['unit'],
                    'daily_traffic_logs' => $daily['billed'],
                    'raw_daily_traffic_logs' => $daily['raw'],
                    'daily_traffic_labels' => $daily['labels'],
                    'daily_traffic_unit' => $daily['unit'],
                    'monthly_traffic_logs' => $monthly['billed'],
                    'raw_monthly_traffic_logs' => $monthly['raw'],
                    'monthly_traffic_labels' => $monthly['labels'],
                    'monthly_traffic_unit' => $monthly['unit'],
                ];
            });

            $traffic_logs = $chartData['traffic_logs'];
            $raw_traffic_logs = $chartData['raw_traffic_logs'];
            $traffic_logs_labels = $chartData['traffic_logs_labels'];
            $traffic_unit = $chartData['traffic_unit'];
            $daily_traffic_logs = $chartData['daily_traffic_logs'];
            $raw_daily_traffic_logs = $chartData['raw_daily_traffic_logs'];
            $daily_traffic_labels = $chartData['daily_traffic_labels'];
            $daily_traffic_unit = $chartData['daily_traffic_unit'];
            $monthly_traffic_logs = $chartData['monthly_traffic_logs'];
            $raw_monthly_traffic_logs = $chartData['raw_monthly_traffic_logs'];
            $monthly_traffic_labels = $chartData['monthly_traffic_labels'];
            $monthly_traffic_unit = $chartData['monthly_traffic_unit'];
        }

        $universalSub = Subscribe::getUniversalSubLink($this->user);
        $subUrlParts = explode('/sub/', $universalSub, 2);
        $subUrlPrefix = count($subUrlParts) === 2 ? $subUrlParts[0] . '/sub/' : $universalSub;
        $subUrlToken = count($subUrlParts) === 2 ? $subUrlParts[1] : '';
        $r2Enabled = (bool) Config::obtain('enable_r2_client_download');
        $clientData = ClientConfig::getClients(
            $universalSub,
            $_ENV['appName'] ?? 'SSPanel',
            $r2Enabled
        );

        return $response->write(
            $this->view()
                ->assign('ann', $ann)
                ->assign('captcha', $captcha)
                ->assign('traffic_logs', json_encode($traffic_logs))
                ->assign('raw_traffic_logs', json_encode($raw_traffic_logs))
                ->assign('traffic_logs_labels', json_encode($traffic_logs_labels))
                ->assign('traffic_unit', $traffic_unit)
                ->assign('daily_traffic_logs', json_encode($daily_traffic_logs))
                ->assign('raw_daily_traffic_logs', json_encode($raw_daily_traffic_logs))
                ->assign('daily_traffic_labels', json_encode($daily_traffic_labels))
                ->assign('daily_traffic_unit', $daily_traffic_unit)
                ->assign('monthly_traffic_logs', json_encode($monthly_traffic_logs))
                ->assign('raw_monthly_traffic_logs', json_encode($raw_monthly_traffic_logs))
                ->assign('monthly_traffic_labels', json_encode($monthly_traffic_labels))
                ->assign('monthly_traffic_unit', $monthly_traffic_unit)
                ->assign('class_expire_days', $class_expire_days)
                ->assign('UniversalSub', $universalSub)
                ->assign('subUrlPrefix', $subUrlPrefix)
                ->assign('subUrlToken', $subUrlToken)
                ->assign('clientData', json_encode($clientData['clients']))
                ->assign('platformIcons', json_encode($clientData['icons']))
                ->assign('user_class', $this->user->class)
                ->assign('user_money', $this->user->money)
                ->assign('ip_limit', $this->user->node_iplimit)
                ->assign('speed_limit', $this->user->node_speedlimit)
                ->fetch('user/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function announcement(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $anns = (new Ann())->where('status', '>', 0)
            ->orderBy('status', 'desc')
            ->orderBy('sort')
            ->orderBy('date', 'desc')->get();

        return $response->write(
            $this->view()
                ->assign('anns', $anns)
                ->fetch('user/announcement.tpl')
        );
    }

    /**
     * @throws Exception
     */	
    public function media(ServerRequest $request, Response $response, array $args)
    {
        $default_unlock_keys = ['YouTube', 'Netflix', 'Disney+', 'OpenAI', 'Gemini', 'TikTok', 'Abema'];
        $ip_keys = ['IPType', 'IPattributes', 'IPRisk'];

        $cached = Cache::remember('user_media_unlock_results', 60, static function () use ($default_unlock_keys, $ip_keys): array {
            $results = [];
            $discovered_unlock_keys = [];

            try {
                $latestIds = StreamMedia::where('created_at', '>', \time() - 86460)
                    ->selectRaw('MAX(id) as max_id')
                    ->groupBy('node_id')
                    ->pluck('max_id')
                    ->all();
            } catch (\Throwable) {
                $latestIds = [];
            }

            if ($latestIds !== []) {
                $unlocks = StreamMedia::whereIn('id', $latestIds)->get();
                $nodeIds = $unlocks->pluck('node_id')->unique()->filter()->values()->all();
                $nodes = $nodeIds !== []
                    ? Node::whereIn('id', $nodeIds)->get(['id', 'name'])->keyBy('id')
                    : null;

                foreach ($unlocks as $unlock) {
                    $node = $nodes?->get($unlock->node_id);
                    if ($node === null) {
                        continue;
                    }

                    $details = \json_decode($unlock->result, true);
                    if (! \is_array($details)) {
                        $details = [];
                    }

                    // 从 result JSON 中提取 IP 质量字段，剩余为流媒体解锁结果
                    $ip_info = [];
                    foreach ($ip_keys as $key) {
                        if (isset($details[$key])) {
                            $ip_info[$key] = $details[$key];
                            unset($details[$key]);
                        }
                    }

                    // 移除解锁结果中多余的 (Native) 标识（如 OpenAI 的 Yes (Native) -> Yes）
                    foreach ($details as $k => $v) {
                        if (\is_string($v)) {
                            $details[$k] = \trim(\str_replace([' (Native)', '(Native)'], '', $v));
                        }
                    }

                    foreach (\array_keys($details) as $key) {
                        if (! \in_array($key, $discovered_unlock_keys, true)) {
                            $discovered_unlock_keys[] = $key;
                        }
                    }

                    $results[] = [
                        'node_name'   => $node->name,
                        'created_at'  => $unlock->created_at,
                        'unlock_item' => $details,
                        'ip_info'     => $ip_info,
                    ];
                }
            }

            $node_names = array_column($results, 'node_name');
            array_multisort($node_names, SORT_ASC, $results);

            return [
                'results' => $results,
                'unlock_keys' => \array_values(\array_unique(\array_merge($default_unlock_keys, $discovered_unlock_keys))),
            ];
        });

        return $response->write($this->view()
            ->assign('results', $cached['results'])
            ->assign('unlock_keys', $cached['unlock_keys'])
            ->assign('ip_keys', $ip_keys)
            ->fetch('user/media.tpl'));
    }
	
    public function checkin(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);

        if (! Config::obtain('enable_checkin') || ! $this->user->isAbleToCheckin()) {
            return ResponseHelper::error($response, I18n::trans('user.checkin_not_allowed', $locale));
        }

        if (Config::obtain('enable_checkin_captcha')) {
            $ret = Captcha::verify($request->getParams());

            if (! $ret) {
                return ResponseHelper::error($response, I18n::trans('user.captcha_failed', $locale));
            }
        }

        $traffic = Reward::issueCheckinReward($this->user->id);

        if (! $traffic) {
            return ResponseHelper::error($response, I18n::trans('user.checkin_failed', $locale));
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.checkin_success', $locale, ['traffic' => $traffic]),
            'data' => [
                'last-checkin-time' => Tools::toDateTime(time()),
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function banned(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('banned_reason', $this->user->banned_reason)
                ->fetch('user/banned.tpl')
        );
    }

    public function logout(ServerRequest $request, Response $response, array $args): Response
    {
        Auth::logout();

        return $response->withStatus(302)->withHeader('Location', '/');
    }

    public function leaveImpersonation(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $result = Auth::leaveImpersonation();

        if (! $result['ok']) {
            return $response->withStatus(403)->withJson([
                'ret' => 0,
                'msg' => $result['msg'],
            ]);
        }

        $targetUid = (int) ($result['target_uid'] ?? 0);
        $redirect = $targetUid > 0 ? '/admin/user/' . $targetUid . '/edit' : '/admin/user';

        return $response
            ->withHeader('HX-Redirect', $redirect)
            ->withJson([
                'ret' => 1,
                'msg' => $result['msg'],
                'redirect' => $redirect,
            ]);
    }
}
