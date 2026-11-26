<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\GiftCard;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Services\DB;
use App\Services\I18n;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function time;
use function trim;

final class MoneyController extends BaseController
{
    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $user = $this->user;
        $moneylogs = (new UserMoneyLog())->where('user_id', $user->id)->orderBy('id', 'desc')->get();

        foreach ($moneylogs as $moneylog) {
            $moneylog->create_time = Tools::toDateTime($moneylog->create_time);
            $moneylog->remark = UserMoneyLog::localizeRemark($moneylog->remark);
        }

        $moneylog_count = $moneylogs->count();
        $enableTabpUpgrade = (bool) Config::obtain('enable_tabp_upgrade');
        $activeTabpInfo = $enableTabpUpgrade ? OrderController::getActiveTabpUpgradeInfo($user) : null;
        $hasActiveTabp = $activeTabpInfo !== null;

        return $response->write(
            $this->view()
                ->assign('moneylogs', $moneylogs)
                ->assign('moneylog_count', $moneylog_count)
                ->assign('has_active_tabp', $hasActiveTabp)
                ->fetch('user/money.tpl')
        );
    }

    public function applyGiftCard(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $giftcard_raw = trim((string) $this->antiXss->xss_clean($request->getParam('giftcard')));

        if ($giftcard_raw === '' || $this->user->is_shadow_banned) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.giftcard_invalid', $locale),
            ]);
        }

        $result = DB::transaction(function () use ($giftcard_raw, $locale): array {
            $user = User::where('id', $this->user->id)->lockForUpdate()->first();
            $giftcard = (new GiftCard())->where('card', $giftcard_raw)->lockForUpdate()->first();

            if ($user === null || $user->is_shadow_banned || $giftcard === null || $giftcard->status !== 0) {
                return [
                    'ret' => 0,
                    'msg' => I18n::trans('user.giftcard_invalid', $locale),
                ];
            }

            $giftcard->status = 1;
            $giftcard->use_time = time();
            $giftcard->use_user = $user->id;
            $giftcard->save();

            $money_before = (float) $user->money;
            $user->money = $money_before + (float) $giftcard->balance;
            $user->save();

            (new UserMoneyLog())->add(
                $user->id,
                $money_before,
                (float) $user->money,
                (float) $giftcard->balance,
                I18n::trans('user.money_remark_gift_card', $locale, ['card' => $giftcard->card])
            );

            return [
                'ret' => 1,
                'msg' => I18n::trans('user.topup_success', $locale),
            ];
        });

        if ($result['ret'] === 1) {
            return $response->withHeader('HX-Refresh', 'true')->withJson($result);
        }

        return $response->withJson($result);
    }
}
