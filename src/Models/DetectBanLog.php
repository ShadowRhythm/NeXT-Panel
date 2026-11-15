<?php

declare(strict_types=1);

namespace App\Models;

use App\Utils\Tools;
use Illuminate\Database\Query\Builder;
use function is_numeric;
use function strtotime;

/**
 * @property int    $id                封禁记录ID
 * @property int    $user_id           用户ID
 * @property int    $detect_number     本次违规次数
 * @property int    $ban_time          封禁时长
 * @property int    $start_time        封禁开始时间
 * @property int    $end_time          封禁结束时间
 * @property int    $all_detect_number 累计违规次数
 *
 * @mixin Builder
 */
final class DetectBanLog extends Model
{
    protected $connection = 'default';
    protected $table = 'detect_ban_log';

    /**
     * 封禁结束时间
     */
    public function banEndTime(): string
    {
        $endTime = is_numeric($this->end_time) ? (int) $this->end_time : strtotime((string) $this->end_time);

        return Tools::toDateTime($endTime + (int) $this->ban_time * 60);
    }
}
