<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec('
        ALTER TABLE `user` ADD KEY IF NOT EXISTS `transfer_today` (`transfer_today`);
        ALTER TABLE `user` ADD KEY IF NOT EXISTS `is_inactive` (`is_inactive`);
        ALTER TABLE `user` ADD KEY IF NOT EXISTS `last_use_time` (`last_use_time`);
        ALTER TABLE `user` ADD KEY IF NOT EXISTS `reg_date` (`reg_date`);
        ALTER TABLE `hourly_usage` ADD KEY IF NOT EXISTS `user_id_date` (`user_id`, `date`);');

        return 2026111901;
    }

    public function down(): int
    {
        DB::getPdo()->exec('
        ALTER TABLE `user` DROP KEY IF EXISTS `transfer_today`;
        ALTER TABLE `user` DROP KEY IF EXISTS `is_inactive`;
        ALTER TABLE `user` DROP KEY IF EXISTS `last_use_time`;
        ALTER TABLE `user` DROP KEY IF EXISTS `reg_date`;
        ALTER TABLE `hourly_usage` DROP KEY IF EXISTS `user_id_date`;');

        return 2026111900;
    }
};
