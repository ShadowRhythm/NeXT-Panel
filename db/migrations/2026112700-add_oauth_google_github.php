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
                ADD COLUMN IF NOT EXISTS `google_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Google OAuth ID',
                ADD COLUMN IF NOT EXISTS `github_id` VARCHAR(64) NULL DEFAULT NULL COMMENT 'GitHub OAuth ID';
        ");

        $pdo->exec('
            ALTER TABLE `user`
                ADD UNIQUE KEY IF NOT EXISTS `uniq_user_google_id` (`google_id`),
                ADD UNIQUE KEY IF NOT EXISTS `uniq_user_github_id` (`github_id`);
        ');

        $settings = [
            ['enable_google_oauth', '0', 'reg', 1, 'bool', '0', '启用 Google OAuth 登录'],
            ['google_client_id', '', 'reg', 0, 'string', '', 'Google OAuth Client ID'],
            ['google_client_secret', '', 'reg', 0, 'string', '', 'Google OAuth Client Secret'],
            ['enable_github_oauth', '0', 'reg', 1, 'bool', '0', '启用 GitHub OAuth 登录'],
            ['github_client_id', '', 'reg', 0, 'string', '', 'GitHub OAuth Client ID'],
            ['github_client_secret', '', 'reg', 0, 'string', '', 'GitHub OAuth Client Secret'],
        ];

        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM `config` WHERE `item` = :item');
        $insertStmt = $pdo->prepare('
            INSERT INTO `config` (`item`, `value`, `class`, `is_public`, `type`, `default`, `mark`)
            VALUES (:item, :value, :class, :is_public, :type, :default, :mark)
        ');

        foreach ($settings as [$item, $value, $class, $isPublic, $type, $default, $mark]) {
            $checkStmt->execute(['item' => $item]);
            if ((int) $checkStmt->fetchColumn() === 0) {
                $insertStmt->execute([
                    'item' => $item,
                    'value' => $value,
                    'class' => $class,
                    'is_public' => $isPublic,
                    'type' => $type,
                    'default' => $default,
                    'mark' => $mark,
                ]);
            }
        }

        return 2026112700;
    }

    public function down(): int
    {
        $pdo = DB::getPdo();

        $pdo->exec('
            ALTER TABLE `user`
                DROP KEY IF EXISTS `uniq_user_google_id`,
                DROP KEY IF EXISTS `uniq_user_github_id`;
        ');

        $pdo->exec('
            ALTER TABLE `user`
                DROP COLUMN IF EXISTS `google_id`,
                DROP COLUMN IF EXISTS `github_id`;
        ');

        $pdo->exec("
            DELETE FROM `config`
            WHERE `item` IN (
                'enable_google_oauth',
                'google_client_id',
                'google_client_secret',
                'enable_github_oauth',
                'github_client_id',
                'github_client_secret'
            );
        ");

        return 2026112600;
    }
};
