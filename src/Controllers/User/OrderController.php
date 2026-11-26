<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserCoupon;
use App\Services\Cron;
use App\Services\DB;
use App\Services\DataTable;
use App\Services\I18n;
use App\Utils\Cookie;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function explode;
use function in_array;
use function is_numeric;
use function json_decode;
use function json_encode;
use function max;
use function min;
use function property_exists;
use function round;
use function time;

final class OrderController extends BaseController
{
    private static function getDetails(): array
    {
        return [
            'field' => [
                'op' => I18n::trans('common.action'),
                'id' => I18n::trans('user.order_id'),
                'product_id' => I18n::trans('user.product_id'),
                'product_type' => I18n::trans('user.product_type'),
                'product_name' => I18n::trans('user.product_name'),
                'coupon' => I18n::trans('user.coupon'),
                'price' => I18n::trans('user.order_amount'),
                'status' => I18n::trans('common.status'),
                'create_time' => I18n::trans('user.create_time'),
                'update_time' => I18n::trans('user.update_time'),
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
                ->fetch('user/order/index.tpl')
        );
    }

    public static function getActiveTabpUpgradeInfo(User $user): ?array
    {
        $activeTabpOrder = Order::where('user_id', $user->id)
            ->where('product_type', 'tabp')
            ->where('status', 'activated')
            ->orderBy('id', 'desc')
            ->first();

        if ($activeTabpOrder === null) {
            return null;
        }

        $activeContent = json_decode($activeTabpOrder->product_content);
        if ($activeContent === null) {
            return null;
        }

        $totalDuration = max(1, (int) ($activeContent->time ?? $activeContent->class_time ?? 30) * 86400);
        $expireTime = $activeTabpOrder->update_time + $totalDuration;
        if ($expireTime <= time()) {
            return null;
        }

        $timeRemaining = max(0, $expireTime - time());
        $timeRatio = min(1.0, max(0.0, $timeRemaining / $totalDuration));

        $totalBandwidth = Tools::gbToB($activeContent->bandwidth ?? 0);
        $usedTraffic = (int) ($user->u + $user->d);
        $trafficRemaining = max(0, $totalBandwidth - $usedTraffic);
        $trafficRatio = $totalBandwidth > 0 ? min(1.0, max(0.0, $trafficRemaining / $totalBandwidth)) : 0.0;

        $ratio = min($timeRatio, $trafficRatio);

        $paidPrice = (float) $activeTabpOrder->price;
        $baseCredit = round($paidPrice * $ratio, 2);
        $maxRatioVal = Config::obtain('tabp_upgrade_credit_max_ratio');
        $maxRatioPercent = ($maxRatioVal !== '' && $maxRatioVal !== null) ? (float) $maxRatioVal : 100.0;
        if ($maxRatioPercent < 0) {
            $maxRatioPercent = 0.0;
        } elseif ($maxRatioPercent > 100) {
            $maxRatioPercent = 100.0;
        }
        $maxAllowedCredit = round($paidPrice * ($maxRatioPercent / 100.0), 2);
        $credit = min($baseCredit, $maxAllowedCredit);

        return [
            'order' => $activeTabpOrder,
            'credit_ratio' => $ratio,
            'credit_amount' => $credit, // base credit before capping to a specific target product
            'paid_price' => $paidPrice,
            'time_ratio' => $timeRatio,
            'traffic_ratio' => $trafficRatio,
        ];
    }

    /**
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $rawProductId = $request->getQueryParams()['product_id'] ?? null;
        $product_id = $rawProductId !== null ? $this->antiXss->xss_clean($rawProductId) : null;
        $redir = Cookie::get('redir');

        if ($redir !== '') {
            Cookie::set(['redir' => ''], time() - 1);
        }

        if ($product_id === null || $product_id === '') {
            return $response->withRedirect('/user/product');
        }

        $product = (new Product())->where('id', $product_id)->first();
        if ($product === null) {
            return $response->withRedirect('/user/product');
        }

        $product->type_text = $product->type();
        $product->content = json_decode($product->content);

        $is_upgrade = (int) ($request->getQueryParams()['upgrade'] ?? 0) === 1;
        $upgrade_credit = 0.0;
        $upgrade_payable = (float) $product->price;

        if ($is_upgrade) {
            $enableTabpUpgrade = (bool) Config::obtain('enable_tabp_upgrade');
            $activeTabpInfo = $enableTabpUpgrade ? self::getActiveTabpUpgradeInfo($this->user) : null;
            if (! $enableTabpUpgrade || $product->type !== 'tabp' || $activeTabpInfo === null || (float) $product->price <= (float) $activeTabpInfo['order']->price || $product->id === $activeTabpInfo['order']->product_id) {
                return $response->withRedirect('/user/product');
            }

            $hasPendingUpgrade = Order::where('user_id', $this->user->id)
                ->where('status', 'pending_payment')
                ->where('product_content', 'like', '%"is_upgrade":true%')
                ->first();

            if ($hasPendingUpgrade !== null) {
                return $response->withRedirect('/user/order/' . $hasPendingUpgrade->id . '/view');
            }

            $credit = min($activeTabpInfo['credit_amount'], (float) $product->price);
            $upgrade_credit = $credit;
            $upgrade_payable = max(0.0, round((float) $product->price - $credit, 2));
        }

        return $response->write(
            $this->view()
                ->assign('product', $product)
                ->assign('is_upgrade', $is_upgrade)
                ->assign('upgrade_credit', $upgrade_credit)
                ->assign('upgrade_payable', $upgrade_payable)
                ->fetch('user/order/create.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function detail(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $this->antiXss->xss_clean($args['id']);

        $order = (new Order())->where('user_id', $this->user->id)->where('id', $id)->first();

        if ($order === null) {
            return $response->withRedirect('/user/order');
        }

        $order->product_type_text = $order->productType();
        $order->raw_status = $order->status;
        $order->status = $order->status();
        $order->auto_renew_text = $order->autoRenew();
        $order->create_time = Tools::toDateTime($order->create_time);
        $order->update_time = Tools::toDateTime($order->update_time);
        $order->content = json_decode($order->product_content);

        if ($order->product_id === 0 || $order->product_type === 'topup' || $order->product_name === '余额充值') {
            $order->product_name = I18n::trans('user.topup');
        }

        $invoice = (new Invoice())->where('order_id', $id)->first();
        if ($invoice !== null) {
            $invoice->status = $invoice->status();
            $invoice->create_time = Tools::toDateTime($invoice->create_time);
            $invoice->update_time = Tools::toDateTime($invoice->update_time);
            $invoice->pay_time = Tools::toDateTime($invoice->pay_time);
            $invoice->content = json_decode($invoice->content);
            if (is_array($invoice->content)) {
                foreach ($invoice->content as $item) {
                    if (isset($item->name)) {
                        if ($item->name === '余额充值') {
                            $item->name = I18n::trans('user.topup');
                        } elseif ($item->name === '退款至账户余额') {
                            $item->name = I18n::trans('user.refund_to_balance');
                        }
                    }
                }
            }
        }

        return $response->write(
            $this->view()
                ->assign('order', $order)
                ->assign('invoice', $invoice)
                ->fetch('user/order/view.tpl')
        );
    }

    public function toggleRenew(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $this->antiXss->xss_clean($args['id']);
        $requested = $request->getParam('auto_renew');
        if ($requested !== null && $requested !== '' && ! in_array($requested, [0, 1, '0', '1', false, true], true)) {
            return ResponseHelper::error($response, I18n::trans('user.invalid_auto_renew_status'));
        }

        return DB::transaction(function () use ($response, $id, $requested): ResponseInterface {
            $order = Order::where('user_id', $this->user->id)->where('id', $id)->lockForUpdate()->first();

            if ($order === null || ! in_array($order->product_type, ['tabp', 'time'], true)) {
                return ResponseHelper::error($response, I18n::trans('user.order_not_found_or_not_supported'));
            }

            $enabled = ($requested !== null && $requested !== '')
                ? in_array($requested, [1, '1', true], true)
                : ((int) $order->auto_renew === 0);

            if ($enabled) {
                if ($order->status !== 'activated') {
                    return ResponseHelper::error($response, I18n::trans('user.only_activated_order_can_enable_auto_renew'));
                }
                $hasPending = Order::where('user_id', $this->user->id)
                    ->where('product_type', $order->product_type)
                    ->where('status', 'pending_activation')
                    ->exists();
                if ($hasPending) {
                    return ResponseHelper::error($response, I18n::trans('user.pending_activation_order_exists'));
                }
            } else {
                if (! in_array($order->status, ['activated', 'expired'], true)) {
                    return ResponseHelper::error($response, I18n::trans('user.order_cannot_disable_auto_renew'));
                }
            }

            if ($enabled) {
                $order->auto_renew = 1;
                $order->save();
            } else {
                Order::where('user_id', $this->user->id)
                    ->where('product_type', $order->product_type)
                    ->whereIn('status', ['activated', 'pending_activation', 'expired'])
                    ->whereIn('auto_renew', [1, 2])
                    ->update(['auto_renew' => 0]);
                $order->auto_renew = 0;
                $order->save();
            }

            $statusText = $order->autoRenew();
            $btnText = $enabled ? I18n::trans('user.disable_auto_renew') : I18n::trans('user.enable_auto_renew');

            return ResponseHelper::successWithData($response, $enabled ? I18n::trans('user.auto_renew_enabled_msg') : I18n::trans('user.auto_renew_disabled_msg'), [
                'auto-renew-badge' => $statusText,
                'toggle-renew-btn' => $btnText,
            ]);
        });
    }

    public function process(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return match ($request->getParam('type')) {
            'product' => $this->product($request, $response, $args),
            'topup' => $this->topup($request, $response, $args),
            default => $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.unknown_order_type', I18n::getCurrentLocale($this->user)),
            ]),
        };
    }

    public function product(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $coupon_raw = (string) $this->antiXss->xss_clean($request->getParam('coupon'));
        $product_id = (int) $this->antiXss->xss_clean($request->getParam('product_id'));
        $auto_renew = $request->getParam('auto_renew');
        $auto_renew = ($auto_renew === 'true' || $auto_renew === '1' || $auto_renew === 1 || $auto_renew === true) ? 1 : 0;
        $is_upgrade = (int) ($request->getParam('is_upgrade') ?? 0) === 1;
        $locale = I18n::getCurrentLocale($this->user);

        if ($this->user->is_shadow_banned) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.product_out_of_stock', $locale),
            ]);
        }

        try {
            $invoice = DB::transaction(function () use (
                $product_id,
                $coupon_raw,
                $auto_renew,
                $is_upgrade,
                $locale
            ): Invoice {
                $user = User::where('id', $this->user->id)->lockForUpdate()->first();
                if ($user === null || $user->is_shadow_banned) {
                    throw new Exception(I18n::trans('user.product_out_of_stock', $locale));
                }

                // Rapid duplicate click / order deduplication (within 5 seconds)
                $recentPendingOrder = Order::where('user_id', $user->id)
                    ->where('product_id', $product_id)
                    ->where('status', 'pending_payment')
                    ->where('create_time', '>=', time() - 5)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($recentPendingOrder !== null) {
                    $recentInvoice = Invoice::where('order_id', $recentPendingOrder->id)->first();
                    if ($recentInvoice !== null) {
                        return $recentInvoice;
                    }
                }

                $product = Product::where('id', $product_id)->first();
                if ($product === null || $product->stock === 0 || (int) $product->status !== 1) {
                    throw new Exception(I18n::trans('user.product_out_of_stock', $locale));
                }

                $buy_price = (float) $product->price;
                $coupon = null;
                $discount = 0.0;
                $activeTabpOrder = null;
                $credit = 0.0;

                if ($is_upgrade) {
                    if (! Config::obtain('enable_tabp_upgrade')) {
                        throw new Exception(I18n::trans('user.package_upgrade_disabled', $locale));
                    }

                    if ($product->type !== 'tabp') {
                        throw new Exception(I18n::trans('user.non_tabp_package_cannot_upgrade', $locale));
                    }

                    $activeTabpInfo = self::getActiveTabpUpgradeInfo($user);
                    if ($activeTabpInfo === null) {
                        throw new Exception(I18n::trans('user.no_valid_package_to_upgrade', $locale));
                    }

                    $activeTabpOrder = $activeTabpInfo['order'];
                    if ((float) $product->price <= (float) $activeTabpOrder->price || $product->id === $activeTabpOrder->product_id) {
                        throw new Exception(I18n::trans('user.new_package_price_must_be_higher', $locale));
                    }

                    $hasPendingUpgrade = Order::where('user_id', $user->id)
                        ->where('status', 'pending_payment')
                        ->where('product_content', 'like', '%"is_upgrade":true%')
                        ->exists();
                    if ($hasPendingUpgrade) {
                        throw new Exception(I18n::trans('user.pending_upgrade_order_exists', $locale));
                    }

                    $coupon_raw = '';
                    $coupon = null;
                    $discount = 0.0;
                    $credit = min($activeTabpInfo['credit_amount'], (float) $product->price);
                    $buy_price = max(0.0, round((float) $product->price - $credit, 2));
                } else {
                    if ($coupon_raw !== '') {
                        $coupon = UserCoupon::where('code', $coupon_raw)->lockForUpdate()->first();

                        if ($coupon === null || ($coupon->expire_time !== 0 && $coupon->expire_time < time())) {
                            throw new Exception(I18n::trans('user.coupon_not_exist_or_expired', $locale));
                        }

                        if (! $coupon->allowsUser($user)) {
                            throw new Exception(I18n::trans('user.coupon_not_applicable', $locale));
                        }

                        $coupon_limit = json_decode($coupon->limit);

                        if ($coupon_limit->disabled) {
                            throw new Exception(I18n::trans('user.coupon_disabled', $locale));
                        }

                        if ($coupon_limit->product_id !== '' && ! in_array((string) $product_id, explode(',', (string) $coupon_limit->product_id), true)) {
                            throw new Exception(I18n::trans('user.coupon_not_applicable', $locale));
                        }

                        $coupon_use_limit = (int) ($coupon_limit->use_time ?? 0);

                        if ($coupon_use_limit > 0) {
                            $user_use_count = Order::where('user_id', $user->id)
                                ->where('coupon', $coupon->code)
                                ->where('status', '!=', 'cancelled')
                                ->count();
                            if ($user_use_count >= $coupon_use_limit) {
                                throw new Exception(I18n::trans('user.coupon_limit_reached', $locale));
                            }
                        }

                        $coupon_total_use_limit = property_exists($coupon_limit, 'total_use_time') ? (int) $coupon_limit->total_use_time : -1;

                        if ($coupon_total_use_limit > 0 && $coupon->use_count >= $coupon_total_use_limit) {
                            throw new Exception(I18n::trans('user.coupon_limit_reached', $locale));
                        }

                        $content = json_decode($coupon->content);

                        if ($content->type === 'percentage') {
                            $discount = round((float) $product->price * (float) $content->value / 100, 2);
                        } else {
                            $discount = (float) $content->value;
                        }

                        $buy_price = max(0.0, round((float) $product->price - $discount, 2));
                    }
                }

                $product_limit = json_decode($product->limit);

                if (isset($product_limit->class_required) && $product_limit->class_required !== '' && $user->class < (int) $product_limit->class_required) {
                    throw new Exception(I18n::trans('user.insufficient_account_class', $locale));
                }

                if (isset($product_limit->node_group_required) && $product_limit->node_group_required !== ''
                    && $user->node_group !== (int) $product_limit->node_group_required) {
                    throw new Exception(I18n::trans('user.user_group_cannot_buy', $locale));
                }

                if (isset($product_limit->new_user_required) && (int) $product_limit->new_user_required !== 0) {
                    $order_count = Order::where('user_id', $user->id)
                        ->where('status', '!=', 'cancelled')
                        ->where('product_type', '!=', 'topup')
                        ->count();
                    if ($order_count > 0) {
                        throw new Exception(I18n::trans('user.product_new_user_only', $locale));
                    }
                }

                if ($product->stock > 0) {
                    $affected = Product::where('id', $product->id)->where('stock', '>', 0)->decrement('stock', 1);
                    if ($affected === 0) {
                        throw new Exception(I18n::trans('user.product_out_of_stock', $locale));
                    }
                }

                Product::where('id', $product->id)->increment('sale_count', 1);

                $order = new Order();
                $order->user_id = $user->id;
                $order->product_id = $product->id;
                $order->product_type = $product->type;
                $order->product_name = $product->name;
                if ($is_upgrade) {
                    $orderContent = json_decode($product->content, true) ?: [];
                    $orderContent['is_upgrade'] = true;
                    $orderContent['supersedes_order_id'] = $activeTabpOrder->id;
                    $orderContent['upgrade_credit'] = $credit;
                    $order->product_content = json_encode($orderContent);
                    $order->coupon = '';
                } else {
                    $order->product_content = $product->content;
                    $order->coupon = $coupon_raw;
                }
                $order->price = $buy_price;
                $order->status = $buy_price <= 0 ? 'pending_activation' : 'pending_payment';
                $order->auto_renew = in_array($product->type, ['tabp', 'time'], true) ? $auto_renew : 0;
                $order->create_time = time();
                $order->update_time = time();
                $order->save();

                $invoice_content = [];
                if ($is_upgrade) {
                    $invoice_content = [
                        [
                            'content_id' => 0,
                            'name' => $product->name,
                            'price' => $product->price,
                        ],
                        [
                            'content_id' => 1,
                            'name' => I18n::trans('user.upgrade_credit_item', $locale, ['id' => $activeTabpOrder->id]),
                            'price' => '-' . $credit,
                        ],
                    ];
                } else {
                    $invoice_content[] = [
                        'content_id' => 0,
                        'name' => $product->name,
                        'price' => $product->price,
                    ];

                    if ($coupon_raw !== '') {
                        $invoice_content[] = [
                            'content_id' => 1,
                            'name' => I18n::trans('user.coupon', $locale) . ' ' . $coupon_raw,
                            'price' => '-' . $discount,
                        ];
                    }
                }

                $invoice = new Invoice();
                $invoice->user_id = $user->id;
                $invoice->order_id = $order->id;
                $invoice->content = json_encode($invoice_content);
                $invoice->price = $buy_price;
                $invoice->status = $buy_price <= 0 ? 'paid_gateway' : 'unpaid';
                $invoice->create_time = time();
                $invoice->update_time = time();
                $invoice->pay_time = $buy_price <= 0 ? time() : 0;
                $invoice->type = 'product';
                $invoice->save();

                if (! $is_upgrade && $coupon_raw !== '' && $coupon !== null) {
                    UserCoupon::where('id', $coupon->id)->increment('use_count', 1);
                }

                if ($buy_price <= 0) {
                    Cron::activateOrder($order);
                }

                return $invoice;
            });
        } catch (Exception $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $e->getMessage(),
            ]);
        }

        return $response->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
    }

    public function topup(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);
        $amount = $this->antiXss->xss_clean($request->getParam('amount'));
        $amount = is_numeric($amount) ? round((float) $amount, 2) : null;

        if ($amount === null || $amount < 0.01 || $amount > 1000000) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.invalid_topup_amount', $locale),
            ]);
        }

        if ($this->user->is_shadow_banned) {
            return $response->withJson([
                'ret' => 0,
                'msg' => I18n::trans('user.payment_failed_try_again', $locale),
            ]);
        }

        try {
            $invoice = DB::transaction(function () use ($amount, $locale): Invoice {
                $user = User::where('id', $this->user->id)->lockForUpdate()->first();
                if ($user === null || $user->is_shadow_banned) {
                    throw new Exception(I18n::trans('user.payment_failed_try_again', $locale));
                }

                // Check rapid duplicate topup order (within 5 seconds)
                $recentPendingTopup = Order::where('user_id', $user->id)
                    ->where('product_type', 'topup')
                    ->where('price', $amount)
                    ->where('status', 'pending_payment')
                    ->where('create_time', '>=', time() - 5)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($recentPendingTopup !== null) {
                    $recentInvoice = Invoice::where('order_id', $recentPendingTopup->id)->first();
                    if ($recentInvoice !== null) {
                        return $recentInvoice;
                    }
                }

                $order = new Order();
                $order->user_id = $user->id;
                $order->product_id = 0;
                $order->product_type = 'topup';
                $order->product_name = I18n::trans('user.topup', $locale);
                $order->product_content = json_encode(['amount' => $amount]);
                $order->coupon = '';
                $order->price = $amount;
                $order->status = 'pending_payment';
                $order->create_time = time();
                $order->update_time = time();
                $order->save();

                $invoice_content = [
                    [
                        'content_id' => 0,
                        'name' => I18n::trans('user.topup', $locale),
                        'price' => $amount,
                    ],
                ];

                $invoice = new Invoice();
                $invoice->user_id = $user->id;
                $invoice->order_id = $order->id;
                $invoice->content = json_encode($invoice_content);
                $invoice->price = $amount;
                $invoice->status = 'unpaid';
                $invoice->create_time = time();
                $invoice->update_time = time();
                $invoice->pay_time = 0;
                $invoice->type = 'topup';
                $invoice->save();

                return $invoice;
            });
        } catch (Exception $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => $e->getMessage(),
            ]);
        }

        return $response->withHeader('HX-Redirect', '/user/invoice/' . $invoice->id . '/view');
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $orders = DataTable::paginate(
            (new Order())->newQuery()->where('user_id', $this->user->id),
            $request,
            ['id', 'product_name', 'product_type', 'status'],
            ['id', 'product_id', 'product_type', 'product_name', 'coupon', 'price', 'status', 'create_time', 'update_time']
        );
        $invoiceIds = (new Invoice())->whereIn('order_id', $orders['data']->pluck('id'))
            ->pluck('id', 'order_id');

        foreach ($orders['data'] as $order) {
            $order->op = '<a class="btn btn-primary" href="/user/order/' . $order->id . '/view">' . I18n::trans('user.view') . '</a>';

            if ($order->status === 'pending_payment') {
                $invoice_id = $invoiceIds[$order->id] ?? 0;
                $order->op .= '
                <a class="btn btn-red" href="/user/invoice/' . $invoice_id . '/view">' . I18n::trans('user.pay') . '</a>';
            }

            if ($order->product_id === 0 || $order->product_type === 'topup' || $order->product_name === '余额充值') {
                $order->product_name = I18n::trans('user.topup');
            }

            $order->product_type = $order->productType();
            $order->status = $order->status();
            $order->create_time = Tools::toDateTime($order->create_time);
            $order->update_time = Tools::toDateTime($order->update_time);
        }

        $orders['orders'] = $orders['data'];
        unset($orders['data']);
        return $response->withJson($orders);
    }
}
