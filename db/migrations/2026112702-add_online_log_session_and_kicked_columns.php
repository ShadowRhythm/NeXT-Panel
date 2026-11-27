<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();

        $hasSessionTime = $pdo->query("SHOW COLUMNS FROM `online_log` LIKE 'session_time'")->fetch();
        if (! $hasSessionTime) {
            $pdo->exec('ALTER TABLE `online_log` ADD COLUMN `session_time` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `first_time`;');
        }

        $hasKickedAt = $pdo->query("SHOW COLUMNS FROM `online_log` LIKE 'kicked_at'")->fetch();
        if (! $hasKickedAt) {
            $pdo->exec('ALTER TABLE `online_log` ADD COLUMN `kicked_at` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `last_time`;');
        }

        return 2026112702;
    }

    public function down(): int
    {
        $pdo = DB::getPdo();

        $hasKickedAt = $pdo->query("SHOW COLUMNS FROM `online_log` LIKE 'kicked_at'")->fetch();
        if ($hasKickedAt) {
            $pdo->exec('ALTER TABLE `online_log` DROP COLUMN `kicked_at`;');
        }

        $hasSessionTime = $pdo->query("SHOW COLUMNS FROM `online_log` LIKE 'session_time'")->fetch();
        if ($hasSessionTime) {
            $pdo->exec('ALTER TABLE `online_log` DROP COLUMN `session_time`;');
        }

        return 2026112701;
    }
};
