<?php

/**
 * TABP package upgrade eligibility tests using Pest
 */

use App\Controllers\User\OrderController;
use App\Models\Order;
use App\Models\Product;

require_once __DIR__ . '/../../../app/predefine.php';

function makeTabpProduct(int $id, int $tier, int $days, float $price): Product
{
    $product = new Product();
    $product->id = $id;
    $product->type = 'tabp';
    $product->price = $price;
    $product->content = json_encode([
        'class' => $tier,
        'tier_level' => $tier,
        'time' => $days,
        'class_time' => $days,
        'bandwidth' => 1000,
    ]);

    return $product;
}

function makeActiveTabpOrder(int $productId, int $tier, int $totalDays, int $daysElapsed, float $price): Order
{
    $order = new Order();
    $order->id = 100;
    $order->product_id = $productId;
    $order->product_type = 'tabp';
    $order->status = 'activated';
    $order->price = $price;
    $order->update_time = time() - ($daysElapsed * 86400);
    $order->product_content = json_encode([
        'class' => $tier,
        'tier_level' => $tier,
        'time' => $totalDays,
        'class_time' => $totalDays,
        'bandwidth' => 1280,
    ]);

    return $order;
}

describe('OrderController::canUpgradeProduct duration guard', function () {
    it('blocks upgrading from an annual plan with many remaining days to a shorter monthly or quarterly plan', function () {
        $activeOrder = makeActiveTabpOrder(1, 4, 365, 200, 128.0); // 165 days remaining
        $monthlyHigher = makeTabpProduct(2, 5, 30, 18.0);
        $quarterlyHigher = makeTabpProduct(3, 5, 90, 50.0);
        $annualHigher = makeTabpProduct(4, 5, 365, 180.0);

        expect(OrderController::canUpgradeProduct($monthlyHigher, $activeOrder))->toBeFalse()
            ->and(OrderController::canUpgradeProduct($quarterlyHigher, $activeOrder))->toBeFalse()
            ->and(OrderController::canUpgradeProduct($annualHigher, $activeOrder))->toBeTrue();
    });

    it('allows upgrading to a monthly plan once the annual plan has 30 or fewer days remaining', function () {
        $activeOrder = makeActiveTabpOrder(1, 4, 365, 340, 128.0); // 25 days remaining
        $monthlyHigher = makeTabpProduct(2, 5, 30, 18.0);

        expect(OrderController::canUpgradeProduct($monthlyHigher, $activeOrder))->toBeTrue();
    });

    it('allows upgrading a monthly plan to a higher-tier monthly plan even right after activation', function () {
        $activeOrder = makeActiveTabpOrder(10, 4, 30, 0, 12.0); // 30 days remaining
        $monthlyHigher = makeTabpProduct(11, 5, 30, 18.0);

        expect(OrderController::canUpgradeProduct($monthlyHigher, $activeOrder))->toBeTrue();
    });

    it('still blocks downgrading to a lower tier or cheaper same-tier plan', function () {
        $activeOrder = makeActiveTabpOrder(10, 5, 30, 5, 18.0);
        $lowerTierAnnual = makeTabpProduct(12, 4, 365, 128.0);
        $sameTierCheaper = makeTabpProduct(13, 5, 30, 15.0);

        expect(OrderController::canUpgradeProduct($lowerTierAnnual, $activeOrder))->toBeFalse()
            ->and(OrderController::canUpgradeProduct($sameTierCheaper, $activeOrder))->toBeFalse();
    });
});
