<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        $pdo = DB::getPdo();
        $settingsFile = defined('BASE_PATH') ? BASE_PATH . '/config/settings.json' : dirname(__DIR__, 2) . '/config/settings.json';

        if (file_exists($settingsFile)) {
            $settings = json_decode((string) file_get_contents($settingsFile), true);
            if (is_array($settings)) {
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM `config` WHERE `item` = :item');
                $insertStmt = $pdo->prepare('
                    INSERT INTO `config` (`item`, `value`, `class`, `is_public`, `type`, `default`, `mark`)
                    VALUES (:item, :value, :class, :is_public, :type, :default, :mark)
                ');

                foreach ($settings as $setting) {
                    $item = $setting['item'] ?? '';
                    if ($item === '') {
                        continue;
                    }

                    $checkStmt->execute(['item' => $item]);
                    if ((int) $checkStmt->fetchColumn() === 0) {
                        $insertStmt->execute([
                            'item' => $item,
                            'value' => (string) ($setting['value'] ?? $setting['default'] ?? ''),
                            'class' => (string) ($setting['class'] ?? 'system'),
                            'is_public' => (int) ($setting['is_public'] ?? 0),
                            'type' => (string) ($setting['type'] ?? 'string'),
                            'default' => (string) ($setting['default'] ?? ''),
                            'mark' => (string) ($setting['mark'] ?? ''),
                        ]);
                    }
                }
            }
        }

        return 2026112300;
    }

    public function down(): int
    {
        return 2026111920;
    }
};
