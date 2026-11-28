<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\HourlyUsage;
use App\Models\UserTrafficStat;
use App\Services\Analytics;
use Illuminate\Database\Capsule\Manager;
use PHPUnit\Framework\TestCase;
use function array_fill;
use function date;
use function json_decode;
use function json_encode;
use function strtotime;

final class BilledTrafficChartTest extends TestCase
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

        $schema->create('hourly_usage', static function ($table): void {
            $table->increments('id');
            $table->integer('user_id')->default(0);
            $table->string('date');
            $table->text('usage');
            $table->text('billed_usage')->nullable();
        });

        $schema->create('user_traffic_stat', static function ($table): void {
            $table->increments('id');
            $table->integer('user_id')->unique();
            $table->string('daily_last_date')->default('1970-01-01');
            $table->text('daily_usage');
            $table->text('daily_billed_usage')->nullable();
            $table->string('monthly_last_month')->default('1970-01');
            $table->text('monthly_usage');
            $table->text('monthly_billed_usage')->nullable();
        });
    }

    public function testHourlyUsageAccumulatesRawAndBilledTrafficAndBackfillsNullBilledRow(): void
    {
        $today = date('Y-m-d');
        $curHour = (int) date('H');
        $prevHour = $curHour > 0 ? $curHour - 1 : 0;

        // Pre-existing row with billed_usage = null (migrated mid-day).
        $legacyHours = array_fill(0, 24, 0);
        $legacyHours[$prevHour] = 1_000_000;
        HourlyUsage::query()->create([
            'user_id' => 1,
            'date' => $today,
            'usage' => json_encode($legacyHours),
            'billed_usage' => null,
        ]);

        // Report 2MB raw at 3x rate = 6MB billed for user 1; 4MB raw at 0.5x = 2MB billed for new user 2.
        HourlyUsage::addBatch(
            [1 => 2_000_000, 2 => 4_000_000],
            [1 => 6_000_000, 2 => 2_000_000]
        );

        $u1 = HourlyUsage::where('user_id', 1)->where('date', $today)->firstOrFail();
        $u1Raw = json_decode((string) $u1->usage, true);
        $u1Billed = json_decode((string) $u1->billed_usage, true);

        if ($prevHour !== $curHour) {
            self::assertSame(1_000_000, $u1Raw[$prevHour]);
            self::assertSame(1_000_000, $u1Billed[$prevHour]);
            self::assertSame(2_000_000, $u1Raw[$curHour]);
            self::assertSame(6_000_000, $u1Billed[$curHour]);
        } else {
            self::assertSame(3_000_000, $u1Raw[$curHour]);
            self::assertSame(7_000_000, $u1Billed[$curHour]);
        }

        $u2 = HourlyUsage::where('user_id', 2)->where('date', $today)->firstOrFail();
        $u2Raw = json_decode((string) $u2->usage, true);
        $u2Billed = json_decode((string) $u2->billed_usage, true);
        self::assertSame(4_000_000, $u2Raw[$curHour]);
        self::assertSame(2_000_000, $u2Billed[$curHour]);
    }

    public function testAnalyticsReturnsBothRawAndBilledAcross24h30dAnd12m(): void
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');
        $curHour = (int) date('H');

        $yRaw = array_fill(0, 24, 0);
        $yRaw[10] = 500_000;
        $yBilled = array_fill(0, 24, 0);
        $yBilled[10] = 1_500_000; // 3x yesterday

        HourlyUsage::query()->create([
            'user_id' => 9,
            'date' => $yesterday,
            'usage' => json_encode($yRaw),
            'billed_usage' => json_encode($yBilled),
        ]);

        HourlyUsage::addBatch([9 => 1_000_000], [9 => 2_500_000]); // 2.5x today

        $past24 = Analytics::getUserPast24HoursUsage(9);
        self::assertSame(1_000_000, $past24['usage'][23]);
        self::assertSame(2_500_000, $past24['billed_usage'][23]);
        self::assertCount(24, $past24['labels']);

        $past30 = Analytics::getUserPast30DaysUsage(9);
        self::assertSame(500_000, $past30['usage'][28]);
        self::assertSame(1_500_000, $past30['billed_usage'][28]);
        self::assertSame(1_000_000, $past30['usage'][29]);
        self::assertSame(2_500_000, $past30['billed_usage'][29]);

        $past12 = Analytics::getUserPast12MonthsUsage(9);
        self::assertSame(1_000_000 + (date('Y-m', strtotime('-1 day')) === date('Y-m') ? 500_000 : 0), $past12['usage'][11]);
        self::assertSame(2_500_000 + (date('Y-m', strtotime('-1 day')) === date('Y-m') ? 1_500_000 : 0), $past12['billed_usage'][11]);
    }
}
