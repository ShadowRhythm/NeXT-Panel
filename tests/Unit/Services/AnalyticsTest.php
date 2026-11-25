<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Config;
use App\Models\HourlyUsage;
use App\Models\Invoice;
use App\Models\Node;
use App\Models\Order;
use App\Models\Payback;
use App\Models\Paylist;
use App\Models\SiteDailyStat;
use App\Models\SubscribeLog;
use App\Models\User;
use App\Services\Analytics;
use App\Services\Cron;
use Illuminate\Database\Capsule\Manager;
use PHPUnit\Framework\TestCase;
use function array_fill;
use function date;
use function json_decode;
use function json_encode;
use function strtotime;
use function substr;
use function time;

final class AnalyticsTest extends TestCase
{
    private Manager $database;

    protected function setUp(): void
    {
        $this->database = new Manager();
        $this->database->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->database->setAsGlobal();
        $this->database->bootEloquent();

        $schema = $this->database->schema();

        $schema->create('config', static function ($table): void {
            $table->increments('id');
            $table->string('item');
            $table->text('value');
            $table->string('type')->default('string');
        });

        $schema->create('user', static function ($table): void {
            $table->increments('id');
            $table->string('user_name')->default('');
            $table->string('email')->default('');
            $table->decimal('money', 12, 2)->default(0);
            $table->integer('class')->default(0);
            $table->string('class_expire')->default('1989-06-04 00:05:00');
            $table->string('reg_date')->default('1989-06-04 00:05:00');
            $table->integer('last_check_in_time')->default(0);
            $table->integer('last_use_time')->default(0);
            $table->integer('is_inactive')->default(0);
        });

        $schema->create('paylist', static function ($table): void {
            $table->increments('id');
            $table->integer('userid')->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->integer('status')->default(0);
            $table->integer('invoice_id')->default(0);
            $table->string('tradeno')->default('');
            $table->string('gateway')->default('');
            $table->integer('datetime')->default(0);
        });

        $schema->create('order', static function ($table): void {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->integer('product_id')->default(0);
            $table->string('product_type')->default('');
            $table->string('product_name')->default('');
            $table->text('product_content')->nullable();
            $table->string('coupon')->default('');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status')->default('');
            $table->integer('auto_renew')->default(0);
            $table->integer('create_time')->default(0);
            $table->integer('update_time')->default(0);
        });

        $schema->create('invoice', static function ($table): void {
            $table->increments('id');
            $table->string('type')->default('product');
            $table->integer('user_id')->default(0);
            $table->integer('order_id')->default(0);
            $table->text('content')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('status')->default('');
            $table->integer('create_time')->default(0);
            $table->integer('update_time')->default(0);
            $table->integer('pay_time')->default(0);
        });

        $schema->create('hourly_usage', static function ($table): void {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->string('date');
            $table->text('usage');
        });

        $schema->create('subscribe_log', static function ($table): void {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->string('type')->default('');
            $table->string('request_ip')->default('127.0.0.1');
            $table->integer('request_time')->default(0);
            $table->string('request_user_agent')->default('');
        });

        $schema->create('node', static function ($table): void {
            $table->increments('id');
            $table->string('name')->default('');
            $table->integer('type')->default(1);
            $table->integer('sort')->default(14);
            $table->decimal('traffic_rate', 5, 2)->default(1);
            $table->integer('node_class')->default(0);
            $table->bigInteger('node_bandwidth')->default(0);
            $table->bigInteger('node_bandwidth_limit')->default(0);
            $table->integer('node_heartbeat')->default(0);
            $table->integer('online_user')->default(0);
        });

        $schema->create('payback', static function ($table): void {
            $table->increments('id');
            $table->decimal('total', 12, 2)->default(0);
            $table->integer('userid')->default(0);
            $table->integer('ref_by')->default(0);
            $table->decimal('ref_get', 12, 2)->default(0);
            $table->integer('invoice_id')->default(0);
            $table->integer('datetime')->default(0);
        });

        $schema->create('site_daily_stat', static function ($table): void {
            $table->increments('id');
            $table->string('date')->unique();
            $table->decimal('income', 10, 2)->default(0);
            $table->integer('new_users')->default(0);
            $table->integer('active_users')->default(0);
            $table->bigInteger('traffic')->default(0);
            $table->text('class_snapshot')->nullable();
            $table->text('hourly_traffic')->nullable();
            $table->text('client_snapshot')->nullable();
            $table->integer('checkin_users')->default(0);
        });

        Config::query()->create(['item' => 'traffic_log_retention_days', 'value' => '7', 'type' => 'int']);
        Config::query()->create(['item' => 'subscribe_log_retention_days', 'value' => '7', 'type' => 'int']);
        Config::clearCache();
    }

    public function testDetectClientNameNormalizesCommonClients(): void
    {
        $this->assertSame('Clash Verge', Analytics::detectClientName('clash-verge/v1.7.7', 'clash'));
        $this->assertSame('Shadowrocket', Analytics::detectClientName('Shadowrocket/2150 CFNetwork/1490', 'ss'));
        $this->assertSame('sing-box', Analytics::detectClientName('SFI/1.9.3 (sing-box)', 'singbox'));
        $this->assertSame('Mihomo / Meta', Analytics::detectClientName('mihomo/1.18.0', 'clash'));
        $this->assertSame('Clash 订阅', Analytics::detectClientName('', 'clash'));
    }

    public function testAggregateDailyStatPopulatesHourlyClientsAndCheckins(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yTs = strtotime($yesterday . ' 12:00:00');

        User::query()->create([
            'user_name'          => 'alice',
            'email'              => 'alice@example.com',
            'class'              => 1,
            'reg_date'           => $yesterday . ' 09:00:00',
            'last_check_in_time' => $yTs,
            'is_inactive'        => 0,
        ]);

        Paylist::query()->create([
            'userid'   => 1,
            'total'    => 99.50,
            'status'   => 1,
            'gateway'  => 'alipay',
            'tradeno'  => 'T1',
            'datetime' => $yTs,
        ]);

        $hours = array_fill(0, 24, 0);
        $hours[21] = 1024 ** 3; // 1 GB at 21:00
        HourlyUsage::query()->create([
            'user_id' => 1,
            'date'    => $yesterday,
            'usage'   => json_encode($hours),
        ]);

        SubscribeLog::query()->create([
            'user_id'            => 1,
            'type'               => 'clash',
            'request_time'       => $yTs,
            'request_user_agent' => 'clash-verge/1.6.0',
        ]);
        SubscribeLog::query()->create([
            'user_id'            => 1,
            'type'               => 'clash',
            'request_time'       => $yTs + 600,
            'request_user_agent' => 'clash-verge/1.6.0',
        ]);

        (new Cron())->aggregateDailyStat();

        $stat = SiteDailyStat::where('date', $yesterday)->first();
        $this->assertNotNull($stat);
        $this->assertSame(1, (int) $stat->checkin_users);
        $this->assertSame(1024 ** 3, (int) $stat->traffic);

        $decodedHours = json_decode((string) $stat->hourly_traffic, true);
        $this->assertCount(24, $decodedHours);
        $this->assertSame(1024 ** 3, (int) $decodedHours[21]);

        $decodedClients = json_decode((string) $stat->client_snapshot, true);
        $this->assertSame(['Clash Verge' => 1], $decodedClients);
    }

    public function testRevenueAnalysisDistinguishesNewAndRenewalOrders(): void
    {
        $month = date('Y-m');
        $ts = strtotime($month . '-05 10:00:00');
        $oldTs = strtotime($month . '-01 00:00:00 -10 days');

        User::query()->create([
            'user_name' => 'newbuyer',
            'email'     => 'new@example.com',
            'reg_date'  => $month . '-02 08:00:00',
        ]);
        User::query()->create([
            'user_name' => 'oldbuyer',
            'email'     => 'old@example.com',
            'reg_date'  => date('Y-m-d H:i:s', $oldTs),
        ]);

        // Old buyer had prior order before period
        Order::query()->create([
            'user_id'      => 2,
            'product_id'   => 1,
            'product_type' => 'tabp',
            'product_name' => 'Pro',
            'price'        => 30.00,
            'status'       => 'expired',
            'create_time'  => $oldTs,
        ]);

        // In-period orders: new buyer's 1st order ($20) + 2nd order ($20), old buyer's renewal ($50)
        Order::query()->create([
            'user_id'      => 1,
            'product_id'   => 1,
            'product_type' => 'tabp',
            'product_name' => 'Pro',
            'coupon'       => 'WELCOME',
            'price'        => 20.00,
            'status'       => 'activated',
            'auto_renew'   => 2,
            'create_time'  => $ts,
        ]);
        Order::query()->create([
            'user_id'      => 1,
            'product_id'   => 2,
            'product_type' => 'bandwidth',
            'product_name' => 'Pack',
            'price'        => 20.00,
            'status'       => 'activated',
            'create_time'  => $ts + 60,
        ]);
        Order::query()->create([
            'user_id'      => 2,
            'product_id'   => 1,
            'product_type' => 'tabp',
            'product_name' => 'Pro',
            'price'        => 50.00,
            'status'       => 'activated',
            'auto_renew'   => 1,
            'create_time'  => $ts + 120,
        ]);

        Paylist::query()->create([
            'userid'   => 1,
            'total'    => 40.00,
            'status'   => 1,
            'gateway'  => 'stripe',
            'tradeno'  => 'S1',
            'datetime' => $ts,
        ]);
        Invoice::query()->create([
            'user_id'  => 1,
            'order_id' => 2,
            'price'    => 40.00,
            'status'   => 'paid_gateway',
            'pay_time' => $ts,
        ]);

        $res = Analytics::getRevenueAnalysis('day', $month, date('Y'));
        $this->assertSame(1, $res['purchaseMix']['newCount']);
        $this->assertSame(20.0, $res['purchaseMix']['newRevenue']);
        $this->assertSame(2, $res['purchaseMix']['renewalCount']);
        $this->assertSame(70.0, $res['purchaseMix']['renewalRevenue']);
        $this->assertSame(1, $res['autoRenew']['failedCount']);
        $this->assertSame(1, $res['autoRenew']['enabledCount']);
        $this->assertSame('WELCOME', $res['topCoupons'][0]['code']);
    }

    public function testTrafficAndClientFallbackDoesNotDoubleCountAggregatedDays(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        $month = date('Y-m');

        $yHours = array_fill(0, 24, 0);
        $yHours[20] = 2 * (1024 ** 3);
        SiteDailyStat::query()->create([
            'date'            => $yesterday,
            'income'          => 0,
            'new_users'       => 0,
            'active_users'    => 1,
            'traffic'         => 2 * (1024 ** 3),
            'class_snapshot'  => '{}',
            'hourly_traffic'  => json_encode($yHours),
            'client_snapshot' => json_encode(['Shadowrocket' => 5]),
            'checkin_users'   => 1,
        ]);

        // Duplicate raw hourly row for yesterday (should be skipped) + fresh row for today (should be included)
        HourlyUsage::query()->create(['user_id' => 1, 'date' => $yesterday, 'usage' => json_encode($yHours)]);
        $tHours = array_fill(0, 24, 0);
        $tHours[20] = 1024 ** 3;
        HourlyUsage::query()->create(['user_id' => 1, 'date' => $today, 'usage' => json_encode($tHours)]);

        $res = Analytics::getTrafficAndClientStats('day', $month, date('Y'));
        if (substr($yesterday, 0, 7) === $month) {
            $this->assertSame(20, $res['peakHour']);
            $this->assertSame(3.0, $res['peakGb']);
            $this->assertSame('Shadowrocket', $res['clients'][0]['name']);
            $this->assertSame(5, $res['clients'][0]['count']);
        } else {
            $this->assertSame(1.0, $res['peakGb']);
        }
    }

    public function testNodeRetentionAndReferrals(): void
    {
        $month = date('Y-m');
        $now = time();

        User::query()->create([
            'user_name'     => 'expiringUser',
            'email'         => 'exp@example.com',
            'class'         => 1,
            'class_expire'  => date('Y-m-d H:i:s', $now + 2 * 86400),
            'last_use_time' => $now - 10 * 86400,
        ]);
        User::query()->create([
            'user_name' => 'kol',
            'email'     => 'kol@example.com',
        ]);

        Node::query()->create([
            'name'                 => 'HK-01',
            'type'                 => 1,
            'sort'                 => 14,
            'node_bandwidth'       => 85 * (1024 ** 3),
            'node_bandwidth_limit' => 100 * (1024 ** 3),
            'node_heartbeat'       => $now,
            'online_user'          => 12,
        ]);

        Payback::query()->create([
            'total'      => 100.00,
            'userid'     => 1,
            'ref_by'     => 2,
            'ref_get'    => 15.00,
            'invoice_id' => 1,
            'datetime'   => $now,
        ]);

        $res = Analytics::getNodeAndRetentionStats('day', $month, date('Y'));
        $this->assertSame(1, $res['nodeStats']['summary']['nearLimitCount']);
        $this->assertSame(85.0, $res['nodeStats']['nodes'][0]['usage_percent']);
        $this->assertSame(1, $res['retention']['expiring7DaysCount']);
        $this->assertSame(1, $res['retention']['expiringNoAutoRenewCount']);
        $this->assertSame(1, $res['retention']['silentPaidCount']);
        $this->assertSame(100.0, $res['referrals']['summary']['totalSales']);
        $this->assertSame('kol', $res['referrals']['topReferrers'][0]['user_name']);
    }

    public function testInvalidMonthAndYearFallBackSafely(): void
    {
        $daily = Analytics::getSiteDailyByMonth('2026-99');
        $this->assertArrayHasKey('dates', $daily);
        $this->assertArrayHasKey('incomeComparison', $daily);

        $sales = Analytics::getProductSalesRanking('2026-99');
        $this->assertIsArray($sales);

        $yearly = Analytics::getProductSalesRankingByYear('invalid');
        $this->assertIsArray($yearly);
    }
}
