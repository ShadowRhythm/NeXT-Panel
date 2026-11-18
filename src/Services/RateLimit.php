<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use RateLimit\Exception\LimitExceeded;
use RateLimit\Rate;
use RateLimit\RedisRateLimiter;
use Redis;
use Throwable;

final class RateLimit
{
    private Redis $redis;

    public function __construct()
    {
        $this->redis = (new Cache())->initRedis();
    }

    public function checkRateLimit(string $limit_type, string $value): bool
    {
        $limiter = match ($limit_type) {
            'sub_ip' => $this->getSubIpLimiter(),
            'sub_token' => $this->getSubTokenLimiter(),
            'webapi_ip' => $this->getWebApiIpLimiter(),
            'webapi_key' => $this->getWebApiKeyLimiter(),
            'user_api_ip' => $this->getUserApiIpLimiter(),
            'user_api_key' => $this->getUserApiKeyLimiter(),
            'admin_api_ip' => $this->getAdminApiIpLimiter(),
            'admin_api_key' => $this->getAdminApiKeyLimiter(),
            'node_api_ip' => $this->getNodeApiIpLimiter(),
            'node_api_key' => $this->getNodeApiKeyLimiter(),
            'email_request_ip' => $this->getEmailIpLimiter(),
            'email_request_address' => $this->getEmailAddressLimiter(),
            'login_ip' => $this->getLoginIpLimiter(),
            'register_ip' => $this->getRegisterIpLimiter(),
            'ticket' => $this->getTicketLimiter(),
            default => null,
        };

        if ($limiter === null) {
            return false;
        }

        try {
            $limiter->limit($value);
        } catch (LimitExceeded) {
            return false;
        } catch (Throwable) {
            return true;
        }

        return true;
    }

    public function getSubIpLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_sub_ip')),
            $this->redis,
            'sspanel_sub_ip:'
        );
    }

    public function getSubTokenLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_sub')),
            $this->redis,
            'sspanel_sub_token:'
        );
    }

    public function getWebApiIpLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_webapi_ip')),
            $this->redis,
            'sspanel_webapi_ip:'
        );
    }

    public function getWebApiKeyLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_webapi')),
            $this->redis,
            'sspanel_webapi_key:'
        );
    }

    public function getUserApiIpLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_user_api_ip')),
            $this->redis,
            'sspanel_user_api_ip:'
        );
    }

    public function getUserApiKeyLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_user_api')),
            $this->redis,
            'sspanel_user_api_key:'
        );
    }

    public function getAdminApiIpLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_admin_api_ip')),
            $this->redis,
            'sspanel_admin_api_ip:'
        );
    }

    public function getAdminApiKeyLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_admin_api')),
            $this->redis,
            'sspanel_admin_api_key:'
        );
    }

    public function getNodeApiIpLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_node_api_ip')),
            $this->redis,
            'sspanel_node_api_ip:'
        );
    }

    public function getNodeApiKeyLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perMinute((int) Config::obtain('rate_limit_node_api')),
            $this->redis,
            'sspanel_node_api_key:'
        );
    }

    public function getEmailIpLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perHour(Config::obtain('email_request_ip_limit')),
            $this->redis,
            'sspanel_email_request_ip:'
        );
    }

    public function getEmailAddressLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::perHour(Config::obtain('email_request_address_limit')),
            $this->redis,
            'sspanel_email_request_address:'
        );
    }

    public function getTicketLimiter(): RedisRateLimiter
    {
        return new RedisRateLimiter(
            Rate::custom(Config::obtain('ticket_limit'), 2592000),
            $this->redis,
            'sspanel_ticket:'
        );
    }

    public function getLoginIpLimiter(): RedisRateLimiter
    {
        $rate = (int) (Config::obtain('rate_limit_login_ip') ?? 15);
        if ($rate <= 0) {
            $rate = 15;
        }

        return new RedisRateLimiter(
            Rate::perMinute($rate),
            $this->redis,
            'sspanel_login_ip:'
        );
    }

    public function getRegisterIpLimiter(): RedisRateLimiter
    {
        $rate = (int) (Config::obtain('rate_limit_register_ip') ?? 10);
        if ($rate <= 0) {
            $rate = 10;
        }

        return new RedisRateLimiter(
            Rate::perHour($rate),
            $this->redis,
            'sspanel_register_ip:'
        );
    }
}
