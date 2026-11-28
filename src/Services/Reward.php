<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use App\Models\Payback;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Utils\Tools;
use Exception;
use function min;
use function random_int;
use function round;
use function time;

final class Reward
{
    /**
     * Calculate payback reward amount from mode, limits, and existing per-invitee totals.
     */
    public static function calculatePaybackAmount(
        string $mode,
        float $total,
        float $rate,
        int $countLimit,
        int $existingCount,
        float $totalLimit,
        float $existingTotal
    ): float {
        if ($total <= 0 || $rate <= 0) {
            return 0.0;
        }

        if ($mode === 'reward_count') {
            if ($countLimit <= 0 || $existingCount >= $countLimit) {
                return 0.0;
            }

            return round($total * $rate, 2);
        }

        if ($mode === 'reward_total') {
            $remaining = round($totalLimit - $existingTotal, 2);
            if ($totalLimit <= 0 || $remaining <= 0) {
                return 0.0;
            }

            return min(round($total * $rate, 2), $remaining);
        }

        return 0.0;
    }

    public static function issuePaybackReward($user_id, $ref_user_id, $total, $invoice_id): void
    {
        $userId = (int) $user_id;
        $refUserId = (int) $ref_user_id;
        $orderTotal = (float) $total;
        $invoiceId = (int) $invoice_id;

        if ($orderTotal <= 0 || $userId <= 0 || $refUserId <= 0 || $userId === $refUserId) {
            return;
        }

        DB::transaction(static function () use ($userId, $refUserId, $orderTotal, $invoiceId): void {
            $ref_user = (new User())->where('id', $refUserId)
                ->where('is_banned', 0)
                ->where('is_shadow_banned', 0)
                ->lockForUpdate()
                ->first();

            $exsit_payback = (new Payback())->where('userid', $userId)
                ->where('invoice_id', $invoiceId)
                ->lockForUpdate()
                ->first();

            if ($ref_user === null || $exsit_payback !== null) {
                return;
            }

            $invite_reward_mode = (string) Config::obtain('invite_reward_mode');
            $invite_reward_rate = (float) Config::obtain('invite_reward_rate');
            $countLimit = (int) Config::obtain('invite_reward_count_limit');
            $totalLimit = (float) Config::obtain('invite_reward_total_limit');

            $existingCount = (int) (new Payback())->where('userid', $userId)
                ->where('ref_by', $refUserId)
                ->count();
            $existingTotal = (float) ((new Payback())->where('userid', $userId)
                ->where('ref_by', $refUserId)
                ->sum('ref_get') ?? 0);

            $ref_get = self::calculatePaybackAmount(
                $invite_reward_mode,
                $orderTotal,
                $invite_reward_rate,
                $countLimit,
                $existingCount,
                $totalLimit,
                $existingTotal
            );

            if ($ref_get <= 0) {
                return;
            }

            $money_before = (float) $ref_user->money;
            $ref_user->money = round($money_before + $ref_get, 2);
            $ref_user->save();

            // 添加余额记录
            (new UserMoneyLog())->add(
                $ref_user->id,
                $money_before,
                (float) $ref_user->money,
                $ref_get,
                '邀请用户 #' . $userId . ' 返利',
            );
            // 添加返利记录
            (new Payback())->add(
                $orderTotal,
                $userId,
                $refUserId,
                $ref_get,
                $invoiceId,
            );
        });
    }

    public static function issueRegReward($user_id, $ref_user_id): void
    {
        $invite_reg_money_reward = round((float) Config::obtain('invite_reg_money_reward'), 2);
        $invite_reg_traffic_reward = (float) Config::obtain('invite_reg_traffic_reward');

        $user = (new User())->where('id', $user_id)->first();

        $ref_user = (new User())->where('id', $ref_user_id)
            ->where('is_banned', 0)
            ->where('is_shadow_banned', 0)
            ->first();

        if ($user !== null && $ref_user !== null) {
            if ($invite_reg_money_reward > 0) {
                $money_before = (float) $user->money;
                $user->money = round($money_before + $invite_reg_money_reward, 2);
                $user->save();
                // 添加余额记录
                (new UserMoneyLog())->add(
                    $user->id,
                    $money_before,
                    (float) $user->money,
                    $invite_reg_money_reward,
                    '被用户 #' . $ref_user_id . ' 邀请注册奖励',
                );
            }

            if ($invite_reg_traffic_reward > 0) {
                $ref_user->transfer_enable += Tools::gbToB($invite_reg_traffic_reward);
                $ref_user->save();
            }
        }
    }

    public static function issueCheckinReward($user_id): int|false
    {
        return DB::transaction(static function () use ($user_id): int|false {
            $user = (new User())->where('id', $user_id)->lockForUpdate()->first();

            if ($user === null || ! $user->isAbleToCheckin()) {
                return false;
            }

            $checkin_min = (int) Config::obtain('checkin_min');
            $checkin_max = (int) Config::obtain('checkin_max');

            if ($checkin_min >= $checkin_max) {
                $traffic = $checkin_min;
            } else {
                try {
                    $traffic = random_int($checkin_min, $checkin_max);
                } catch (Exception) {
                    $traffic = 0;
                }
            }

            if ($traffic !== 0) {
                $user->transfer_enable += Tools::mbToB($traffic);
                $user->last_check_in_time = time();
                $user->is_inactive = 0;
                $user->save();
            }

            return $traffic;
        });
    }
}
