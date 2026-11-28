<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\InviteCode;
use App\Models\Payback;
use App\Services\I18n;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function implode;

final class InviteController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $eligibility = $this->user->getInviteEligibility($locale);
        $canInvite = $eligibility['allowed'];

        $invite_url = '';
        if ($canInvite) {
            $code = (new InviteCode())->where('user_id', $this->user->id)->first()?->code;
            if ($code === null) {
                $code = (new InviteCode())->add($this->user->id);
            }
            $invite_url = $_ENV['baseUrl'] . '/auth/register?code=' . $code;
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

        $invite_reward_rate = Config::obtain('invite_reward_rate') * 100;

        return $response->write(
            $this->view()
                ->assign('paybacks', $paybacks)
                ->assign('invite_url', $invite_url)
                ->assign('can_invite', $canInvite)
                ->assign('invite_reasons', $eligibility['reasons'])
                ->assign('paybacks_sum', $paybacks_sum)
                ->assign('invite_reward_rate', $invite_reward_rate)
                ->fetch('user/invite.tpl')
        );
    }

    public function reset(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $locale = I18n::getCurrentLocale($user);
        $eligibility = $user->getInviteEligibility($locale);

        if (! $eligibility['allowed']) {
            return ResponseHelper::error($response, implode(' ', $eligibility['reasons']));
        }

        $user->removeInvite();
        $code = (new InviteCode())->add($user->id);

        return $response->withJson([
            'ret' => 1,
            'msg' => I18n::trans('user.reset_success', $locale),
            'data' => [
                'invite-url' => $_ENV['baseUrl'] . '/auth/register?code=' . $code,
            ],
        ]);
    }
}
