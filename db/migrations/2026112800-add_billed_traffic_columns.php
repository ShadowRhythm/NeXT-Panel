<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();

        if (! $pdo->query("SHOW COLUMNS FROM `hourly_usage` LIKE 'billed_usage'")->fetch()) {
            $pdo->exec('ALTER TABLE `hourly_usage` ADD COLUMN `billed_usage` LONGTEXT NULL DEFAULT NULL AFTER `usage`;');
        }

        if (! $pdo->query("SHOW COLUMNS FROM `user_traffic_stat` LIKE 'daily_billed_usage'")->fetch()) {
            $pdo->exec('ALTER TABLE `user_traffic_stat` ADD COLUMN `daily_billed_usage` LONGTEXT NULL DEFAULT NULL AFTER `daily_usage`;');
        }

        if (! $pdo->query("SHOW COLUMNS FROM `user_traffic_stat` LIKE 'monthly_billed_usage'")->fetch()) {
            $pdo->exec('ALTER TABLE `user_traffic_stat` ADD COLUMN `monthly_billed_usage` LONGTEXT NULL DEFAULT NULL AFTER `monthly_usage`;');
        }

        return 2026112800;
    }

    public function down(): int
    {
        $pdo = DB::getPdo();

        if ($pdo->query("SHOW COLUMNS FROM `user_traffic_stat` LIKE 'monthly_billed_usage'")->fetch()) {
            $pdo->exec('ALTER TABLE `user_traffic_stat` DROP COLUMN `monthly_billed_usage`;');
        }

        if ($pdo->query("SHOW COLUMNS FROM `user_traffic_stat` LIKE 'daily_billed_usage'")->fetch()) {
            $pdo->exec('ALTER TABLE `user_traffic_stat` DROP COLUMN `daily_billed_usage`;');
        }

        if ($pdo->query("SHOW COLUMNS FROM `hourly_usage` LIKE 'billed_usage'")->fetch()) {
            $pdo->exec('ALTER TABLE `hourly_usage` DROP COLUMN `billed_usage`;');
        }

        return 2026112702;
    }
};
