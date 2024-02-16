<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\InviteCode;
use App\Models\Payback;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class InviteController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        // 获取用户注册日期
        $userRegDate = strtotime($this->user->reg_date);
        $threeMonthsAgo = strtotime("-90 days");
        $userclass = ($this->user->class);

        $code = (new InviteCode())->where('user_id', $this->user->id)->first()?->code;

        if ($code === null) {
            $code = (new InviteCode())->add($this->user->id);
        }

        $paybacks = (new Payback())->where('ref_by', $this->user->id)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($paybacks as $payback) {
            $payback->datetime = Tools::toDateTime($payback->datetime);
        }

        $paybacks_sum = (new Payback())->where('ref_by', $this->user->id)->sum('ref_get');

        if (! $paybacks_sum) {
            $paybacks_sum = 0;
        }

        $invite_url = $_ENV['baseUrl'] . '/auth/register?code=' . $code;
        $invite_reward_rate = Config::obtain('invite_reward_rate') * 100;

        // 邀请限制
        if ($userRegDate > $threeMonthsAgo) {
            $invite_url = '注册时长未满90天，所以暂无邀请权限。过段时间再来看看吧';
        }
        if ($userclass <= 1){
            $invite_url = '当前等级未满2，所以暂无邀请权限。';
        }
        return $response->write(
            $this->view()
                ->assign('paybacks', $paybacks)
                ->assign('invite_url', $invite_url)
                ->assign('paybacks_sum', $paybacks_sum)
                ->assign('invite_reward_rate', $invite_reward_rate)
                ->fetch('user/invite.tpl')
        );
    }

    public function reset(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $user->removeInvite();
        $code = (new InviteCode())->add($user->id);

        return $response->withJson([
            'ret' => 1,
            'msg' => '重置成功',
            'data' => [
                'invite-url' => $_ENV['baseUrl'] . '/auth/register?code=' . $code,
            ],
        ]);
    }
}
