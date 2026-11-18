<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Ann;
use App\Models\Config;
use App\Models\DetectLog;
use App\Models\EmailQueue;
use App\Models\HourlyUsage;
use App\Models\Invoice;
use App\Models\InviteCode;
use App\Models\Link;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Models\Order;
use App\Models\Paylist;
use App\Models\Product;
use App\Models\StreamMedia;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\UserMoneyLog;
use App\Services\DB;
use App\Utils\Tools;
use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;
use function date;
use function in_array;
use function json_decode;
use function json_encode;
use function max;
use function str_replace;
use function strtotime;
use function time;
use const PHP_EOL;

final class Cron
{
    public static function cleanDb(): void
    {
        try {
            do {
                $deleted = (new SubscribeLog())->where(
                    'request_time',
                    '<',
                    time() - 86400 * Config::obtain('subscribe_log_retention_days')
                )->limit(2000)->delete();
            } while ($deleted > 0);
        } catch (Throwable $e) {
            echo 'Clean SubscribeLog failed: ' . $e->getMessage() . PHP_EOL;
        }

        try {
            do {
                $deleted = (new HourlyUsage())->where(
                    'date',
                    '<',
                    date('Y-m-d', time() - 86400 * Config::obtain('traffic_log_retention_days'))
                )->limit(2000)->delete();
            } while ($deleted > 0);
        } catch (Throwable $e) {
            echo 'Clean HourlyUsage failed: ' . $e->getMessage() . PHP_EOL;
        }

        try {
            do {
                $deleted = (new DetectLog())->where('datetime', '<', time() - 86400 * 3)->limit(2000)->delete();
            } while ($deleted > 0);
        } catch (Throwable $e) {
            echo 'Clean DetectLog failed: ' . $e->getMessage() . PHP_EOL;
        }

        try {
            do {
                $deleted = (new EmailQueue())->where('time', '<', time() - 86400 * 30)->limit(2000)->delete();
            } while ($deleted > 0);
        } catch (Throwable $e) {
            echo 'Clean EmailQueue failed: ' . $e->getMessage() . PHP_EOL;
        }

        try {
            do {
                $deleted = (new OnlineLog())->where('last_time', '<', time() - 86400)->limit(2000)->delete();
            } while ($deleted > 0);
        } catch (Throwable $e) {
            echo 'Clean OnlineLog failed: ' . $e->getMessage() . PHP_EOL;
        }

        try {
            do {
                $deleted = (new StreamMedia())->where('created_at', '<', time() - 86400 * 3)->limit(2000)->delete();
            } while ($deleted > 0);
        } catch (Throwable $e) {
            echo 'Clean StreamMedia failed: ' . $e->getMessage() . PHP_EOL;
        }

        try {
            self::cleanCancelledOrdersAndInvoices();
        } catch (Throwable $e) {
            echo 'Clean Cancelled Orders and Invoices failed: ' . $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' 数据库清理完成' . PHP_EOL;
    }

    public static function cleanCancelledOrdersAndInvoices(): void
    {
        $retentionDays = (int) Config::obtain('cancelled_order_retention_days');
        if ($retentionDays <= 0) {
            return;
        }

        $timeLimit = time() - 86400 * $retentionDays;

        // 1. Clean cancelled invoices and their associated unpaid paylists
        do {
            $invoices = (new Invoice())
                ->where('status', 'cancelled')
                ->where(static function ($query) use ($timeLimit): void {
                    $query->where('update_time', '<', $timeLimit)
                        ->where('update_time', '>', 0)
                        ->orWhere(static function ($subQuery) use ($timeLimit): void {
                            $subQuery->where('update_time', '<=', 0)
                                ->where('create_time', '<', $timeLimit);
                        });
                })
                ->whereNotExists(static function ($subQuery): void {
                    $subQuery->selectRaw(1)
                        ->from('order')
                        ->whereColumn('order.id', 'invoice.order_id')
                        ->where('order.status', '!=', 'cancelled');
                })
                ->limit(500)
                ->get();

            if ($invoices->isEmpty()) {
                break;
            }

            $invoiceIds = $invoices->pluck('id')->all();

            (new Paylist())
                ->whereIn('invoice_id', $invoiceIds)
                ->where('status', 0)
                ->delete();

            (new Invoice())
                ->whereIn('id', $invoiceIds)
                ->where('status', 'cancelled')
                ->delete();
        } while (count($invoiceIds) >= 500);

        // 2. Clean cancelled orders (ensuring no non-cancelled invoice exists for the order)
        do {
            $orders = (new Order())
                ->where('status', 'cancelled')
                ->where(static function ($query) use ($timeLimit): void {
                    $query->where('update_time', '<', $timeLimit)
                        ->where('update_time', '>', 0)
                        ->orWhere(static function ($subQuery) use ($timeLimit): void {
                            $subQuery->where('update_time', '<=', 0)
                                ->where('create_time', '<', $timeLimit);
                        });
                })
                ->whereNotExists(static function ($subQuery): void {
                    $subQuery->selectRaw(1)
                        ->from('invoice')
                        ->whereColumn('invoice.order_id', 'order.id')
                        ->where('invoice.status', '!=', 'cancelled');
                })
                ->limit(500)
                ->get();

            if ($orders->isEmpty()) {
                break;
            }

            $orderIds = $orders->pluck('id')->all();

            (new Order())
                ->whereIn('id', $orderIds)
                ->where('status', 'cancelled')
                ->delete();
        } while (count($orderIds) >= 500);
    }

    public static function detectInactiveUser(): void
    {
        $checkin_days = Config::obtain('detect_inactive_user_checkin_days');
        $login_days = Config::obtain('detect_inactive_user_login_days');
        $use_days = Config::obtain('detect_inactive_user_use_days');

        (new User())->where('is_admin', 0)
            ->where('is_inactive', 0)
            ->where('last_check_in_time', '<', time() - 86400 * $checkin_days)
            ->where('last_login_time', '<', time() - 86400 * $login_days)
            ->where('last_use_time', '<', time() - 86400 * $use_days)
            ->update(['is_inactive' => 1]);

        (new User())->where('is_admin', 0)
            ->where('is_inactive', 1)
            ->where('last_check_in_time', '>', time() - 86400 * $checkin_days)
            ->where('last_login_time', '>', time() - 86400 * $login_days)
            ->where('last_use_time', '>', time() - 86400 * $use_days)
            ->update(['is_inactive' => 0]);

        echo Tools::toDateTime(time()) .
            ' 检测到 ' . (new User())->where('is_inactive', 1)->count() . ' 个账户处于闲置状态' . PHP_EOL;
    }

    public static function detectNodeOffline(): void
    {
        $nodes = (new Node())->where('type', 1)->get();

        foreach ($nodes as $node) {
            try {
                if ($node->getNodeOnlineStatus() >= 0 && $node->online === 1) {
                    continue;
                }

                if ($node->getNodeOnlineStatus() === -1 && $node->online === 1) {
                    echo 'Send Node Offline Email to admin users' . PHP_EOL;

                    try {
                        Notification::notifyAdmin(
                            $_ENV['appName'] . '-系统警告',
                            '管理员你好，系统发现节点 ' . $node->name . ' 掉线了，请你及时处理。'
                        );
                    } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }

                    if (Config::obtain('im_bot_group_notify_node_offline')) {
                        try {
                            Notification::notifyUserGroup(
                                str_replace(
                                    '%node_name%',
                                    $node->name,
                                    I18n::trans('bot.node_offline', $_ENV['locale'])
                                ),
                            );
                        } catch (TelegramSDKException | GuzzleException $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }

                    $node->online = 0;
                    $node->save();

                    continue;
                }

                if ($node->getNodeOnlineStatus() === 1 && $node->online === 0) {
                    echo 'Send Node Online Email to admin user' . PHP_EOL;

                    try {
                        Notification::notifyAdmin(
                            $_ENV['appName'] . '-系统提示',
                            '管理员你好，系统发现节点 ' . $node->name . ' 恢复上线了。'
                        );
                    } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }

                    if (Config::obtain('im_bot_group_notify_node_online')) {
                        try {
                            Notification::notifyUserGroup(
                                str_replace(
                                    '%node_name%',
                                    $node->name,
                                    I18n::trans('bot.node_online', $_ENV['locale'])
                                ),
                            );
                        } catch (TelegramSDKException | GuzzleException $e) {
                            echo $e->getMessage() . PHP_EOL;
                        }
                    }

                    $node->online = 1;
                    $node->save();
                }
            } catch (Throwable $e) {
                echo 'Cron detectNodeOffline failed for node #' . $node->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 节点离线检测完成' . PHP_EOL;
    }

    public static function expirePaidUserAccount(): void
    {
        $paidUsers = (new User())->where('class', '>', 0)
            ->where('class_expire', '<', date('Y-m-d H:i:s'))
            ->get();

        $expiredUserIds = [];

        foreach ($paidUsers as $user) {
            try {
                $text = '你好，系统发现你的账号等级已经过期了。';
                $reset_traffic = (int) Config::obtain('class_expire_reset_traffic');

                if ($reset_traffic >= 0) {
                    $user->transfer_enable = Tools::gbToB($reset_traffic);
                    $text .= '流量已经被重置为' . $reset_traffic . 'GB。';
                }

                try {
                    Notification::notifyUser($user, $_ENV['appName'] . '-你的账号等级已经过期了', $text);
                } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }

                $user->class = 0;
                $user->node_group = 0;
                $user->node_speedlimit = (int) Config::obtain('reg_speed_limit');
                $user->node_iplimit = (int) Config::obtain('reg_ip_limit');
                $user->save();
                $expiredUserIds[] = $user->id;
            } catch (Throwable $e) {
                echo 'Cron expirePaidUserAccount failed for user #' . $user->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        if (! empty($expiredUserIds)) {
            Order::whereIn('user_id', $expiredUserIds)
                ->whereIn('product_type', ['time', 'tabp'])
                ->where('status', 'activated')
                ->update(['status' => 'expired', 'auto_renew' => 0, 'update_time' => time()]);
        }

        echo Tools::toDateTime(time()) . ' 付费用户过期检测完成' . PHP_EOL;
    }

    public static function processEmailQueue(): void
    {
        if ((new EmailQueue())->count() === 0) {
            echo Tools::toDateTime(time()) . ' 邮件队列为空' . PHP_EOL;
        } else {
            //记录当前时间戳
            $timestamp = time();
            //邮件队列处理
            while (true) {
                if (time() - $timestamp > 299) {
                    echo Tools::toDateTime(time()) . '邮件队列处理超时，已跳过' . PHP_EOL;
                    break;
                }

                $email_queue = DB::transaction(static function (): ?EmailQueue {
                    $email = (new EmailQueue())->where('next_attempt_at', '<=', time())
                        ->orderBy('id')->lock('FOR UPDATE SKIP LOCKED')->first();
                    if ($email === null) {
                        return null;
                    }
                    $email->attempts++;
                    $email->next_attempt_at = time() + 600;
                    $email->save();
                    return $email;
                });

                if ($email_queue === null) {
                    break;
                }

                echo '发送邮件至 ' . $email_queue->to_email . PHP_EOL;

                if (Tools::isEmail($email_queue->to_email)) {
                    try {
                        Mail::send(
                            $email_queue->to_email,
                            $email_queue->subject,
                            $email_queue->template,
                            json_decode($email_queue->array)
                        );
                        $email_queue->delete();
                    } catch (Throwable $e) {
                        $email_queue->last_error = mb_substr($e->getMessage(), 0, 1000);
                        $email_queue->next_attempt_at = time() + min(86400, 60 * (2 ** min(10, $email_queue->attempts)));
                        $email_queue->save();
                        echo $e->getMessage() . PHP_EOL;
                    }
                } else {
                    echo $email_queue->to_email . ' 邮箱格式错误，已跳过' . PHP_EOL;
                    $email_queue->delete();
                }
            }

            echo Tools::toDateTime(time()) . ' 邮件队列处理完成' . PHP_EOL;
        }
    }

    public static function activateOrder(int|Order $order): bool
    {
        $orderId = $order instanceof Order ? $order->id : $order;

        return (bool) DB::transaction(static function () use ($orderId): bool {
            $order = Order::where('id', $orderId)->lockForUpdate()->first();
            if ($order === null || $order->status !== 'pending_activation') {
                return false;
            }

            $user = User::where('id', $order->user_id)->lockForUpdate()->first();
            if ($user === null) {
                return false;
            }

            $content = json_decode($order->product_content);

            switch ($order->product_type) {
                case 'topup':
                    $money_before = (float) $user->money;
                    $user->money += $content->amount;
                    $user->save();
                    $order->status = 'activated';
                    $order->update_time = time();
                    $order->save();
                    (new UserMoneyLog())->add(
                        $user->id,
                        $money_before,
                        (float) $user->money,
                        $content->amount,
                        "充值订单 #{$order->id}"
                    );
                    echo "充值订单 #{$order->id} 已激活。\n";
                    return true;

                case 'bandwidth':
                    $user->transfer_enable += Tools::gbToB($content->bandwidth);
                    $user->save();
                    $order->status = 'activated';
                    $order->update_time = time();
                    $order->save();
                    echo "流量包订单 #{$order->id} 已激活。\n";
                    return true;

                case 'time':
                    $days = max((int) ($content->class_time ?? 0), (int) ($content->time ?? 0));
                    if ($days <= 0) {
                        $days = 30;
                    }
                    $currentExpireTimestamp = strtotime($user->class_expire ?? '');
                    if ($user->class === (int) $content->class && $currentExpireTimestamp > time()) {
                        $base = new DateTime($user->class_expire);
                    } else {
                        $base = new DateTime();
                    }
                    $user->class = (int) $content->class;
                    $user->class_expire = $base->modify('+' . $days . ' days')->format('Y-m-d H:i:s');
                    $user->node_group = $content->node_group;
                    $user->node_speedlimit = $content->speed_limit;
                    $user->node_iplimit = $content->ip_limit;
                    $user->save();
                    $order->status = 'activated';
                    $order->update_time = time();
                    $order->save();

                    Order::where('user_id', $user->id)
                        ->where('product_type', 'time')
                        ->where('status', 'activated')
                        ->where('id', '!=', $order->id)
                        ->update(['status' => 'expired', 'update_time' => time()]);

                    echo "时间包订单 #{$order->id} 已激活。\n";
                    return true;

                case 'tabp':
                    if ($content->is_upgrade ?? false) {
                        $targetOldOrderId = (int) ($content->supersedes_order_id ?? 0);
                        $activeTabpOrder = Order::where('user_id', $user->id)
                            ->where('product_type', 'tabp')
                            ->where('status', 'activated')
                            ->where('id', '!=', $order->id)
                            ->first();

                        if ($activeTabpOrder !== null) {
                            if ($activeTabpOrder->id === $targetOldOrderId) {
                                $activeTabpOrder->status = 'superseded';
                                $activeTabpOrder->auto_renew = 0;
                                $activeTabpOrder->update_time = time();
                                $activeTabpOrder->save();
                            } else {
                                $activeContent = json_decode($activeTabpOrder->product_content);
                                $expireTime = $activeTabpOrder->update_time + ((int) ($activeContent->time ?? $activeContent->class_time ?? 0)) * 86400;
                                if ($expireTime > time()) {
                                    return false;
                                }
                                $activeTabpOrder->status = 'expired';
                                $activeTabpOrder->update_time = time();
                                $activeTabpOrder->save();
                            }
                        }
                    } else {
                        // Check if user already has an active TABP order that has not expired
                        $activeTabpOrder = Order::where('user_id', $user->id)
                            ->where('product_type', 'tabp')
                            ->where('status', 'activated')
                            ->where('id', '!=', $order->id)
                            ->orderBy('id', 'desc')
                            ->first();
                        if ($activeTabpOrder !== null) {
                            $activeContent = json_decode($activeTabpOrder->product_content);
                            $duration = (int) ($activeContent->time ?? $activeContent->class_time ?? 0);
                            $expireTime = $activeTabpOrder->update_time + $duration * 86400;
                            if ($expireTime > time()) {
                                // Current active TABP is still running, keep this order in pending_activation queue
                                return false;
                            }
                            // Expire all old activated TABP orders
                            Order::where('user_id', $user->id)
                                ->where('product_type', 'tabp')
                                ->where('status', 'activated')
                                ->where('id', '!=', $order->id)
                                ->update(['status' => 'expired', 'update_time' => time()]);
                        }
                    }

                    $user->u = 0;
                    $user->d = 0;
                    $user->transfer_today = 0;
                    $user->transfer_enable = Tools::gbToB($content->bandwidth);
                    $user->class = $content->class;
                    $user->class_expire = (new DateTime())->modify('+' . $content->class_time . ' days')->format('Y-m-d H:i:s');
                    $user->node_group = $content->node_group;
                    $user->node_speedlimit = $content->speed_limit;
                    $user->node_iplimit = $content->ip_limit;
                    $user->save();
                    $order->status = 'activated';
                    $order->update_time = time();
                    $order->save();
                    echo "TABP订单 #{$order->id} 已激活。\n";
                    return true;

                default:
                    return false;
            }
        });
    }

    public static function processTabpOrderActivation(): void
    {
        $activated_orders = Order::where('status', 'activated')
            ->where('product_type', 'tabp')
            ->get();

        foreach ($activated_orders as $order) {
            try {
                $content = json_decode($order->product_content);
                $duration = (int) ($content->time ?? $content->class_time ?? 0);

                if ($order->update_time + $duration * 86400 < time()) {
                    $order->status = 'expired';
                    $order->update_time = time();
                    $order->save();
                    echo "TABP订单 #{$order->id} 已过期。\n";
                }
            } catch (Throwable $e) {
                echo 'Cron processTabpOrderActivation check failed for order #' . $order->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        $pending_order_ids = Order::where('status', 'pending_activation')
            ->where('product_type', 'tabp')
            ->orderBy('id')
            ->pluck('id');

        foreach ($pending_order_ids as $orderId) {
            try {
                self::activateOrder($orderId);
            } catch (Throwable $e) {
                echo 'Cron processTabpOrderActivation failed for order #' . $orderId . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' TABP订单激活处理完成' . PHP_EOL;
    }

    public static function processBandwidthOrderActivation(): void
    {
        $orderIds = Order::where('status', 'pending_activation')
            ->where('product_type', 'bandwidth')
            ->orderBy('id')
            ->pluck('id');

        foreach ($orderIds as $orderId) {
            try {
                self::activateOrder($orderId);
            } catch (Throwable $e) {
                echo 'Cron processBandwidthOrderActivation failed for order #' . $orderId . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 流量包订单激活处理完成' . PHP_EOL;
    }

    /**
     * @throws Exception
     */
    public static function processTimeOrderActivation(): void
    {
        $activeTimeOrders = Order::where('status', 'activated')
            ->where('product_type', 'time')
            ->get();
        $userIds = $activeTimeOrders->pluck('user_id')->unique()->filter()->values();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($activeTimeOrders as $activeOrder) {
            try {
                $user = $users->get($activeOrder->user_id);
                if ($user === null || strtotime($user->class_expire ?? '') <= time()) {
                    $activeOrder->status = 'expired';
                    $activeOrder->update_time = time();
                    $activeOrder->save();
                    echo "时间包订单 #{$activeOrder->id} 已过期。\n";
                }
            } catch (Throwable $e) {
                echo 'Cron processTimeOrderActivation check failed for order #' . $activeOrder->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        $orderIds = Order::where('status', 'pending_activation')
            ->where('product_type', 'time')
            ->orderBy('id')
            ->pluck('id');

        foreach ($orderIds as $orderId) {
            try {
                self::activateOrder($orderId);
            } catch (Throwable $e) {
                echo 'Cron processTimeOrderActivation failed for order #' . $orderId . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 时间包订单激活处理完成' . PHP_EOL;
    }

    /**
     * @throws Exception
     */
    public static function processTopupOrderActivation(): void
    {
        // 获取等待激活的充值订单，允许同时处理多个充值订单
        $orderIds = (new Order())->where('status', 'pending_activation')
            ->where('product_type', 'topup')
            ->orderBy('id')
            ->pluck('id');

        foreach ($orderIds as $orderId) {
            try {
                self::activateOrder($orderId);
            } catch (Throwable $e) {
                echo 'Cron processTopupOrderActivation failed for order #' . $orderId . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 充值订单激活处理完成' . PHP_EOL;
    }

    public static function processAutoRenew(): void
    {
        $orders = Order::where('status', 'activated')
            ->whereIn('product_type', ['tabp', 'time'])
            ->whereIn('auto_renew', [1, 2])
            ->get();

        $userIds = $orders->pluck('user_id')->unique()->filter()->values();
        $productIds = $orders->pluck('product_id')->unique()->filter()->values();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($orders as $order) {
            try {
                $expireTime = 0;
                if ($order->product_type === 'tabp') {
                    $content = json_decode($order->product_content);
                    $duration = (int) ($content->time ?? $content->class_time ?? 0);
                    $expireTime = $order->update_time + $duration * 86400;
                    $timeLeft = $expireTime - time();
                    if ($expireTime <= 0 || $timeLeft > 86400 || $timeLeft < 0 || $order->status === 'expired') {
                        continue;
                    }
                } elseif ($order->product_type === 'time') {
                    $user = $users->get($order->user_id);
                    if ($user === null) {
                        continue;
                    }
                    $expireTime = strtotime($user->class_expire ?? '');
                    if ($expireTime === false || $expireTime <= 0) {
                        continue;
                    }
                    $timeLeft = $expireTime - time();
                    if ($timeLeft > 86400 || $timeLeft < 0) {
                        continue;
                    }
                } else {
                    continue;
                }

                $hasPending = Order::where('user_id', $order->user_id)
                    ->where('product_type', $order->product_type)
                    ->whereIn('status', ['pending_payment', 'pending_activation'])
                    ->exists();
                if ($hasPending) {
                    continue;
                }

                $product = $products->get($order->product_id);
                if ($product === null || $product->status !== 1) {
                    if ($order->auto_renew === 1) {
                        $order->auto_renew = 0;
                        $order->save();
                        $user = $users->get($order->user_id);
                        if ($user !== null) {
                            try {
                                Notification::notifyUser(
                                    $user,
                                    $_ENV['appName'] . ' - 订单自动续费已停用',
                                    "您的商品【{$order->product_name}】(订单 #{$order->id}) 因商品已下架或停售，系统已自动关闭该订单的自动续费。请及时前往商城选购其他套餐。"
                                );
                            } catch (Throwable $e) {
                                echo $e->getMessage() . PHP_EOL;
                            }
                        }
                    }
                    continue;
                }
                $price = (float) $product->price;

                DB::transaction(static function () use ($order, $product, $price): void {
                    $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->first();
                    if ($lockedOrder === null || $lockedOrder->status !== 'activated' ||
                        ! in_array($lockedOrder->auto_renew, [1, 2], true) ||
                        $lockedOrder->product_type !== $product->type
                    ) {
                        return;
                    }

                    $user = User::where('id', $lockedOrder->user_id)->lockForUpdate()->first();
                    if ($user === null) {
                        return;
                    }

                    if ($lockedOrder->product_type === 'tabp') {
                        $content = json_decode($lockedOrder->product_content);
                        $duration = (int) ($content->time ?? $content->class_time ?? 0);
                        $expireTime = $lockedOrder->update_time + $duration * 86400;
                        if ($expireTime - time() < 0) {
                            return;
                        }
                    } elseif ($lockedOrder->product_type === 'time') {
                        $expireTime = strtotime($user->class_expire ?? '');
                        if ($expireTime === false || $expireTime <= 0 || ($expireTime - time()) > 86400 || ($expireTime - time()) < 0) {
                            return;
                        }
                    }

                    if (Order::where('user_id', $user->id)
                        ->where('product_type', $lockedOrder->product_type)
                        ->whereIn('status', ['pending_payment', 'pending_activation'])
                        ->exists()
                    ) {
                        return;
                    }

                    if ($user->money < $price) {
                        if ($lockedOrder->auto_renew === 1) {
                            $lockedOrder->auto_renew = 2;
                            $lockedOrder->save();
                            try {
                                $tip = $lockedOrder->product_type === 'tabp'
                                    ? '请在订单到期前及时充值，系统会继续尝试续费；订单过期后将不再自动重试。'
                                    : '请及时充值，充值后系统将自动重试续费。';
                                $prefix = $lockedOrder->product_type === 'tabp'
                                    ? "您的商品【{$product->name}】(订单 #{$lockedOrder->id}) 即将到期，自动续费扣款失败（需要 ¥{$price}，当前账户余额 ¥{$user->money}）。"
                                    : "您的商品【{$product->name}】(订单 #{$lockedOrder->id}) 自动续费扣款失败（需要 ¥{$price}，当前账户余额 ¥{$user->money}）。";
                                Notification::notifyUser(
                                    $user,
                                    $_ENV['appName'] . ' - 订单自动续费扣款失败提醒',
                                    "{$prefix}{$tip}"
                                );
                            } catch (Throwable $e) {
                                echo $e->getMessage() . PHP_EOL;
                            }
                        }
                        return;
                    }

                    if ($product->stock === 0) {
                        if ($lockedOrder->auto_renew === 1) {
                            $lockedOrder->auto_renew = 2;
                            $lockedOrder->save();
                            try {
                                Notification::notifyUser(
                                    $user,
                                    $_ENV['appName'] . ' - 订单自动续费库存不足提醒',
                                    "您的商品【{$product->name}】(订单 #{$lockedOrder->id}) 即将到期，因商品暂时售罄无法自动续费。请留意库存补充或前往商城选购其他套餐。"
                                );
                            } catch (Throwable $e) {
                                echo $e->getMessage() . PHP_EOL;
                            }
                        }
                        return;
                    }
                    if ($product->stock > 0) {
                        $affected = Product::where('id', $product->id)->where('stock', '>', 0)->decrement('stock', 1);
                        if ($affected === 0) {
                            return;
                        }
                    }
                    Product::where('id', $product->id)->increment('sale_count', 1);

                    $moneyBefore = (float) $user->money;
                    $user->money -= $price;
                    $user->save();

                    (new UserMoneyLog())->add(
                        $user->id,
                        $moneyBefore,
                        (float) $user->money,
                        -$price,
                        "自动续费订单商品 {$product->name}"
                    );

                    $newOrder = new Order();
                    $newOrder->user_id = $user->id;
                    $newOrder->product_id = $product->id;
                    $newOrder->product_type = $product->type;
                    $newOrder->product_name = $product->name;
                    $newOrder->product_content = $product->content;
                    $newOrder->coupon = '';
                    $newOrder->price = $price;
                    $newOrder->status = 'pending_activation';
                    $newOrder->auto_renew = 1;
                    $newOrder->create_time = time();
                    $newOrder->update_time = time();
                    $newOrder->save();

                    $invoiceContent = [
                        [
                            'content_id' => 0,
                            'name' => $product->name . ' (自动续费)',
                            'price' => $price,
                        ],
                    ];
                    $invoice = new Invoice();
                    $invoice->user_id = $user->id;
                    $invoice->order_id = $newOrder->id;
                    $invoice->content = json_encode($invoiceContent);
                    $invoice->price = $price;
                    $invoice->status = 'paid_balance';
                    $invoice->create_time = time();
                    $invoice->update_time = time();
                    $invoice->pay_time = time();
                    $invoice->type = 'product';
                    $invoice->save();

                    $lockedOrder->auto_renew = 0;
                    $lockedOrder->save();

                    echo "订单 #{$lockedOrder->id} 自动续费成功，创建新订单 #{$newOrder->id}。\n";

                    try {
                        Notification::notifyUser(
                            $user,
                            $_ENV['appName'] . ' - 订单自动续费成功',
                            "您的商品【{$product->name}】已成功自动续费，生成新订单 #{$newOrder->id}，扣除余额 ¥{$price}。"
                        );
                    } catch (Throwable $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }
                });
            } catch (Throwable $e) {
                echo 'Cron processAutoRenew failed for order #' . $order->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 自动续费检测处理完成' . PHP_EOL;
    }

    public static function processPendingOrder(): void
    {
        $pending_payment_orders = (new Order())->where('status', 'pending_payment')->pluck('id');

        foreach ($pending_payment_orders as $orderId) {
            try {
                $shouldActivate = false;
                DB::transaction(static function () use ($orderId, &$shouldActivate): void {
                    $order = (new Order())->where('id', $orderId)->lockForUpdate()->first();
                    if ($order === null || $order->status !== 'pending_payment') {
                        return;
                    }
                    $invoice = (new Invoice())->where('order_id', $order->id)->lockForUpdate()->first();

                    if ($invoice === null) {
                        return;
                    }
                    if (in_array($invoice->status, ['paid_gateway', 'paid_balance', 'paid_admin'])) {
                        $order->status = 'pending_activation';
                        $order->update_time = time();
                        $order->save();
                        echo "已标记订单 #{$order->id} 为等待激活。\n";
                        $shouldActivate = true;
                        return;
                    }
                    if ($order->create_time + 86400 < time() && $invoice->status !== 'partially_paid') {
                        $order->status = 'cancelled';
                        $order->update_time = time();
                        $order->save();
                        $invoice->status = 'cancelled';
                        $invoice->update_time = time();
                        $invoice->save();

                        // Rollback stock and sale count
                        $product = Product::find($order->product_id);
                        if ($product !== null) {
                            if ($product->stock >= 0) {
                                Product::where('id', $product->id)->increment('stock', 1);
                            }
                            Product::where('id', $product->id)->where('sale_count', '>', 0)->decrement('sale_count', 1);
                        }
                        // Rollback coupon usage
                        if ($order->coupon !== '') {
                            UserCoupon::where('code', $order->coupon)->where('use_count', '>', 0)->decrement('use_count', 1);
                        }

                        echo "已取消超时订单 #{$order->id} 和账单 #{$invoice->id}，并恢复库存及优惠码使用次数。\n";
                    }
                });

                if ($shouldActivate) {
                    self::activateOrder($orderId);
                }
            } catch (Throwable $e) {
                echo 'Cron processPendingOrder failed for order #' . $orderId . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 等待中订单处理完成' . PHP_EOL;
    }

    public static function removeInactiveUserLinkAndInvite(): void
    {
        $inactive_user_ids = (new User())->where('is_inactive', 1)->pluck('id');

        if ($inactive_user_ids->isNotEmpty()) {
            Link::whereIn('userid', $inactive_user_ids)->delete();
            InviteCode::whereIn('user_id', $inactive_user_ids)->delete();
        }

        echo Tools::toDateTime(time()) . ' Successfully removed inactive user\'s Link and Invite' . PHP_EOL;
    }

    public static function resetNodeBandwidth(): void
    {
        (new Node())->where('bandwidthlimit_resetday', date('d'))->update(['node_bandwidth' => 0]);

        echo Tools::toDateTime(time()) . ' 重设节点流量完成' . PHP_EOL;
    }

    public static function resetTodayBandwidth(): void
    {
        (new User())->query()->update(['transfer_today' => 0]);

        echo Tools::toDateTime(time()) . ' 重设用户每日流量完成' . PHP_EOL;
    }

    public static function resetFreeUserBandwidth(): void
    {
        $today = (int) date('d');
        $lastDay = (int) date('t');

        $query = (new User())->where('class', 0);
        if ($today === $lastDay) {
            $freeUsers = $query->where('auto_reset_day', '>=', $today)->get();
        } else {
            $freeUsers = $query->where('auto_reset_day', $today)->get();
        }

        foreach ($freeUsers as $user) {
            try {
                try {
                    Notification::notifyUser(
                        $user,
                        $_ENV['appName'] . '-免费流量重置通知',
                        '你好，你的免费流量已经被重置为' . $user->auto_reset_bandwidth . 'GB。'
                    );
                } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                    echo $e->getMessage() . PHP_EOL;
                }

                $user->u = 0;
                $user->d = 0;
                $user->transfer_enable = $user->auto_reset_bandwidth * 1024 * 1024 * 1024;
                $user->save();
            } catch (Throwable $e) {
                echo 'Cron resetFreeUserBandwidth failed for user #' . $user->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 免费用户流量重置完成' . PHP_EOL;
    }

    public static function sendDailyFinanceMail(): void
    {
        $today = strtotime('00:00:00');
        $paylists = (new Paylist())->where('status', 1)
            ->whereBetween('datetime', [strtotime('-1 day', $today), $today])->get();

        if (count($paylists) > 0) {
            $userNames = User::whereIn('id', $paylists->pluck('userid'))->pluck('user_name', 'id');
            $text_html = '<table><tr><td>金额</td><td>用户ID</td><td>用户名</td><td>充值时间</td></tr>';

            foreach ($paylists as $paylist) {
                try {
                    $userName = $userNames[$paylist->userid] ?? '已删除用户';
                    $text_html .= '<tr>';
                    $text_html .= '<td>' . $paylist->total . '</td>';
                    $text_html .= '<td>' . $paylist->userid . '</td>';
                    $text_html .= '<td>' . $userName . '</td>';
                    $text_html .= '<td>' . Tools::toDateTime((int) $paylist->datetime) . '</td>';
                    $text_html .= '</tr>';
                } catch (Throwable $e) {
                    echo 'Cron sendDailyFinanceMail failed for paylist #' . $paylist->id . ': ' . $e->getMessage() . PHP_EOL;
                }
            }

            $text_html .= '</table>';
            $text_html .= '<br>昨日总收入笔数：' . count($paylists) . '<br>昨日总收入金额：' . $paylists->sum('total');

            $text_html = str_replace([
                '<table>',
                '<tr>',
                '<td>',
            ], [
                '<table style="width: 100%;border: 1px solid black;border-collapse: collapse;">',
                '<tr style="border: 1px solid black;padding: 5px;">',
                '<td style="border: 1px solid black;padding: 5px;">',
            ], $text_html);

            echo 'Sending daily finance email to admin user' . PHP_EOL;

            try {
                Notification::notifyAdmin(
                    '财务日报',
                    $text_html,
                    'finance.tpl'
                );
            } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                echo $e->getMessage() . PHP_EOL;
            }

            echo Tools::toDateTime(time()) . ' Successfully sent daily finance email' . PHP_EOL;
        } else {
            echo 'No paylist found' . PHP_EOL;
        }
    }

    public static function sendWeeklyFinanceMail(): void
    {
        $today = strtotime('00:00:00');
        $paylists = (new Paylist())->where('status', 1)
            ->whereBetween('datetime', [strtotime('-1 week', $today), $today])
            ->get();

        $text_html = '<br>上周总收入笔数：' . count($paylists) . '<br>上周总收入金额：' . $paylists->sum('total');
        echo 'Sending weekly finance email to admin user' . PHP_EOL;

        try {
            Notification::notifyAdmin(
                '财务周报',
                $text_html,
                'finance.tpl'
            );
        } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' 成功发送财务周报' . PHP_EOL;
    }

    public static function sendMonthlyFinanceMail(): void
    {
        $today = strtotime('00:00:00');
        $paylists = (new Paylist())->where('status', 1)
            ->whereBetween('datetime', [strtotime('-1 month', $today), $today])
            ->get();

        $text_html = '<br>上月总收入笔数：' . count($paylists) . '<br>上月总收入金额：' . $paylists->sum('total');
        echo 'Sending monthly finance email to admin user' . PHP_EOL;

        try {
            Notification::notifyAdmin(
                '财务月报',
                $text_html,
                'finance.tpl'
            );
        } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' 成功发送财务月报' . PHP_EOL;
    }

    public static function sendPaidUserUsageLimitNotification(): void
    {
        $paidUsers = (new User())->where('class', '>', 0)->get();

        foreach ($paidUsers as $user) {
            try {
                $user_traffic_left = $user->transfer_enable - $user->u - $user->d;
                $under_limit = false;
                $unit_text = '';

                if (Config::obtain('notify_limit_mode') === 'per' &&
                    $user->transfer_enable > 0 &&
                    $user_traffic_left / $user->transfer_enable * 100 < Config::obtain('notify_limit_value')
                ) {
                    $under_limit = true;
                    $unit_text = '%';
                } elseif (Config::obtain('notify_limit_mode') === 'mb' &&
                    Tools::bToMB($user_traffic_left) < Config::obtain('notify_limit_value')
                ) {
                    $under_limit = true;
                    $unit_text = 'MB';
                }

                if ($under_limit && ! $user->traffic_notified) {
                    try {
                        Notification::notifyUser(
                            $user,
                            $_ENV['appName'] . '-你的剩余流量过低',
                            '你好，系统发现你剩余流量已经低于 ' . Config::obtain('notify_limit_value') . $unit_text . ' 。',
                        );

                        $user->traffic_notified = true;
                    } catch (GuzzleException|ClientExceptionInterface|TelegramSDKException $e) {
                        $user->traffic_notified = false;
                        echo $e->getMessage() . PHP_EOL;
                    }

                    $user->save();
                } elseif (! $under_limit && $user->traffic_notified) {
                    $user->traffic_notified = false;
                    $user->save();
                }
            } catch (Throwable $e) {
                echo 'Cron sendPaidUserUsageLimitNotification failed for user #' . $user->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 付费用户用量限制提醒完成' . PHP_EOL;
    }

    public static function sendDailyTrafficReport(): void
    {
        $users = (new User())->whereIn('daily_mail_enable', [1, 2])->get();
        $ann_latest_raw = (new Ann())->where('status', '>', 0)
            ->orderBy('status', 'desc')
            ->orderBy('sort')
            ->orderBy('date', 'desc')->first();

        if ($ann_latest_raw === null) {
            $ann_latest = '<br><br>';
        } else {
            $ann_latest = $ann_latest_raw->content . '<br><br>';
        }

        foreach ($users as $user) {
            try {
                $user->sendDailyNotification($ann_latest);
            } catch (Throwable $e) {
                echo 'Cron sendDailyTrafficReport failed for user #' . $user->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' Successfully sent daily traffic report' . PHP_EOL;
    }

    public static function sendDailyJobNotification(): void
    {
        try {
            Notification::notifyUserGroup(
                I18n::trans('bot.daily_job_run', $_ENV['locale'])
            );
        } catch (TelegramSDKException | GuzzleException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent daily job notification' . PHP_EOL;
    }

    public static function sendDiaryNotification(): void
    {
        try {
            Notification::notifyUserGroup(
                str_replace(
                    [
                        '%checkin_user%',
                        '%lastday_total%',
                    ],
                    [
                        Analytics::getTodayCheckinUser(),
                        Analytics::getTodayTrafficUsage(),
                    ],
                    I18n::trans('bot.diary', $_ENV['locale'])
                )
            );
        } catch (TelegramSDKException | GuzzleException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        echo Tools::toDateTime(time()) . ' Successfully sent diary notification' . PHP_EOL;
    }

    public static function updateNodeIp(): void
    {
        $nodes = (new Node())->where('type', 1)->get();

        foreach ($nodes as $node) {
            try {
                $node->updateNodeIp();
                $node->save();
            } catch (Throwable $e) {
                echo 'Cron updateNodeIp failed for node #' . $node->id . ': ' . $e->getMessage() . PHP_EOL;
            }
        }

        echo Tools::toDateTime(time()) . ' 更新节点 IP 完成' . PHP_EOL;
    }
}
