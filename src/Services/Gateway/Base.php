<?php

declare(strict_types=1);

namespace App\Services\Gateway;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\User;
use App\Models\UserMoneyLog;
use App\Services\Cron;
use App\Services\DB;
use App\Services\Reward;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use voku\helper\AntiXSS;
use function get_called_class;
use function in_array;
use function is_array;
use function is_string;
use function parse_url;
use function rtrim;
use function str_starts_with;
use function strtolower;
use function time;
use function trim;
use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

abstract class Base
{
    protected AntiXSS $antiXss;

    abstract public function purchase(ServerRequest $request, Response $response, array $args): ResponseInterface;

    abstract public function notify(ServerRequest $request, Response $response, array $args): ResponseInterface;

    /**
     * 支付网关的 codeName
     */
    abstract public static function _name(): string;

    /**
     * 是否启用支付网关
     */
    abstract public static function _enable(): bool;

    /**
     * 显示给用户的名称
     */
    abstract public static function _readableName(): string;

    public function getReturnHTML(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write('ok');
    }

    abstract public static function getPurchaseHTML(): string;

    public function postPayment(string $trade_no, ?float $actual_amount = null): void
    {
        DB::transaction(function () use ($trade_no, $actual_amount): void {
            $paylist = (new Paylist())->where('tradeno', $trade_no)->lockForUpdate()->first();

            if ($paylist === null || $paylist->status !== 0) {
                return;
            }

            if ($actual_amount !== null) {
                if ((float) $actual_amount < (float) $paylist->total - 0.01) {
                    return;
                }
                if ((float) $actual_amount > (float) $paylist->total) {
                    $paylist->total = (float) $actual_amount;
                }
            }

            $paylist->datetime = time();
            $paylist->status = 1;
            $paylist->save();

            $preInvoice = (new Invoice())->where('id', $paylist->invoice_id)->first();
            $order = null;
            if ($preInvoice !== null) {
                $order = Order::where('id', $preInvoice->order_id)->lockForUpdate()->first();
            }

            $invoice = (new Invoice())->where('id', $paylist->invoice_id)->lockForUpdate()->first();

            if ($invoice !== null &&
                ($invoice->status === 'unpaid' || $invoice->status === 'partially_paid') &&
                (float) $paylist->total >= (float) $invoice->price
            ) {
                $invoice->status = 'paid_gateway';
                $invoice->update_time = time();
                $invoice->pay_time = time();
                $invoice->save();

                if ($order !== null && $order->status === 'pending_payment') {
                    $order->status = 'pending_activation';
                    $order->update_time = time();
                    $order->save();
                    Cron::activateOrder($order);
                }
            }

            $user = (new User())->where('id', $paylist->userid)->lockForUpdate()->first();

            if ($user !== null && $invoice !== null && $invoice->status === 'cancelled') {
                $money_before = (float) $user->money;
                $user->money += (float) $paylist->total;
                $user->save();
                $invoice->status = 'refunded_balance';
                $invoice->update_time = time();
                $invoice->pay_time = time();
                $invoice->save();
                (new UserMoneyLog())->add(
                    $user->id,
                    $money_before,
                    (float) $user->money,
                    (float) $paylist->total,
                    '已取消账单 #' . $invoice->id . ' 支付自动退款至余额'
                );
            } elseif ($user !== null && $invoice !== null && (float) $paylist->total > (float) $invoice->price) {
                $money_before = (float) $user->money;
                $user->money += (float) $paylist->total - (float) $invoice->price;
                $user->save();
                (new UserMoneyLog())->add(
                    $user->id,
                    $money_before,
                    (float) $user->money,
                    (float) $paylist->total - (float) $invoice->price,
                    '超额支付账单 #' . $invoice->id
                );
            }

            if ($invoice !== null && $invoice->status === 'paid_gateway' &&
                $user !== null && $user->ref_by > 0 && Config::obtain('invite_mode') === 'reward') {
                Reward::issuePaybackReward($user->id, $user->ref_by, $invoice->price, $paylist->invoice_id);
            }
        });
    }

    public static function generateGuid(): string
    {
        return Tools::genRandomChar();
    }

    protected static function getCallbackUrl(): string
    {
        return $_ENV['baseUrl'] . '/payment/notify/' . get_called_class()::_name();
    }

    protected static function getUserReturnUrl(): string
    {
        return $_ENV['baseUrl'] . '/user/payment/return/' . get_called_class()::_name();
    }

    /**
     * Restrict gateway return URLs to same-origin paths or the configured baseUrl host.
     */
    protected static function sanitizeReturnUrl(mixed $redir): string
    {
        $fallback = static::getUserReturnUrl();
        if (! is_string($redir)) {
            return $fallback;
        }

        $redir = trim($redir);
        if ($redir === '') {
            return $fallback;
        }

        $baseUrl = rtrim((string) ($_ENV['baseUrl'] ?? ''), '/');
        if (str_starts_with($redir, '/') && ! str_starts_with($redir, '//')) {
            return $baseUrl . $redir;
        }

        $scheme = strtolower((string) parse_url($redir, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($redir, PHP_URL_HOST));
        $baseHost = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));

        if (in_array($scheme, ['http', 'https'], true) && $host !== '' && $host === $baseHost) {
            return $redir;
        }

        return $fallback;
    }

    /**
     * Validate that an upstream gateway redirect URL uses an explicit HTTP(S) scheme and host.
     */
    protected static function isValidExternalRedirectUrl(mixed $url): bool
    {
        if (! is_string($url)) {
            return false;
        }

        $url = trim($url);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && $host !== '';
    }

    protected static function getActiveGateway(string $key): bool
    {
        $raw = Config::obtain('payment_gateway');
        $active_gateways = is_array($raw) ? $raw : Config::parseArrayValue($raw);

        return in_array($key, $active_gateways, true);
    }
}
