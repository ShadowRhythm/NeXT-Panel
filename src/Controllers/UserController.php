<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Ann;
use App\Models\Config;
use App\Services\Analytics;
use App\Models\InviteCode;
use App\Models\LoginIp;
use App\Models\Node;
use App\Models\StreamMedia;
use App\Services\DB;
use App\Models\OnlineLog;
use App\Models\Payback;
use App\Services\Auth;
use App\Services\Captcha;
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

        return $response->write(
            $this->view()
                ->assign('ann', $ann)
                ->assign('captcha', $captcha)
                ->assign('traffic_logs', json_encode($traffic_logs))
                ->assign('class_expire_days', $class_expire_days)
                ->assign('UniversalSub', Subscribe::getUniversalSubLink($this->user))
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
        $pdo = DB::getPdo();
        $nodes = $pdo->query('SELECT DISTINCT node_id FROM stream_media');

        foreach ($nodes as $node_id) {
            $node = Node::where('id', $node_id)->first();

            $unlock = StreamMedia::where('node_id', $node_id)
                ->orderBy('id', 'desc')
                ->where('created_at', '>', \time() - 86460) // 只获取最近一天零一分钟内上报的数据
                ->first();

            if ($unlock !== null && $node !== null) {
                $details = \json_decode($unlock->result, true);
                //$details = str_replace('No (Originals Only)', 'Originals Only', $details);
                //$details = str_replace('Yes (Oversea Only)', 'Oversea Only', $details);

                foreach ($details as $key => $value) {
                    $info = [
                        'node_name' => $node->name,
                        'created_at' => $unlock->created_at,
                        'unlock_item' => $details,
                    ];
                }

                array_push($results, $info);
            }
        }
		
        $node_names = array_column($results, 'node_name');
        array_multisort($node_names, SORT_ASC, $results);

        return $response->write($this->view()
            ->assign('results', $results)
            ->fetch('user/media.tpl'));
    }

    public function checkin(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if (! Config::obtain('enable_checkin') || ! $this->user->isAbleToCheckin()) {
            return ResponseHelper::error($response, '暂时还不能签到');
        }

        if (Config::obtain('enable_checkin_captcha')) {
            $ret = Captcha::verify($request->getParams());

            if (! $ret) {
                return ResponseHelper::error($response, '系统无法接受你的验证结果，请刷新页面后重试');
            }
        }

        $traffic = Reward::issueCheckinReward($this->user->id);

        if (! $traffic) {
            return ResponseHelper::error($response, '签到失败');
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '获得了 ' . $traffic . 'MB 流量',
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
