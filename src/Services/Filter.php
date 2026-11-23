<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Config;
use App\Utils\Tools;
use function explode;
use function in_array;
use function is_array;

final class Filter
{
    public static function checkEmailFilter(string $email): bool
    {
        if (! Tools::isEmail($email)) {
            return false;
        }

        $mail_filter = (int) (Config::obtain('mail_filter') ?: ($_ENV['mail_filter'] ?? 0));
        if ($mail_filter === 0) {
            return true;
        }

        $parts = explode('@', $email);
        $mail_suffix = $parts[1] ?? '';
        if ($mail_suffix === '') {
            return false;
        }

        $mail_filter_list = Config::obtain('mail_filter_list');
        if (empty($mail_filter_list) && ! empty($_ENV['mail_filter_list'])) {
            $mail_filter_list = $_ENV['mail_filter_list'];
        }

        if (! is_array($mail_filter_list)) {
            $mail_filter_list = Config::parseArrayValue($mail_filter_list);
        }

        return match ($mail_filter) {
            1 => in_array($mail_suffix, $mail_filter_list, true),
            2 => ! in_array($mail_suffix, $mail_filter_list, true),
            default => true,
        };
    }
}
