<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class Auth
{
    private User $user;

    public static function login($uid, $time): void
    {
        self::getDriver()->login($uid, $time);
    }

    public static function getUser(): User
    {
        global $user;

        if ($user === null) {
            $user = self::getDriver()->getUser();
        }

        return $user;
    }

    public static function logout(): void
    {
        self::getDriver()->logout();
    }

    public static function isImpersonating(): bool
    {
        $currentUser = self::getUser();
        if (! $currentUser->isLogin) {
            return false;
        }

        return self::getDriver()->isImpersonatingUser((int) $currentUser->id);
    }

    /**
     * @return array{ok: bool, msg: string}
     */
    public static function impersonate(User $admin, User $targetUser): array
    {
        return self::getDriver()->impersonate($admin, $targetUser);
    }

    /**
     * @return array{ok: bool, msg: string, target_uid?: int}
     */
    public static function leaveImpersonation(): array
    {
        $currentUser = self::getUser();
        if (! $currentUser->isLogin) {
            return ['ok' => false, 'msg' => '无效的登录状态'];
        }

        return self::getDriver()->leaveImpersonation((int) $currentUser->id);
    }

    public static function resetCachedUser(): void
    {
        global $user;
        $user = null;
    }

    private static function getDriver(): Auth\Cookie
    {
        return Factory::createAuth();
    }
}
