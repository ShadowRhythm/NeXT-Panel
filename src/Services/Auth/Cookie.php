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
use function is_array;
use function mb_substr;
use function random_bytes;
use function time;

final class Cookie extends Base
{
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

        unset($_COOKIE['sid']);

        CookieUtils::setWithDomain([
            'uid' => '',
            'email' => '',
            'key' => '',
            'ip' => '',
            'device' => '',
            'expire_in' => '',
            'sid' => '',
        ], 0, $_SERVER['HTTP_HOST'] ?? '');
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
}
