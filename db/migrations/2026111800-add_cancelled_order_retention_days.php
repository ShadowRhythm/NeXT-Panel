<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();
        $exists = $pdo->query("SELECT COUNT(*) FROM `config` WHERE `item` = 'cancelled_order_retention_days'")->fetchColumn();

        if ((int) $exists === 0) {
            $pdo->exec("
                INSERT INTO `config` (`item`, `value`, `class`, `is_public`, `type`, `default`, `mark`)
                VALUES ('cancelled_order_retention_days', '30', 'cron', 0, 'int', '30', '已取消订单及账单保留天数(天，0为不清理)');
            ");
        }

        return 2026111800;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            DELETE FROM `config` WHERE `item` = 'cancelled_order_retention_days';
        ");

        return 2026111102;
    }
};
