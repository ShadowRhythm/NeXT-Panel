<?php

declare(strict_types=1);

namespace App\Services\Subscribe;

use function array_filter;
use function array_map;
use function array_values;
use function explode;
use function filter_var;
use function is_array;
use function is_string;
use function trim;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

abstract class Base
{
    abstract public function getContent($user): string;

    /**
     * Format a server address for RFC 3986 URI authority (wrapping IPv6 in brackets).
     */
    protected static function formatUriHost(string $server): string
    {
        $raw_server = trim($server);
        $unbracketed = trim($raw_server, '[]');

        return filter_var($unbracketed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? '[' . $unbracketed . ']'
            : $raw_server;
    }

    /**
     * Normalize custom_config ALPN values (array or comma-separated string) into a list.
     *
     * @return list<string>
     */
    protected static function parseAlpn(mixed $alpn): array
    {
        if (is_array($alpn)) {
            $list = array_map(static fn ($item): string => trim((string) $item), $alpn);

            return array_values(array_filter($list, static fn (string $item): bool => $item !== ''));
        }

        if (is_string($alpn) && $alpn !== '') {
            $list = array_map('trim', explode(',', $alpn));

            return array_values(array_filter($list, static fn (string $item): bool => $item !== ''));
        }

        return [];
    }
}
