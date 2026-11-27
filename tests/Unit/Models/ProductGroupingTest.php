<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Controllers\User\OrderController;
use App\Models\Order;
use App\Models\Product;
use PHPUnit\Framework\TestCase;
use function json_encode;

final class ProductGroupingTest extends TestCase
{
    public function testProductInfersPlanGroupTierAndCycle(): void
    {
        $standardMonthly = new Product();
        $standardMonthly->name = 'Standard - 月付';
        $standardMonthly->type = 'tabp';
        $standardMonthly->price = 10.0;
        $standardMonthly->content = json_encode([
            'time' => 30,
            'class_time' => 30,
            'class' => 3,
            'bandwidth' => 100,
        ]);

        self::assertSame('Standard', $standardMonthly->getPlanGroup());
        self::assertSame(3, $standardMonthly->getTierLevel());
        self::assertSame('monthly', $standardMonthly->getCycleKey());

        $explicitUltra = new Product();
        $explicitUltra->name = 'YEAR-W-PERSONAL';
        $explicitUltra->type = 'tabp';
        $explicitUltra->price = 168.0;
        $explicitUltra->content = json_encode([
            'time' => 365,
            'class_time' => 365,
            'class' => 5,
            'bandwidth' => 1800,
            'plan_group' => 'Ultra',
            'tier_level' => 5,
            'cycle_label' => 'yearly',
        ]);

        self::assertSame('Ultra', $explicitUltra->getPlanGroup());
        self::assertSame(5, $explicitUltra->getTierLevel());
        self::assertSame('yearly', $explicitUltra->getCycleKey());
    }

    public function testUpgradeAllowsHigherTierEvenWhenTargetPriceIsLower(): void
    {
        $activeOrder = new Order();
        $activeOrder->product_id = 7;
        $activeOrder->price = 30.0;
        $activeOrder->product_content = json_encode([
            'time' => 91,
            'class_time' => 91,
            'class' => 3,
            'bandwidth' => 300,
            'tier_level' => 3,
        ]);

        $higherTierMonthly = new Product();
        $higherTierMonthly->id = 6;
        $higherTierMonthly->type = 'tabp';
        $higherTierMonthly->price = 18.0;
        $higherTierMonthly->content = json_encode([
            'time' => 30,
            'class_time' => 30,
            'class' => 5,
            'bandwidth' => 200,
            'tier_level' => 5,
        ]);

        $lowerTierQuarterly = new Product();
        $lowerTierQuarterly->id = 8;
        $lowerTierQuarterly->type = 'tabp';
        $lowerTierQuarterly->price = 30.0;
        $lowerTierQuarterly->content = json_encode([
            'time' => 91,
            'class_time' => 91,
            'class' => 2,
            'bandwidth' => 180,
            'tier_level' => 2,
        ]);

        self::assertTrue(OrderController::canUpgradeProduct($higherTierMonthly, $activeOrder));
        self::assertFalse(OrderController::canUpgradeProduct($lowerTierQuarterly, $activeOrder));
    }
}
