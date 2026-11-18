<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            CREATE TABLE IF NOT EXISTS `user_traffic_stat` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
                `user_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '用户ID',
                `daily_last_date` date NOT NULL DEFAULT '1970-01-01' COMMENT 'daily最后更新日期',
                `daily_usage` longtext NOT NULL DEFAULT '[]' COMMENT '过去30天用量(JSON数组)' CHECK (json_valid(`daily_usage`)),
                `monthly_last_month` varchar(7) NOT NULL DEFAULT '1970-01' COMMENT 'monthly最后更新月份(YYYY-MM)',
                `monthly_usage` longtext NOT NULL DEFAULT '[]' COMMENT '过去12个月用量(JSON数组)' CHECK (json_valid(`monthly_usage`)),
                PRIMARY KEY (`id`),
                UNIQUE KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        return 2026111801;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            DROP TABLE IF EXISTS `user_traffic_stat`;
        ");

        return 2026111800;
    }
};
