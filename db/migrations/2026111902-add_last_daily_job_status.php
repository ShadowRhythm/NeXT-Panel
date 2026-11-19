<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();
        $exists = $pdo->query("SELECT COUNT(*) FROM `config` WHERE `item` = 'last_daily_job_status'")->fetchColumn();

        if ((int) $exists === 0) {
            $pdo->exec("
                INSERT INTO `config` (`item`, `value`, `class`, `is_public`, `type`, `default`, `mark`)
                VALUES ('last_daily_job_status', 'success', 'system', 0, 'string', 'success', '上次每日任务执行状态');
            ");
        }

        return 2026111902;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            DELETE FROM `config` WHERE `item` = 'last_daily_job_status';
        ");

        return 2026111901;
    }
};
