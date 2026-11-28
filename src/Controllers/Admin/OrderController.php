<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\Product;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\UserMoneyLog;
use App\Services\DB;
use App\Services\DataTable;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function count;
use function in_array;
use function json_decode;
use function json_encode;
use function max;
use function round;
use function time;

final class OrderController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => '操作',
            'id' => '订单ID',
            'user_id' => '提交用户',
            'product_id' => '商品ID',
            'product_type' => '商品类型',
            'product_name' => '商品名称',
            'coupon' => '优惠码',
            'price' => '金额',
            'status' => '状态',
            'auto_renew' => '自动续费',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ],
    ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/order/index.tpl')
        );
    }

    public function search(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $out_order_id = $request->getParam('gateway_order_id');
        $paylist = (new Paylist())->where('tradeno', $out_order_id)->first();
        $invoice = (new Invoice())->where('id', $paylist?->invoice_id)->first();
        $order = (new Order())->where('id', $invoice?->order_id)->first();

        if ($order === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '未找到订单',
            ]);
        }

        return $response->withHeader('HX-Redirect', '/admin/order/' . $order->id . '/view')->withJson([
            'ret' => 1,
            'msg' => '找到了订单',
        ]);
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $order = (new Order())->find($id);

        if ($order === null) {
            return $response->withStatus(301)->withHeader('Location', '/admin/order');
        }

        $order->raw_status = $order->status;
        $order->product_type_text = $order->productType();
        $order->status_text = $order->status();
        $order->auto_renew_text = $order->autoRenew();
        $order->create_time = Tools::toDateTime($order->create_time);
        $order->update_time = Tools::toDateTime($order->update_time);
        $order->content = json_decode($order->product_content);

        $invoice = (new Invoice())->where('order_id', $id)->first();
        if ($invoice !== null) {
            $invoice->raw_status = $invoice->status;
            $invoice->already_collected = 0.0;

            if ($invoice->raw_status === 'partially_paid') {
                $invoice->already_collected = max(0.0, round((float) $order->price - (float) $invoice->price, 2));
            }

            $invoice->status = $invoice->status();
            $invoice->create_time = Tools::toDateTime($invoice->create_time);
            $invoice->update_time = Tools::toDateTime($invoice->update_time);
            $invoice->pay_time = Tools::toOptionalDateTime((int) $invoice->pay_time);
            $invoice->content = json_decode($invoice->content);
        } else {
            $invoice = (object) [
                'id' => 0,
                'raw_status' => '',
                'already_collected' => 0.0,
                'price' => '-',
                'status' => '无关联账单',
                'create_time' => '-',
                'update_time' => '-',
                'pay_time' => '-',
                'content' => [],
            ];
        }

        return $response->write(
            $this->view()
                ->assign('order', $order)
                ->assign('invoice', $invoice)
                ->fetch('admin/order/view.tpl')
        );
    }

    public function cancel(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $order_id = $args['id'];

        $result = DB::transaction(static function () use ($order_id): array {
            $order = Order::where('id', $order_id)->lockForUpdate()->first();

            if ($order === null) {
                return [
                    'ret' => 0,
                    'msg' => '订单不存在',
                ];
            }

            if (in_array($order->status, ['activated', 'expired', 'superseded', 'cancelled'], true)) {
                return [
                    'ret' => 0,
                    'msg' => '无法取消 ' . $order->status() . ' 状态的订单',
                ];
            }

            $invoice = Invoice::where('order_id', $order->id)->lockForUpdate()->first();

            if ($invoice === null) {
                return [
                    'ret' => 0,
                    'msg' => '关联账单不存在',
                ];
            }

            $isFullyPaid = in_array($invoice->status, ['paid_gateway', 'paid_balance', 'paid_admin'], true);
            $isPartiallyPaid = $invoice->status === 'partially_paid';
            $isUnpaidOrPartial = in_array($invoice->status, ['unpaid', 'partially_paid'], true);

            if ($isPartiallyPaid) {
                $refundAmount = max(0.0, round((float) $order->price - (float) $invoice->price, 2));
            } elseif ($isFullyPaid) {
                $refundAmount = (float) $order->price;
            } else {
                $refundAmount = 0.0;
            }

            if ($refundAmount > 0.0) {
                $user = User::where('id', $order->user_id)->lockForUpdate()->first();

                if ($user !== null) {
                    $oldMoney = (float) $user->money;
                    $newMoney = round($oldMoney + $refundAmount, 2);
                    $user->money = $newMoney;
                    $user->save();

                    (new UserMoneyLog())->add(
                        $user->id,
                        $oldMoney,
                        $newMoney,
                        $refundAmount,
                        '订单 #' . $order->id . ' 取消退款至账户余额'
                    );

                    $content = json_decode($invoice->content, true) ?: [];
                    $content[] = [
                        'content_id' => count($content),
                        'name' => '退款至账户余额',
                        'price' => '-' . $refundAmount,
                    ];
                    $invoice->content = json_encode($content);
                }
            }

            if ($isUnpaidOrPartial) {
                $product = Product::find($order->product_id);
                if ($product !== null) {
                    if ($product->stock >= 0) {
                        Product::where('id', $product->id)->increment('stock', 1);
                    }
                    Product::where('id', $product->id)->where('sale_count', '>', 0)->decrement('sale_count', 1);
                }

                if ($order->coupon !== '') {
                    UserCoupon::where('code', $order->coupon)->where('use_count', '>', 0)->decrement('use_count', 1);
                }
            }

            $order->status = 'cancelled';
            $order->update_time = time();
            $order->save();

            $invoice->status = $isFullyPaid ? 'refunded_balance' : 'cancelled';
            $invoice->update_time = time();
            $invoice->save();

            if ($refundAmount > 0.0) {
                return [
                    'ret' => 1,
                    'msg' => '订单取消成功，已退款 ¥' . $refundAmount . ' 至用户余额',
                ];
            }

            return [
                'ret' => 1,
                'msg' => '订单取消成功',
            ];
        });

        return $response->withJson($result);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $order_id = $args['id'];
        $order = (new Order())->find($order_id);

        if ($order === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '订单不存在',
            ]);
        }

        $invoice = (new Invoice())->where('order_id', $order_id)->first();

        if ($order->delete() && ($invoice === null || $invoice->delete())) {
            return $response->withJson([
                'ret' => 1,
                'msg' => '删除成功',
            ]);
        }

        return $response->withJson([
            'ret' => 0,
            'msg' => '删除失败',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $orders = DataTable::paginate(
            (new Order())->newQuery(),
            $request,
            ['id', 'user_id', 'product_name', 'product_type', 'status'],
            ['id', 'user_id', 'product_name', 'product_type', 'price', 'status', 'auto_renew', 'create_time', 'update_time']
        );

        foreach ($orders['data'] as $order) {
            $order->op = '<button class="btn btn-red" id="delete-order-' . $order->id . '"
             onclick="deleteOrder(' . $order->id . ')">删除</button>';

            if (in_array($order->status, ['pending_payment', 'pending_activation'])) {
                $order->op .= '
                <button class="btn btn-orange" id="cancel-order-' . $order->id . '"
                 onclick="cancelOrder(' . $order->id . ')">取消</button>';
            }

            $order->op .= '
            <a class="btn btn-primary" href="/admin/order/' . $order->id . '/view">查看</a>';

            if (in_array($order->product_type, ['tabp', 'time'], true)) {
                $badgeClass = match ((int) $order->auto_renew) {
                    1 => 'bg-green text-green-fg',
                    2 => 'bg-danger text-danger-fg',
                    default => 'bg-secondary text-secondary-fg',
                };
                $badgeText = $order->autoRenew();
                $order->auto_renew_html = '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
            } else {
                $order->auto_renew_html = '-';
            }

            $order->product_type = $order->productType();
            $order->status = $order->status();
            $order->create_time = Tools::toDateTime($order->create_time);
            $order->update_time = Tools::toDateTime($order->update_time);
        }

        $orders['orders'] = $orders['data']->map(static function (Order $order): array {
            $row = $order->toArray();
            $row['auto_renew'] = $order->auto_renew_html ?? '-';
            unset($row['auto_renew_html']);
            return $row;
        });
        unset($orders['data']);
        return $response->withJson($orders);
    }

    public function toggleRenew(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $order = (new Order())->find($id);

        if ($order === null || ! in_array($order->product_type, ['tabp', 'time'], true)) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '订单不存在或不支持自动续费',
            ]);
        }

        $requested = $request->getParam('auto_renew');
        if ($requested !== null && $requested !== '' && ! in_array($requested, [0, 1, '0', '1', false, true], true)) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '自动续费状态无效',
            ]);
        }

        $enabled = ($requested !== null && $requested !== '')
            ? in_array($requested, [1, '1', true], true)
            : ((int) $order->auto_renew === 0);

        $order->auto_renew = $enabled ? 1 : 0;
        $order->save();

        $badgeClass = match ((int) $order->auto_renew) {
            1 => 'bg-green text-green-fg',
            2 => 'bg-danger text-danger-fg',
            default => 'bg-secondary text-secondary-fg',
        };
        $badgeHtml = '<span id="auto-renew-badge" class="badge ' . $badgeClass . '">' . $order->autoRenew() . '</span>';
        $btnText = '<i class="icon ti ti-refresh"></i> ' . ((int) $order->auto_renew === 1 ? '关闭自动续费' : '开启自动续费');

        return $response->withJson([
            'ret' => 1,
            'msg' => (int) $order->auto_renew === 1 ? '已为该订单开启自动续费' : '已为该订单关闭自动续费',
            'data' => [
                'auto-renew-badge-container' => $badgeHtml,
                'admin-toggle-renew-btn' => $btnText,
            ],
        ]);
    }
}
