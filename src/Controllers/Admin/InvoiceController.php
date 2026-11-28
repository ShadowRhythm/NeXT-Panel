<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Services\Cron;
use App\Services\DB;
use App\Services\DataTable;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;
use function json_decode;
use function time;

final class InvoiceController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => '操作',
            'id' => '账单ID',
            'user_id' => '归属用户',
            'order_id' => '订单ID',
            'price' => '账单金额',
            'status' => '账单状态',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'pay_time' => '支付时间',
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
                ->fetch('admin/invoice/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $invoice = (new Invoice())->find($id);

        if ($invoice === null) {
            return $response->withStatus(302)->withHeader('Location', '/admin/invoice');
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

        return $response->write(
            $this->view()
                ->assign('invoice', $invoice)
                ->assign('invoice_content', $invoice_content)
                ->assign('paylist', $paylist)
                ->fetch('admin/invoice/view.tpl')
        );
    }

    public function markPaid(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoice_id = $args['id'];

        $result = DB::transaction(static function () use ($invoice_id): array {
            $invoice = (new Invoice())->where('id', $invoice_id)->lockForUpdate()->first();

            if ($invoice === null) {
                return [
                    'ret' => 0,
                    'msg' => '账单不存在',
                ];
            }

            if (in_array($invoice->status, ['paid_gateway', 'paid_balance', 'paid_admin'], true)) {
                return [
                    'ret' => 0,
                    'msg' => '不能标记已经支付的账单',
                ];
            }

            $order = (new Order())->where('id', $invoice->order_id)->lockForUpdate()->first();

            if ($order === null) {
                return [
                    'ret' => 0,
                    'msg' => '关联订单不存在',
                ];
            }

            if ($order->status === 'cancelled') {
                return [
                    'ret' => 0,
                    'msg' => '关联订单已被取消，标记失败',
                ];
            }

            $now = time();
            $invoice->update_time = $now;
            $invoice->pay_time = $now;
            $invoice->status = 'paid_admin';
            $invoice->save();

            $order->update_time = $now;
            $order->status = 'pending_activation';
            $order->save();
            Cron::activateOrder($order);

            return [
                'ret' => 1,
                'msg' => '成功标记账单为已支付（管理员）',
            ];
        });

        return $response->withJson($result);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $invoices = DataTable::paginate(
            (new Invoice())->newQuery(),
            $request,
            ['id', 'user_id', 'order_id', 'type', 'status'],
            ['id', 'user_id', 'order_id', 'price', 'status', 'create_time', 'update_time', 'pay_time']
        );

        foreach ($invoices['data'] as $invoice) {
            $invoice->op = '<a class="btn btn-primary" href="/admin/invoice/' . $invoice->id . '/view">查看</a>';
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
