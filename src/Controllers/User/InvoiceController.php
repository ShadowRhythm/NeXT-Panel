<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Services\Cron;
use App\Services\DB;
use App\Services\DataTable;
use App\Services\I18n;
use App\Services\Payment;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function is_array;
use function json_decode;
use function json_encode;
use function time;

final class InvoiceController extends BaseController
{
    private static function getDetails(): array
    {
        return [
            'field' => [
                'op' => I18n::trans('common.action'),
                'id' => I18n::trans('user.invoice_id'),
                'order_id' => I18n::trans('user.order_id'),
                'price' => I18n::trans('user.invoice_amount'),
                'status' => I18n::trans('user.invoice_status'),
                'create_time' => I18n::trans('user.create_time'),
                'update_time' => I18n::trans('user.update_time'),
                'pay_time' => I18n::trans('user.payment_time'),
            ],
        ];
    }

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::getDetails())
                ->fetch('user/invoice/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $this->antiXss->xss_clean($args['id']);

        $invoice = (new Invoice())->where('user_id', $this->user->id)->where('id', $id)->first();

        if ($invoice === null) {
            return $response->withRedirect('/user/invoice');
        }

        $paylist = [];

        if ($invoice->status === 'paid_gateway') {
            $paylist = (new Paylist())->where('invoice_id', $invoice->id)->where('status', 1)->first();
        }

        $invoice->status_text = $invoice->status();
        $invoice->create_time = Tools::toDateTime($invoice->create_time);
        $invoice->update_time = Tools::toDateTime($invoice->update_time);
        $invoice->pay_time = Tools::toOptionalDateTime((int) $invoice->pay_time);
        $invoice_content = json_decode($invoice->content);
        if (is_array($invoice_content)) {
            foreach ($invoice_content as $item) {
                if (isset($item->name)) {
                    if ($item->name === '余额充值') {
                        $item->name = I18n::trans('user.topup');
                    } elseif ($item->name === '退款至账户余额') {
                        $item->name = I18n::trans('user.refund_to_balance');
                    } elseif (preg_match('/(?:套餐升[级級]折抵|Package Upgrade Credit|Crédit de mise à niveau|パッケージアップグレード割引|Crédito de upgrade|Скидка на апгрейд|Crédito por mejora de paquete|Khấu trừ nâng cấp gói|Paket-Upgrade-Anrechnung|اعتبار ارتقای بسته|Paket Yükseltme Kredisi|رصيد تعويض ترقية الباقة|Kredit Upgrade Paket|요금제 업그레이드 차감).*?#(\d+)/u', $item->name, $matches)) {
                        $item->name = I18n::trans('user.upgrade_credit_item', null, ['id' => $matches[1]]);
                    } elseif (preg_match('/^(?:优惠码|優惠碼|Coupon Code|Coupon|Code promo|クーポンコード|Código do cupom|Código de cupón|Mã giảm giá|Gutscheincode|Промокод|کد تخفیف|Kupon Kodu|رمز القسيمة|Kode Kupon|쿠폰 코드)\s+(.+)$/u', $item->name, $matches)) {
                        $item->name = I18n::trans('user.coupon') . ' ' . $matches[1];
                    }
                }
            }
        }

        return $response->write(
            $this->view()
                ->assign('invoice', $invoice)
                ->assign('invoice_content', $invoice_content)
                ->assign('paylist', $paylist)
                ->assign('payments', Payment::getPaymentsEnabled())
                ->fetch('user/invoice/view.tpl')
        );
    }

    public function payBalance(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoice_id = $this->antiXss->xss_clean($request->getParam('invoice_id'));

        return DB::transaction(function () use ($response, $invoice_id): ResponseInterface {
            $invoice = (new Invoice())->where('user_id', $this->user->id)->where('id', $invoice_id)->first();

            if ($invoice === null) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.invoice_not_found'),
                ]);
            }

            $order = Order::where('id', $invoice->order_id)->lockForUpdate()->first();
            $invoice = Invoice::where('user_id', $this->user->id)
                ->where('id', $invoice_id)
                ->lockForUpdate()
                ->first();

            if ($invoice === null) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.invoice_not_found'),
                ]);
            }

            $user = (new User())->where('id', $this->user->id)->lockForUpdate()->first();

            if ($user === null || $user->is_shadow_banned) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.payment_failed_try_again'),
                ]);
            }

            // 账单是否为充值
            if ($invoice->type === 'topup') {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.invoice_topup_no_balance_pay'),
                ]);
            }

            if ($invoice->status !== 'unpaid' && $invoice->status !== 'partially_paid') {
                return $response->withHeader('HX-Redirect', '/user/invoice')->withJson([
                    'ret' => 2,
                    'msg' => I18n::trans('user.invoice_already_paid'),
                ]);
            }

            if ($order === null || $order->status !== 'pending_payment') {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.order_invalid_or_cancelled'),
                ]);
            }

            if ($user->money < $invoice->price) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => I18n::trans('user.insufficient_balance'),
                ]);
            }

            $money_before = $user->money;
            $user->money -= $invoice->price;
            $user->save();

            (new UserMoneyLog())->add(
                $user->id,
                $money_before,
                (float) $user->money,
                -$invoice->price,
                I18n::trans('user.money_remark_pay_invoice', null, ['id' => $invoice->id])
            );

            $invoice->status = 'paid_balance';
            $invoice->update_time = time();
            $invoice->pay_time = time();
            $invoice->save();

            if ($order !== null && $order->status === 'pending_payment') {
                $order->status = 'pending_activation';
                $order->update_time = time();
                $order->save();
                Cron::activateOrder($order);
            }

            return $response->withHeader('HX-Redirect', '/user/invoice');
        });
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoices = DataTable::paginate(
            (new Invoice())->newQuery()->where('user_id', $this->user->id),
            $request,
            ['id', 'order_id', 'type', 'status'],
            ['id', 'order_id', 'price', 'status', 'create_time', 'update_time', 'pay_time']
        );

        foreach ($invoices['data'] as $invoice) {
            $invoice->op = '<a class="btn btn-primary" href="/user/invoice/' . $invoice->id . '/view">' . I18n::trans('user.view') . '</a>';
            $invoice->status = $invoice->status();
            $invoice->create_time = Tools::toDateTime($invoice->create_time);
            $invoice->update_time = Tools::toDateTime($invoice->update_time);
            $invoice->pay_time = Tools::toOptionalDateTime((int) $invoice->pay_time);
        }

        $invoices['invoices'] = $invoices['data'];
        unset($invoices['data']);
        return $response->withJson($invoices);
    }
}
