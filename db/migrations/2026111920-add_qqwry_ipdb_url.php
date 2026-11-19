<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();
        $exists = $pdo->query("SELECT COUNT(*) FROM `config` WHERE `item` = 'qqwry_ipdb_url'")->fetchColumn();

        if ((int) $exists === 0) {
            $pdo->exec("
                INSERT INTO `config` (`item`, `value`, `class`, `is_public`, `type`, `default`, `mark`)
                VALUES ('qqwry_ipdb_url', 'https://github.com/nmgliangwei/qqwry.ipdb/releases/latest/download/qqwry.ipdb', 'system', 0, 'string', 'https://github.com/nmgliangwei/qqwry.ipdb/releases/latest/download/qqwry.ipdb', 'qqwry.ipdb 下载地址（留空则不自动更新）');
            ");
        }

        return 2026111920;
    }

    public function down(): int
    {
        DB::getPdo()->exec("
            DELETE FROM `config` WHERE `item` = 'qqwry_ipdb_url';
        ");

        return 2026111902;
    }
};
