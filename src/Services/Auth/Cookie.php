<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\Config;
use App\Models\Node;
use App\Models\User;
use App\Models\UserSession;
use App\Services\Cache;
use App\Services\ClientDetector;
use App\Utils\Cookie as CookieUtils;
use App\Utils\Hash;
use Illuminate\Database\QueryException;
use Throwable;
use function bin2hex;
use function hash;
use function hash_equals;
use function headers_sent;
use function is_array;
use function mb_substr;
use function min;
use function preg_match;
use function random_bytes;
use function time;

final class Cookie extends Base
{
    private const IMPERSONATE_COOKIE = 'impersonate';
    private const IMPERSONATE_CACHE_PREFIX = 'admin_impersonate:';
    private const IMPERSONATE_TTL = 3600;

    public function login($uid, $time): void
    {
        $user = (new User())->find($uid);
        if ($user === null) {
            return;
        }
        $now = time();
        $expire_in = $time + $now;
        $sid = bin2hex(random_bytes(24));
        $tokenHash = hash('sha256', $sid);

        $this->recordSession((int) $uid, $tokenHash, $now, $expire_in);

        CookieUtils::setWithDomain([
            'uid' => (string) $uid,
            'email' => $user->email,
            'key' => Hash::cookieHash($user->pass, $expire_in),
            'ip' => Hash::ipHash($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $uid, $expire_in),
            'device' => Hash::deviceHash($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', $uid, $expire_in),
            'expire_in' => (string) $expire_in,
            'sid' => $sid,
        ], $expire_in, $_SERVER['HTTP_HOST'] ?? '');

        $_COOKIE['sid'] = $sid;
    }

    public function getUser(): User
    {
        $uid = CookieUtils::get('uid');
        $email = CookieUtils::get('email');
        $key = CookieUtils::get('key');
        $ipHash = CookieUtils::get('ip');
        $deviceHash = CookieUtils::get('device');
        $expire_in = CookieUtils::get('expire_in');

        $user = new User();
        $user->isLogin = false;

        if ($uid === null ||
            $email === null ||
            $key === null ||
            $ipHash === null ||
            $deviceHash === null ||
            $expire_in === null
        ) {
            return $user;
        }

        if ($expire_in < time()) {
            return $user;
        }

        if (Config::obtain('enable_login_bind_ip')) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $node = (new Node())->where('ipv4', $ip)->orWhere('ipv6', $ip)->first();

            if ($node === null && $ipHash !== Hash::ipHash($ip, $uid, $expire_in)) {
                return $user;
            }
        }

        if (Config::obtain('enable_login_bind_device')) {
            $ua = $_SERVER['HTTP_USER_AGENT'];

            if ($deviceHash !== Hash::deviceHash($ua, $uid, $expire_in)) {
                return $user;
            }
        }

        $user = (new User())->find($uid);

        if ($user === null) {
            $user = new User();
            $user->isLogin = false;
            return $user;
        }

        if ($user->email !== $email) {
            $user = new User();
            $user->isLogin = false;
            return $user;
        }

        if (Hash::cookieHash($user->pass, $expire_in) !== $key) {
            $user = new User();
            $user->isLogin = false;
            return $user;
        }

        if (! $this->validateWebSession((int) $user->id)) {
            $invalidUser = new User();
            $invalidUser->isLogin = false;
            return $invalidUser;
        }

        $user->isLogin = true;

        return $user;
    }

    public function logout(): void
    {
        $sid = CookieUtils::get('sid');
        if ($sid !== null && $sid !== '') {
            $tokenHash = hash('sha256', $sid);
            try {
                UserSession::where('token_hash', $tokenHash)->delete();
            } catch (Throwable) {
                // Ignore if user_session table does not exist yet
            }
            Cache::forget('user_session_valid:' . $tokenHash);
        }

        $this->forgetImpersonationCache();

        $this->writeAuthCookies([
            'uid' => '',
            'email' => '',
            'key' => '',
            'ip' => '',
            'device' => '',
            'expire_in' => '',
            'sid' => '',
            self::IMPERSONATE_COOKIE => '',
        ], 1);
    }

    public static function getCurrentTokenHash(): string
    {
        $sid = CookieUtils::get('sid');
        if ($sid === null || $sid === '') {
            return '';
        }

        return hash('sha256', $sid);
    }

    /**
     * @return array{revoked: bool, is_current: bool}
     */
    public static function revokeSessionById(int $userId, int $sessionId): array
    {
        $session = UserSession::where('user_id', $userId)->where('id', $sessionId)->first();
        if ($session === null) {
            return ['revoked' => false, 'is_current' => false];
        }

        $currentHash = self::getCurrentTokenHash();
        $isCurrent = $currentHash !== '' && hash_equals($currentHash, (string) $session->token_hash);
        $tokenHash = (string) $session->token_hash;
        $session->delete();
        Cache::forget('user_session_valid:' . $tokenHash);

        return ['revoked' => true, 'is_current' => $isCurrent];
    }

    public static function revokeOtherSessions(int $userId): int
    {
        $currentHash = self::getCurrentTokenHash();
        $query = UserSession::where('user_id', $userId);
        if ($currentHash !== '') {
            $query->where('token_hash', '!=', $currentHash);
        }

        $sessions = $query->get(['id', 'token_hash']);
        $count = 0;

        foreach ($sessions as $session) {
            Cache::forget('user_session_valid:' . (string) $session->token_hash);
            $count++;
        }

        if ($count > 0) {
            $delQuery = UserSession::where('user_id', $userId);
            if ($currentHash !== '') {
                $delQuery->where('token_hash', '!=', $currentHash);
            }
            $delQuery->delete();
        }

        // Also invalidate pre-migration legacy cookies without sid
        Cache::set('user_legacy_revoked_' . $userId, time(), 86400 * 30);

        return $count;
    }

    private function recordSession(int $userId, string $tokenHash, int $now, int $expireIn): void
    {
        try {
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $parsed = ClientDetector::parseWebAgent($ua);

            $session = new UserSession();
            $session->user_id = $userId;
            $session->token_hash = $tokenHash;
            $session->device_name = mb_substr($parsed['device_name'], 0, 128);
            $session->os = mb_substr($parsed['os'], 0, 64);
            $session->browser = mb_substr($parsed['browser'], 0, 64);
            $session->device_type = mb_substr($parsed['device_type'], 0, 32);
            $session->ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 45);
            $session->user_agent = mb_substr($ua, 0, 512);
            $session->last_active_at = $now;
            $session->expires_at = $expireIn;
            $session->created_at = $now;
            $session->save();

            Cache::set('user_session_valid:' . $tokenHash, [
                'user_id' => $userId,
                'expires_at' => $expireIn,
                'last_active_at' => $now,
            ], 60);
        } catch (Throwable) {
            // Gracefully ignore if migration has not run yet
        }
    }

    private function validateWebSession(int $userId): bool
    {
        $impersonateToken = CookieUtils::get(self::IMPERSONATE_COOKIE);
        if ($impersonateToken !== null && $impersonateToken !== '') {
            return $this->resolveValidImpersonation($userId) !== null;
        }

        $sid = CookieUtils::get('sid');
        if ($sid === null || $sid === '') {
            return Cache::get('user_legacy_revoked_' . $userId) === null;
        }

        $tokenHash = hash('sha256', $sid);
        $now = time();
        $cacheKey = 'user_session_valid:' . $tokenHash;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            if ((int) ($cached['user_id'] ?? 0) !== $userId || (int) ($cached['expires_at'] ?? 0) < $now) {
                return false;
            }

            if ($now - (int) ($cached['last_active_at'] ?? 0) >= 300) {
                try {
                    UserSession::where('token_hash', $tokenHash)->update([
                        'last_active_at' => $now,
                        'ip' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                    ]);
                    $cached['last_active_at'] = $now;
                    Cache::set($cacheKey, $cached, 60);
                } catch (Throwable) {
                    // ignore
                }
            }

            return true;
        }

        try {
            $session = UserSession::where('token_hash', $tokenHash)
                ->where('user_id', $userId)
                ->first();
        } catch (QueryException) {
            // Gracefully allow before DB migration runs
            return true;
        }

        if ($session === null || (int) $session->expires_at < $now) {
            return false;
        }

        if ($now - (int) $session->last_active_at >= 300) {
            $session->last_active_at = $now;
            $session->ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
            $session->save();
        }

        Cache::set($cacheKey, [
            'user_id' => $userId,
            'expires_at' => (int) $session->expires_at,
            'last_active_at' => (int) $session->last_active_at,
        ], 60);

        return true;
    }

    public function isImpersonatingUser(int $userId): bool
    {
        return $this->resolveValidImpersonation($userId) !== null;
    }

    /**
     * @return array{ok: bool, msg: string}
     */
    public function impersonate(User $admin, User $targetUser): array
    {
        if (! $admin->isLogin || ! (bool) $admin->is_admin || (bool) $admin->is_banned) {
            return ['ok' => false, 'msg' => '无权执行该操作'];
        }

        if ($this->isImpersonatingUser((int) $admin->id)) {
            return ['ok' => false, 'msg' => '当前已在代登录状态，请先返回管理员身份'];
        }

        if ((int) $targetUser->id === (int) $admin->id) {
            return ['ok' => false, 'msg' => '不能切换到当前管理员自身'];
        }

        if ((bool) $targetUser->is_admin) {
            return ['ok' => false, 'msg' => '不能切换到其他管理员账号'];
        }

        $now = time();
        $adminSid = (string) (CookieUtils::get('sid') ?? '');
        $adminExpireIn = (int) (CookieUtils::get('expire_in') ?? 0);
        $adminKey = (string) (CookieUtils::get('key') ?? '');
        $adminIp = (string) (CookieUtils::get('ip') ?? '');
        $adminDevice = (string) (CookieUtils::get('device') ?? '');

        if ($adminExpireIn <= $now + 60 || $adminKey === '') {
            return ['ok' => false, 'msg' => '当前管理员登录态即将过期，请重新登录后再试'];
        }

        if (! $this->isAdminSessionStillValid($admin, $adminSid, $adminKey, $adminExpireIn)) {
            return ['ok' => false, 'msg' => '当前管理员会话已失效，请重新登录'];
        }

        $ttl = min(self::IMPERSONATE_TTL, $adminExpireIn - $now);
        $targetExpireIn = $now + $ttl;
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $payload = [
            'admin_uid' => (int) $admin->id,
            'admin_sid' => $adminSid,
            'target_uid' => (int) $targetUser->id,
            'issued_at' => $now,
            'expires_at' => $targetExpireIn,
            'restore_cookie' => [
                'uid' => (string) $admin->id,
                'email' => (string) $admin->email,
                'key' => $adminKey,
                'ip' => $adminIp,
                'device' => $adminDevice,
                'expire_in' => (string) $adminExpireIn,
                'sid' => $adminSid,
            ],
        ];

        if (! Cache::set(self::IMPERSONATE_CACHE_PREFIX . $tokenHash, $payload, $ttl)) {
            return ['ok' => false, 'msg' => '缓存服务不可用，无法切换用户身份'];
        }

        $this->writeAuthCookies([
            'uid' => (string) $targetUser->id,
            'email' => (string) $targetUser->email,
            'key' => Hash::cookieHash($targetUser->pass, $targetExpireIn),
            'ip' => Hash::ipHash($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $targetUser->id, $targetExpireIn),
            'device' => Hash::deviceHash($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', $targetUser->id, $targetExpireIn),
            'expire_in' => (string) $targetExpireIn,
            'sid' => '',
            self::IMPERSONATE_COOKIE => $token,
        ], $targetExpireIn);

        global $user;
        $user = null;

        return ['ok' => true, 'msg' => '已切换为用户 #' . $targetUser->id];
    }

    /**
     * @return array{ok: bool, msg: string, target_uid?: int}
     */
    public function leaveImpersonation(int $currentUserId): array
    {
        $resolved = $this->resolveValidImpersonation($currentUserId);
        if ($resolved === null) {
            return ['ok' => false, 'msg' => '无效的代登录状态，无法返回管理员身份'];
        }

        $payload = $resolved['payload'];
        $restore = $payload['restore_cookie'];
        $targetUid = (int) $payload['target_uid'];
        $adminExpireIn = (int) $restore['expire_in'];

        Cache::forget(self::IMPERSONATE_CACHE_PREFIX . $resolved['tokenHash']);

        $this->writeAuthCookies([
            'uid' => (string) $restore['uid'],
            'email' => (string) $restore['email'],
            'key' => (string) $restore['key'],
            'ip' => (string) $restore['ip'],
            'device' => (string) $restore['device'],
            'expire_in' => (string) $adminExpireIn,
            'sid' => (string) $restore['sid'],
        ], $adminExpireIn);

        $this->writeAuthCookies([
            self::IMPERSONATE_COOKIE => '',
        ], 1);

        global $user;
        $user = null;

        return [
            'ok' => true,
            'msg' => '已返回管理员身份',
            'target_uid' => $targetUid,
        ];
    }

    /**
     * @return array{tokenHash: string, payload: array<string, mixed>, admin: User}|null
     */
    private function resolveValidImpersonation(int $expectedTargetUid): ?array
    {
        if ($expectedTargetUid <= 0) {
            return null;
        }

        $token = CookieUtils::get(self::IMPERSONATE_COOKIE);
        if ($token === null || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return null;
        }

        $tokenHash = hash('sha256', $token);
        $payload = Cache::get(self::IMPERSONATE_CACHE_PREFIX . $tokenHash);
        if (! is_array($payload)) {
            return null;
        }

        $now = time();
        $adminUid = (int) ($payload['admin_uid'] ?? 0);
        $targetUid = (int) ($payload['target_uid'] ?? 0);
        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        $restore = $payload['restore_cookie'] ?? null;

        if (
            $adminUid <= 0 ||
            $targetUid !== $expectedTargetUid ||
            $adminUid === $targetUid ||
            $expiresAt < $now ||
            ! is_array($restore) ||
            (string) ($restore['uid'] ?? '') !== (string) $adminUid
        ) {
            return null;
        }

        $admin = (new User())->find($adminUid);
        if ($admin === null || ! (bool) $admin->is_admin || (bool) $admin->is_banned) {
            return null;
        }

        $adminSid = (string) ($payload['admin_sid'] ?? '');
        $adminKey = (string) ($restore['key'] ?? '');
        $adminExpireIn = (int) ($restore['expire_in'] ?? 0);

        if (! $this->isAdminSessionStillValid($admin, $adminSid, $adminKey, $adminExpireIn)) {
            return null;
        }

        return [
            'tokenHash' => $tokenHash,
            'payload' => $payload,
            'admin' => $admin,
        ];
    }

    private function isAdminSessionStillValid(User $admin, string $adminSid, string $adminKey, int $adminExpireIn): bool
    {
        if ($adminExpireIn < time() || $adminKey === '') {
            return false;
        }

        if (! hash_equals(Hash::cookieHash($admin->pass, $adminExpireIn), $adminKey)) {
            return false;
        }

        return $this->isSessionTokenActive((int) $admin->id, $adminSid);
    }

    private function isSessionTokenActive(int $userId, string $sid): bool
    {
        if ($sid === '') {
            return Cache::get('user_legacy_revoked_' . $userId) === null;
        }

        $tokenHash = hash('sha256', $sid);
        $now = time();
        $cacheKey = 'user_session_valid:' . $tokenHash;
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return (int) ($cached['user_id'] ?? 0) === $userId
                && (int) ($cached['expires_at'] ?? 0) >= $now;
        }

        try {
            $session = UserSession::where('token_hash', $tokenHash)
                ->where('user_id', $userId)
                ->first();
        } catch (QueryException) {
            return true;
        }

        if ($session === null || (int) $session->expires_at < $now) {
            return false;
        }

        Cache::set($cacheKey, [
            'user_id' => $userId,
            'expires_at' => (int) $session->expires_at,
            'last_active_at' => (int) $session->last_active_at,
        ], 60);

        return true;
    }

    private function forgetImpersonationCache(): void
    {
        $token = CookieUtils::get(self::IMPERSONATE_COOKIE);
        if ($token !== null && preg_match('/^[a-f0-9]{64}$/', $token) === 1) {
            Cache::forget(self::IMPERSONATE_CACHE_PREFIX . hash('sha256', $token));
        }
    }

    /**
     * @param array<string, string> $cookies
     */
    private function writeAuthCookies(array $cookies, int $expireIn): void
    {
        if (! headers_sent()) {
            CookieUtils::setWithDomain($cookies, $expireIn, $_SERVER['HTTP_HOST'] ?? '');
        }

        foreach ($cookies as $cookieKey => $cookieValue) {
            if ($expireIn <= time()) {
                unset($_COOKIE[$cookieKey]);
            } else {
                $_COOKIE[$cookieKey] = $cookieValue;
            }
        }
    }
}
