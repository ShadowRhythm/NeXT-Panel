<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_session` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `token_hash` CHAR(64) NOT NULL,
                `device_name` VARCHAR(128) NOT NULL DEFAULT '',
                `os` VARCHAR(64) NOT NULL DEFAULT '',
                `browser` VARCHAR(64) NOT NULL DEFAULT '',
                `device_type` VARCHAR(32) NOT NULL DEFAULT 'desktop',
                `ip` VARCHAR(45) NOT NULL DEFAULT '',
                `user_agent` VARCHAR(512) NOT NULL DEFAULT '',
                `last_active_at` INT UNSIGNED NOT NULL DEFAULT 0,
                `expires_at` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_session_token_hash` (`token_hash`),
                KEY `idx_session_user_expires` (`user_id`, `expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_device_alias` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL,
                `ip` VARCHAR(64) NOT NULL,
                `alias` VARCHAR(64) NOT NULL DEFAULT '',
                `updated_at` INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_device_alias_user_ip` (`user_id`, `ip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        return 2026112701;
    }

    public function down(): int
    {
        $pdo = DB::getPdo();
        $pdo->exec('DROP TABLE IF EXISTS `user_device_alias`;');
        $pdo->exec('DROP TABLE IF EXISTS `user_session`;');

        return 2026112700;
    }
};
