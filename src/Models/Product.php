<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\I18n;
use Illuminate\Database\Query\Builder;
use function is_object;
use function json_decode;
use function preg_match;
use function strtolower;
use function trim;
use function ucfirst;

/**
 * @property int    $id          商品ID
 * @property string $type        类型
 * @property string $name        名称
 * @property float  $price       售价
 * @property string $content     内容
 * @property string $limit       购买限制
 * @property int    $status      销售状态
 * @property int    $create_time 创建时间
 * @property int    $update_time 更新时间
 * @property int    $sale_count  累计销量
 * @property int    $stock       库存
 *
 * @mixin Builder
 */
final class Product extends Model
{
    protected $connection = 'default';
    protected $table = 'product';

    /**
     * 商品状态
     */
    public function status(): string
    {
        return $this->status ? '正常' : '下架';
    }

    /**
     * 商品类型
     */
    public function type(): string
    {
        return match ($this->type) {
            'tabp' => I18n::trans('user.product_type_tabp'),
            'time' => I18n::trans('user.product_type_time'),
            'bandwidth' => I18n::trans('user.product_type_bandwidth'),
            default => I18n::trans('user.product_type_other'),
        };
    }

    /**
     * 商品库存
     */
    public function stock(): string|int
    {
        return $this->stock < 0 ? '无限制' : $this->stock;
    }

    public function getContentObject(): object
    {
        $decoded = json_decode((string) $this->content);

        return is_object($decoded) ? $decoded : (object) [];
    }

    public function getTierLevel(): int
    {
        $content = $this->getContentObject();
        if (isset($content->tier_level) && (int) $content->tier_level > 0) {
            return (int) $content->tier_level;
        }

        return (int) ($content->class ?? 0);
    }

    public function getCycleDays(): int
    {
        $content = $this->getContentObject();

        return max(1, (int) ($content->class_time ?? $content->time ?? 30));
    }

    public function getCycleKey(): string
    {
        $content = $this->getContentObject();
        $explicit = trim((string) ($content->cycle_label ?? ''));
        if ($explicit !== '' && $explicit !== 'auto') {
            return $explicit;
        }

        $days = $this->getCycleDays();
        if ($days <= 31) {
            return 'monthly';
        }
        if ($days >= 85 && $days <= 95) {
            return 'quarterly';
        }
        if ($days >= 175 && $days <= 186) {
            return 'half_yearly';
        }
        if ($days >= 360) {
            return 'yearly';
        }

        return 'custom';
    }

    public function getPlanGroup(): string
    {
        $content = $this->getContentObject();
        $explicit = trim((string) ($content->plan_group ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $name = trim((string) $this->name);
        if (preg_match('/^(Starter|Basic|Standard|Pro|Ultra)\s*[-·]/iu', $name, $matches)) {
            return ucfirst(strtolower($matches[1]));
        }

        return 'Lv. ' . (int) ($content->class ?? 0);
    }

    public static function getTierFromContent(?string $jsonContent): int
    {
        $content = json_decode((string) $jsonContent);
        if (! is_object($content)) {
            return 0;
        }

        if (isset($content->tier_level) && (int) $content->tier_level > 0) {
            return (int) $content->tier_level;
        }

        return (int) ($content->class ?? 0);
    }
}
