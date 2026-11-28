<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Auth;
use App\Services\Auth\Cookie as CookieAuth;
use App\Utils\Hash;

describe('Auth\\Cookie impersonation guards', function () {
    beforeEach(function () {
        $this->prevCookie = $_COOKIE;
        $this->prevEnv = $_ENV;
        $_COOKIE = [];
        $_ENV['key'] = 'test-secret-key';
        Auth::resetCachedUser();
    });

    afterEach(function () {
        $_COOKIE = $this->prevCookie;
        $_ENV = $this->prevEnv;
        Auth::resetCachedUser();
    });

    it('rejects impersonation when actor is not an active admin', function () {
        $auth = new CookieAuth();

        $guest = new User();
        $guest->id = 1;
        $guest->isLogin = false;
        $guest->is_admin = 1;
        $guest->is_banned = 0;

        $nonAdmin = new User();
        $nonAdmin->id = 2;
        $nonAdmin->isLogin = true;
        $nonAdmin->is_admin = 0;
        $nonAdmin->is_banned = 0;

        $bannedAdmin = new User();
        $bannedAdmin->id = 3;
        $bannedAdmin->isLogin = true;
        $bannedAdmin->is_admin = 1;
        $bannedAdmin->is_banned = 1;

        $target = new User();
        $target->id = 10;
        $target->is_admin = 0;

        expect($auth->impersonate($guest, $target)['ok'])->toBeFalse()
            ->and($auth->impersonate($nonAdmin, $target)['ok'])->toBeFalse()
            ->and($auth->impersonate($bannedAdmin, $target)['ok'])->toBeFalse();
    });

    it('rejects impersonating self or another admin account', function () {
        $auth = new CookieAuth();

        $admin = new User();
        $admin->id = 1;
        $admin->isLogin = true;
        $admin->is_admin = 1;
        $admin->is_banned = 0;

        $otherAdmin = new User();
        $otherAdmin->id = 2;
        $otherAdmin->is_admin = 1;

        expect($auth->impersonate($admin, $admin)['ok'])->toBeFalse()
            ->and($auth->impersonate($admin, $otherAdmin)['ok'])->toBeFalse();
    });

    it('rejects impersonation when admin session cookies are missing or about to expire', function () {
        $auth = new CookieAuth();

        $admin = new User();
        $admin->id = 1;
        $admin->pass = 'hashed-pass';
        $admin->isLogin = true;
        $admin->is_admin = 1;
        $admin->is_banned = 0;

        $target = new User();
        $target->id = 10;
        $target->is_admin = 0;

        expect($auth->impersonate($admin, $target)['ok'])->toBeFalse();

        $soon = time() + 30;
        $_COOKIE['expire_in'] = (string) $soon;
        $_COOKIE['key'] = Hash::cookieHash($admin->pass, $soon);

        expect($auth->impersonate($admin, $target)['ok'])->toBeFalse();
    });

    it('returns false for missing or malformed impersonate tokens', function (string $token) {
        $auth = new CookieAuth();
        $_COOKIE['impersonate'] = $token;

        expect($auth->isImpersonatingUser(10))->toBeFalse()
            ->and($auth->leaveImpersonation(10)['ok'])->toBeFalse();
    })->with([
        'empty' => '',
        'short string' => 'admin',
        'non-hex chars' => str_repeat('z', 64),
        'uppercase hex' => str_repeat('A', 64),
        'too long' => str_repeat('a', 65),
    ]);

    it('reports not impersonating and rejects leave when logged out', function () {
        expect(Auth::isImpersonating())->toBeFalse()
            ->and(Auth::leaveImpersonation()['ok'])->toBeFalse();
    });
});
