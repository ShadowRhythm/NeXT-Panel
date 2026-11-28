<?php

/**
 * Invite eligibility rule tests using Pest
 */

use App\Models\User;

require_once __DIR__ . '/../../../app/predefine.php';

function makeInviteTestUser(int $class, string $regDate): User
{
    $user = new User();
    $user->id = 1;
    $user->class = $class;
    $user->reg_date = $regDate;
    $user->locale = 'zh_CN';

    return $user;
}

describe('User::getInviteEligibility', function () {
    it('allows users with class >= 2 and registration age >= 90 days', function () {
        $user = makeInviteTestUser(2, date('Y-m-d H:i:s', time() - (91 * 86400)));
        $res = $user->getInviteEligibility('zh_CN');

        expect($res['allowed'])->toBeTrue()
            ->and($res['reasons'])->toBeEmpty()
            ->and($res['remaining_reg_days'])->toBe(0);
    });

    it('denies users below class 2 even after 90 days', function () {
        $user = makeInviteTestUser(1, date('Y-m-d H:i:s', time() - (120 * 86400)));
        $res = $user->getInviteEligibility('zh_CN');

        expect($res['allowed'])->toBeFalse()
            ->and($res['reasons'])->toHaveCount(1);
    });

    it('denies users registered for fewer than 90 days even at class >= 2', function () {
        $user = makeInviteTestUser(3, date('Y-m-d H:i:s', time() - (30 * 86400)));
        $res = $user->getInviteEligibility('zh_CN');

        expect($res['allowed'])->toBeFalse()
            ->and($res['reasons'])->toHaveCount(1)
            ->and($res['remaining_reg_days'])->toBe(60);
    });

    it('lists both unmet reasons when neither class nor reg duration is satisfied', function () {
        $user = makeInviteTestUser(0, date('Y-m-d H:i:s', time() - (10 * 86400)));
        $res = $user->getInviteEligibility('zh_CN');

        expect($res['allowed'])->toBeFalse()
            ->and($res['reasons'])->toHaveCount(2);
    });

    it('fails closed when reg_date is missing or unparseable', function () {
        $user = makeInviteTestUser(2, '');
        $res = $user->getInviteEligibility('zh_CN');

        expect($res['allowed'])->toBeFalse()
            ->and($res['reasons'])->toHaveCount(1);
    });
});
