<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            CREATE TABLE IF NOT EXISTS `site_daily_stat` (
                `id`             bigint unsigned NOT NULL AUTO_INCREMENT,
                `date`           date            NOT NULL COMMENT '统计日期，每天一行',
                `income`         decimal(10,2)   NOT NULL DEFAULT 0.00 COMMENT '当日已支付流水',
                `new_users`      int unsigned    NOT NULL DEFAULT 0    COMMENT '当日新注册用户数',
                `active_users`   int unsigned    NOT NULL DEFAULT 0    COMMENT '活跃用户数（is_inactive=0快照）',
                `traffic`        bigint unsigned NOT NULL DEFAULT 0    COMMENT '当日全站流量 bytes',
                `class_snapshot` json            NOT NULL DEFAULT (JSON_OBJECT()) COMMENT '各等级用户数快照 {\"0\":150,\"1\":80}',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_date` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        return 2026111900;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            DROP TABLE IF EXISTS `site_daily_stat`;
        ");

        return 2026111899;
    }
};
