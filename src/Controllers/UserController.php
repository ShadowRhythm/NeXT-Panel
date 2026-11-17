<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Ann;
use App\Models\Config;
use App\Services\Analytics;
use App\Models\Node;
use App\Models\StreamMedia;
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
            $hourly_usage = Analytics::getUserTodayHourlyUsage($this->user->id);

            foreach ($hourly_usage as $hour => $usage) {
                $traffic_logs[] = Tools::bToMB((int) $usage);
            }
        }

        $universalSub = Subscribe::getUniversalSubLink($this->user);
        $subUrlParts = explode('/sub/', $universalSub, 2);
        $subUrlPrefix = count($subUrlParts) === 2 ? $subUrlParts[0] . '/sub/' : $universalSub;
        $subUrlToken = count($subUrlParts) === 2 ? $subUrlParts[1] : '';
        $r2Enabled = filter_var($_ENV['enable_r2_client_download'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
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
        $results = [];
        $default_unlock_keys = ['YouTube', 'Netflix', 'Disney+', 'OpenAI', 'Gemini', 'BBC', 'Abema'];
        $ip_keys = ['IPType', 'IPattributes', 'IPRisk'];
        $discovered_unlock_keys = [];

        try {
            $node_ids = StreamMedia::select('node_id')->distinct()->pluck('node_id')->toArray();
        } catch (\Throwable) {
            $node_ids = [];
        }

        foreach ($node_ids as $node_id) {
            $node = (new Node())->find($node_id);
            $unlock = StreamMedia::where('node_id', $node_id)
                ->orderBy('id', 'desc')
                ->where('created_at', '>', \time() - 86460) // 只获取最近一天零一分钟内上报的数据
                ->first();

            if ($unlock !== null && $node !== null) {
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

                foreach (\array_keys($details) as $key) {
                    if (! \in_array($key, $discovered_unlock_keys, true)) {
                        $discovered_unlock_keys[] = $key;
                    }
                }

                $info = [
                    'node_name'   => $node->name,
                    'created_at'  => $unlock->created_at,
                    'unlock_item' => $details,
                    'ip_info'     => $ip_info,
                ];
                $results[] = $info;
            }
        }
		
        $node_names = array_column($results, 'node_name');
        array_multisort($node_names, SORT_ASC, $results);

        $unlock_keys = \array_values(\array_unique(\array_merge($default_unlock_keys, $discovered_unlock_keys)));

        return $response->write($this->view()
            ->assign('results', $results)
            ->assign('unlock_keys', $unlock_keys)
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
}
