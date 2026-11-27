<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $token_hash
 * @property string $device_name
 * @property string $os
 * @property string $browser
 * @property string $device_type
 * @property string $ip
 * @property string $user_agent
 * @property int    $last_active_at
 * @property int    $expires_at
 * @property int    $created_at
 *
 * @mixin Builder
 */
final class UserSession extends Model
{
    protected $connection = 'default';
    protected $table = 'user_session';

    protected $casts = [
        'user_id' => 'int',
        'last_active_at' => 'int',
        'expires_at' => 'int',
        'created_at' => 'int',
    ];
}
