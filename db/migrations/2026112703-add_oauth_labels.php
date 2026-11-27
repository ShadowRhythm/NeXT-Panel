<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();

        $pdo->exec("
            ALTER TABLE `user`
                ADD COLUMN IF NOT EXISTS `google_label` VARCHAR(191) NULL DEFAULT NULL COMMENT 'Google OAuth Display Label',
                ADD COLUMN IF NOT EXISTS `github_label` VARCHAR(191) NULL DEFAULT NULL COMMENT 'GitHub OAuth Display Label';
        ");

        return 2026112703;
    }

    public function down(): int
    {
        $pdo = DB::getPdo();

        $pdo->exec('
            ALTER TABLE `user`
                DROP COLUMN IF EXISTS `google_label`,
                DROP COLUMN IF EXISTS `github_label`;
        ');

        return 2026112702;
    }
};
