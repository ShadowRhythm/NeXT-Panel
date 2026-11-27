<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $ip
 * @property string $alias
 * @property int    $updated_at
 *
 * @mixin Builder
 */
final class UserDeviceAlias extends Model
{
    protected $connection = 'default';
    protected $table = 'user_device_alias';

    protected $casts = [
        'user_id' => 'int',
        'updated_at' => 'int',
    ];
}
