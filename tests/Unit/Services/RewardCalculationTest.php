<?php

/**
 * Reward calculation unit tests using Pest
 */

use App\Services\Reward;

require_once __DIR__ . '/../../../app/predefine.php';

describe('Reward::calculatePaybackAmount', function () {
    it('returns rounded percentage reward when under per-invitee count limit', function () {
        $reward = Reward::calculatePaybackAmount('reward_count', 99.99, 0.15, 2, 1, 50.0, 0.0);

        expect($reward)->toBe(15.0);
    });

    it('returns zero when count limit has been reached or count limit is non-positive', function () {
        expect(Reward::calculatePaybackAmount('reward_count', 100.0, 0.2, 1, 1, 50.0, 0.0))->toBe(0.0)
            ->and(Reward::calculatePaybackAmount('reward_count', 100.0, 0.2, 0, 0, 50.0, 0.0))->toBe(0.0);
    });

    it('caps reward at remaining per-invitee total limit and avoids floating-point dust', function () {
        expect(Reward::calculatePaybackAmount('reward_total', 100.0, 0.2, 1, 0, 50.0, 45.0))->toBe(5.0)
            ->and(Reward::calculatePaybackAmount('reward_total', 100.0, 0.2, 1, 0, 50.0, 50.0))->toBe(0.0)
            ->and(Reward::calculatePaybackAmount('reward_total', 100.0, 0.2, 1, 0, 50.0, 49.9999))->toBe(0.0);
    });

    it('returns zero for non-positive order total, rate, or unknown mode', function () {
        expect(Reward::calculatePaybackAmount('reward_count', 0.0, 0.2, 3, 0, 50.0, 0.0))->toBe(0.0)
            ->and(Reward::calculatePaybackAmount('reward_count', 100.0, 0.0, 3, 0, 50.0, 0.0))->toBe(0.0)
            ->and(Reward::calculatePaybackAmount('unknown', 100.0, 0.2, 3, 0, 50.0, 0.0))->toBe(0.0);
    });
});
