<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Config;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Paylist;
use App\Services\Cron;
use Illuminate\Database\Capsule\Manager;
use PHPUnit\Framework\TestCase;

final class CleanCancelledOrdersTest extends TestCase
{
    private Manager $database;

    protected function setUp(): void
    {
        $this->database = new Manager();
        $this->database->addConnection([
            'driver' => 'sqlite',
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
    }

    public function testDoesNotCleanWhenRetentionDaysIsZero(): void
    {
        Config::query()->create([
            'item' => 'cancelled_order_retention_days',
            'value' => '0',
            'type' => 'int',
        ]);

        $oldTime = time() - (86400 * 40);

        $order = Order::query()->create([
            'status' => 'cancelled',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);

        $invoice = Invoice::query()->create([
            'order_id' => $order->id,
            'status' => 'cancelled',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);

        $paylist = Paylist::query()->create([
            'invoice_id' => $invoice->id,
            'status' => 0,
        ]);

        Cron::cleanCancelledOrdersAndInvoices();

        $this->assertNotNull(Order::query()->find($order->id));
        $this->assertNotNull(Invoice::query()->find($invoice->id));
        $this->assertNotNull(Paylist::query()->find($paylist->id));
    }

    public function testCleansCancelledOrdersAndInvoicesOlderThanRetentionDays(): void
    {
        Config::query()->create([
            'item' => 'cancelled_order_retention_days',
            'value' => '30',
            'type' => 'int',
        ]);

        $oldTime = time() - (86400 * 35);
        $recentTime = time() - (86400 * 5);

        // 1. Old cancelled order & invoice -> Should be deleted
        $oldOrder = Order::query()->create([
            'status' => 'cancelled',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);
        $oldInvoice = Invoice::query()->create([
            'order_id' => $oldOrder->id,
            'status' => 'cancelled',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);
        $unpaidPaylist = Paylist::query()->create([
            'invoice_id' => $oldInvoice->id,
            'status' => 0,
        ]);

        // 2. Recent cancelled order & invoice -> Should NOT be deleted
        $recentOrder = Order::query()->create([
            'status' => 'cancelled',
            'create_time' => $recentTime,
            'update_time' => $recentTime,
        ]);
        $recentInvoice = Invoice::query()->create([
            'order_id' => $recentOrder->id,
            'status' => 'cancelled',
            'create_time' => $recentTime,
            'update_time' => $recentTime,
        ]);

        // 3. Old order cancelled, but invoice refunded_balance -> Neither should be deleted
        $refundedOrder = Order::query()->create([
            'status' => 'cancelled',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);
        $refundedInvoice = Invoice::query()->create([
            'order_id' => $refundedOrder->id,
            'status' => 'refunded_balance',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);

        // 4. Old active order & invoice -> Should NOT be deleted
        $activeOrder = Order::query()->create([
            'status' => 'activated',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);
        $paidInvoice = Invoice::query()->create([
            'order_id' => $activeOrder->id,
            'status' => 'paid_gateway',
            'create_time' => $oldTime,
            'update_time' => $oldTime,
        ]);
        $paidPaylist = Paylist::query()->create([
            'invoice_id' => $paidInvoice->id,
            'status' => 1,
        ]);

        Cron::cleanCancelledOrdersAndInvoices();

        // Check old cancelled items are deleted
        $this->assertNull(Order::query()->find($oldOrder->id));
        $this->assertNull(Invoice::query()->find($oldInvoice->id));
        $this->assertNull(Paylist::query()->find($unpaidPaylist->id));

        // Check recent cancelled items are preserved
        $this->assertNotNull(Order::query()->find($recentOrder->id));
        $this->assertNotNull(Invoice::query()->find($recentInvoice->id));

        // Check refunded order & invoice are preserved
        $this->assertNotNull(Order::query()->find($refundedOrder->id));
        $this->assertNotNull(Invoice::query()->find($refundedInvoice->id));

        // Check active order, paid invoice, and paid paylist are preserved
        $this->assertNotNull(Order::query()->find($activeOrder->id));
        $this->assertNotNull(Invoice::query()->find($paidInvoice->id));
        $this->assertNotNull(Paylist::query()->find($paidPaylist->id));
    }
}
