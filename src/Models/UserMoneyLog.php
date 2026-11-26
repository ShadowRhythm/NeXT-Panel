<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\I18n;
use Illuminate\Database\Query\Builder;
use function preg_match;
use function time;

/**
 * @property int $id 记录ID
 * @property int $user_id 用户ID
 * @property float $before 用户变动前账户余额
 * @property float $after 用户变动后账户余额
 * @property float $amount 变动总额
 * @property string $remark 备注
 * @property int $create_time 创建时间
 *
 * @mixin Builder
 */
final class UserMoneyLog extends Model
{
    protected $connection = 'default';
    protected $table = 'user_money_log';

    public function add(int $user_id, float $before, float $after, float $amount, string $remark): void
    {
        $this->user_id = $user_id;
        $this->before = $before;
        $this->after = $after;
        $this->amount = $amount;
        $this->remark = $remark;
        $this->create_time = time();
        $this->save();
    }

    public static function localizeRemark(string $remark, ?string $locale = null): string
    {
        if (preg_match('/^邀请用户 #(\d+) 返利$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_invite_reward', $locale, ['id' => $m[1]]);
        }
        if (preg_match('/^被用户 #(\d+) 邀请注册奖励$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_invited_reg_reward', $locale, ['id' => $m[1]]);
        }
        if (preg_match('/^(?:支付账单|Pay invoice) #(\d+)$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_pay_invoice', $locale, ['id' => $m[1]]);
        }
        if (preg_match('/^账单 #(\d+) 退款至账户余额$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_invoice_refund', $locale, ['id' => $m[1]]);
        }
        if (preg_match('/^(?:礼品卡充值|Gift Card Top-up) (.+)$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_gift_card', $locale, ['card' => $m[1]]);
        }
        if (preg_match('/^充值订单 #(\d+)$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_topup_order', $locale, ['id' => $m[1]]);
        }
        if (preg_match('/^自动续费订单商品 (.+)$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_auto_renew_product', $locale, ['name' => $m[1]]);
        }
        if (preg_match('/^超额支付账单 #(\d+)$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_overpaid_invoice', $locale, ['id' => $m[1]]);
        }
        if (preg_match('/^已取消账单 #(\d+) 支付自动退款至余额$/u', $remark, $m)) {
            return I18n::trans('user.money_remark_cancelled_invoice_refund', $locale, ['id' => $m[1]]);
        }
        if ($remark === '管理员添加余额') {
            return I18n::trans('user.money_remark_admin_add', $locale);
        }
        if ($remark === '管理员扣除余额') {
            return I18n::trans('user.money_remark_admin_deduct', $locale);
        }
        if ($remark === '余额充值') {
            return I18n::trans('user.topup', $locale);
        }

        return $remark;
    }
}
