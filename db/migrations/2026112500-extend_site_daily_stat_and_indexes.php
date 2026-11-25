<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            ALTER TABLE `site_daily_stat`
                ADD COLUMN IF NOT EXISTS `hourly_traffic` longtext NULL DEFAULT NULL COMMENT '当日24小时全站流量分布(JSON数组)' AFTER `class_snapshot`,
                ADD COLUMN IF NOT EXISTS `client_snapshot` longtext NULL DEFAULT NULL COMMENT '当日订阅客户端分布快照(JSON对象)' AFTER `hourly_traffic`,
                ADD COLUMN IF NOT EXISTS `checkin_users` int(10) unsigned NOT NULL DEFAULT 0 COMMENT '当日签到人数' AFTER `client_snapshot`;

            ALTER TABLE `paylist` ADD KEY IF NOT EXISTS `status_datetime` (`status`, `datetime`);
            ALTER TABLE `order` ADD KEY IF NOT EXISTS `status_create_time` (`status`, `create_time`);
            ALTER TABLE `payback` ADD KEY IF NOT EXISTS `datetime` (`datetime`);
            ALTER TABLE `user` ADD KEY IF NOT EXISTS `class_expire` (`class_expire`);
            ALTER TABLE `user` ADD KEY IF NOT EXISTS `last_check_in_time` (`last_check_in_time`);
        ");

        return 2026112500;
    }

    public function down(): int
    {
        DB::getPdo()->exec('
            ALTER TABLE `user` DROP KEY IF EXISTS `last_check_in_time`;
            ALTER TABLE `user` DROP KEY IF EXISTS `class_expire`;
            ALTER TABLE `payback` DROP KEY IF EXISTS `datetime`;
            ALTER TABLE `order` DROP KEY IF EXISTS `status_create_time`;
            ALTER TABLE `paylist` DROP KEY IF EXISTS `status_datetime`;

            ALTER TABLE `site_daily_stat`
                DROP COLUMN IF EXISTS `checkin_users`,
                DROP COLUMN IF EXISTS `client_snapshot`,
                DROP COLUMN IF EXISTS `hourly_traffic`;
        ');

        return 2026112300;
    }
};
