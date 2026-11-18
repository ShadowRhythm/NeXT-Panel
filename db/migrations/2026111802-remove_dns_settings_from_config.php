<?php

declare(strict_types=1);

use App\Interfaces\MigrationInterface;
use App\Services\DB;

return new class() implements MigrationInterface {
    public function up(): int
    {
        DB::getPdo()->exec("
            DELETE FROM `config` WHERE `item` IN (
                'dns_select',
                'dns_server_853',
                'dns_server_port_853',
                'dns_type_853',
                'dns_server_443',
                'dns_server_port_443',
                'dns_path_443',
                'dns_type_443'
            );
        ");

        return 2026111802;
    }

    public function down(): int
    {
        return 2026111801;
    }
};
