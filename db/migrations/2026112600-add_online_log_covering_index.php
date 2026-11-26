<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec('
            ALTER TABLE `online_log` ADD KEY IF NOT EXISTS `idx_last_time_user_id` (`last_time`, `user_id`);
        ');

        return 2026112600;
    }

    public function down(): int
    {
        DB::getPdo()->exec('
            ALTER TABLE `online_log` DROP KEY IF EXISTS `idx_last_time_user_id`;
        ');

        return 2026112500;
    }
};
