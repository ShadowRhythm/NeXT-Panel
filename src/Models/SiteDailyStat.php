<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Query\Builder;
use function array_fill;
use function json_encode;

/**
 * @property int         $id              记录ID
 * @property string      $date            统计日期，每天一行
 * @property float       $income          当日已支付流水
 * @property int         $new_users       当日新注册用户数
 * @property int         $active_users    活跃用户数（is_inactive=0快照）
 * @property int         $traffic         当日全站流量 bytes
 * @property string      $class_snapshot  各等级用户数快照 {"0":150,"1":80}
 * @property string|null $hourly_traffic  当日24小时全站流量分布 JSON数组
 * @property string|null $client_snapshot 当日订阅客户端分布快照 JSON对象
 * @property int         $checkin_users   当日签到人数
 *
 * @mixin Builder
 */
final class SiteDailyStat extends Model
{
    protected $connection = 'default';
    protected $table = 'site_daily_stat';

    public static function getOrCreate(string $date): self
    {
        $stat = self::where('date', $date)->first();

        if ($stat === null) {
            $stat = new self();
            $stat->date = $date;
            $stat->income = 0.00;
            $stat->new_users = 0;
            $stat->active_users = 0;
            $stat->traffic = 0;
            $stat->class_snapshot = json_encode((object) []);
            $stat->hourly_traffic = json_encode(array_fill(0, 24, 0));
            $stat->client_snapshot = json_encode((object) []);
            $stat->checkin_users = 0;
            $stat->save();
        }

        return $stat;
    }
}
