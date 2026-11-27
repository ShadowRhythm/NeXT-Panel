<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\Product;
use App\Services\I18n;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function count;
use function json_decode;
use function json_encode;
use function max;
use function md5;
use function min;
use function round;
use function strtolower;
use function substr;
use function trim;
use function usort;
use const JSON_UNESCAPED_UNICODE;

final class ProductController extends BaseController
{
    private const CYCLE_ORDER = [
        'monthly' => 1,
        'quarterly' => 2,
        'half_yearly' => 3,
        'yearly' => 4,
        'custom' => 5,
    ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $locale = I18n::getCurrentLocale($this->user);

        $tabps = (new Product())->where('status', '1')
            ->where('type', 'tabp')
            ->orderBy('id')
            ->get();

        $bandwidths = (new Product())->where('status', '1')
            ->where('type', 'bandwidth')
            ->orderBy('id')
            ->get();

        $times = (new Product())->where('status', '1')
            ->where('type', 'time')
            ->orderBy('id')
            ->get();

        $enableTabpUpgrade = (bool) Config::obtain('enable_tabp_upgrade');
        $activeTabpInfo = OrderController::getActiveTabpUpgradeInfo($this->user);
        $hasActiveTabp = $activeTabpInfo !== null;
        $activeTabpOrder = $hasActiveTabp ? $activeTabpInfo['order'] : null;
        $credit = $hasActiveTabp ? (float) $activeTabpInfo['credit_amount'] : 0.0;
        $activeTier = $hasActiveTabp ? (int) $activeTabpInfo['tier_level'] : 0;

        $groupedRaw = [];
        $activeProductAvailable = false;

        foreach ($tabps as $tabp) {
            $contentObj = $tabp->getContentObject();
            $tabp->content = $contentObj;

            $tier = $tabp->getTierLevel();
            $groupName = $tabp->getPlanGroup();
            $cycleKey = $tabp->getCycleKey();
            $days = $tabp->getCycleDays();
            $canUpgrade = $hasActiveTabp && $enableTabpUpgrade && OrderController::canUpgradeProduct($tabp, $activeTabpOrder);

            $upgradeCredit = 0.0;
            $upgradePrice = (float) $tabp->price;
            if ($canUpgrade) {
                $upgradeCredit = min($credit, (float) $tabp->price);
                $upgradePrice = max(0.0, round((float) $tabp->price - $upgradeCredit, 2));
            }

            $actionMode = 'new';
            if ($hasActiveTabp) {
                if ((int) $tabp->id === (int) $activeTabpOrder->product_id) {
                    $actionMode = 'renew';
                    $activeProductAvailable = ((int) $tabp->stock === -1 || (int) $tabp->stock > 0);
                } elseif ($tier > $activeTier) {
                    $actionMode = $canUpgrade ? 'upgrade' : 'queued';
                } elseif ($tier === $activeTier) {
                    $actionMode = 'cycle_switch';
                } else {
                    $actionMode = 'downgrade';
                }
            }

            $tabp->can_upgrade = $canUpgrade;
            $tabp->upgrade_credit = $upgradeCredit;
            $tabp->upgrade_price = $upgradePrice;

            $groupKey = 'group-' . substr(md5(strtolower(trim($groupName))), 0, 12);

            if (! isset($groupedRaw[$groupKey])) {
                $groupedRaw[$groupKey] = [
                    'group_key' => $groupKey,
                    'name' => $groupName,
                    'tier_level' => $tier,
                    'class' => (int) ($contentObj->class ?? 0),
                    'is_current' => false,
                    'skus' => [],
                ];
            }

            if ($hasActiveTabp && (int) $tabp->id === (int) $activeTabpOrder->product_id) {
                $groupedRaw[$groupKey]['is_current'] = true;
            }

            $groupedRaw[$groupKey]['tier_level'] = max($groupedRaw[$groupKey]['tier_level'], $tier);
            $groupedRaw[$groupKey]['skus'][] = [
                'id' => (int) $tabp->id,
                'name' => (string) $tabp->name,
                'price' => round((float) $tabp->price, 2),
                'stock' => (int) $tabp->stock,
                'days' => $days,
                'class' => (int) ($contentObj->class ?? 0),
                'bandwidth' => (float) ($contentObj->bandwidth ?? 0),
                'speed_limit' => (string) ($contentObj->speed_limit ?? '0'),
                'ip_limit' => (string) ($contentObj->ip_limit ?? '0'),
                'cycle_key' => $cycleKey,
                'variant_label' => trim((string) ($contentObj->variant_label ?? '')),
                'action_mode' => $actionMode,
                'can_upgrade' => $canUpgrade,
                'upgrade_credit' => $upgradeCredit,
                'upgrade_price' => $upgradePrice,
            ];
        }

        $tabpGroups = [];
        foreach ($groupedRaw as $group) {
            $skus = $group['skus'];
            usort($skus, static function (array $a, array $b): int {
                $orderA = self::CYCLE_ORDER[$a['cycle_key']] ?? 9;
                $orderB = self::CYCLE_ORDER[$b['cycle_key']] ?? 9;
                if ($orderA !== $orderB) {
                    return $orderA <=> $orderB;
                }
                if ($a['days'] !== $b['days']) {
                    return $a['days'] <=> $b['days'];
                }

                return $a['price'] <=> $b['price'];
            });

            $cycleCounts = [];
            foreach ($skus as $sku) {
                $cycleCounts[$sku['cycle_key']] = ($cycleCounts[$sku['cycle_key']] ?? 0) + 1;
            }

            $baseSku = $skus[0];
            $baseDays = max(1, (int) $baseSku['days']);
            $baseMonthlyRate = $baseSku['price'] / ($baseDays / 30);

            $defaultIndex = 0;
            $minPrice = $skus[0]['price'];

            foreach ($skus as $idx => &$sku) {
                if ($sku['price'] < $minPrice) {
                    $minPrice = $sku['price'];
                }
                if ($sku['action_mode'] === 'renew') {
                    $defaultIndex = $idx;
                }

                $cycleText = match ($sku['cycle_key']) {
                    'monthly' => I18n::trans('user.cycle_monthly', $locale),
                    'quarterly' => I18n::trans('user.cycle_quarterly', $locale),
                    'half_yearly' => I18n::trans('user.cycle_half_yearly', $locale),
                    'yearly' => I18n::trans('user.cycle_yearly', $locale),
                    default => I18n::trans('user.duration_days', $locale, ['days' => $sku['days']]),
                };

                if ($sku['variant_label'] !== '') {
                    $sku['pill_label'] = $cycleText . ' · ' . $sku['variant_label'];
                } elseif (($cycleCounts[$sku['cycle_key']] ?? 0) > 1) {
                    $sku['pill_label'] = $cycleText . ' · ' . $sku['bandwidth'] . 'GB';
                } else {
                    $sku['pill_label'] = $cycleText;
                }

                $monthlyEquiv = round($sku['price'] / max(1.0, $sku['days'] / 30.0), 2);
                $sku['monthly_price'] = $monthlyEquiv;
                $sku['discount_percent'] = 0;
                if (count($skus) > 1 && $sku['days'] > $baseDays && $baseMonthlyRate > 0) {
                    $saving = (int) round((1 - ($monthlyEquiv / $baseMonthlyRate)) * 100);
                    if ($saving >= 5 && $saving < 100) {
                        $sku['discount_percent'] = $saving;
                    }
                }
            }
            unset($sku);

            $group['min_price'] = $minPrice;
            $group['default_index'] = $defaultIndex;
            $group['skus'] = $skus;
            $group['skus_json'] = json_encode($skus, JSON_UNESCAPED_UNICODE) ?: '[]';
            $tabpGroups[] = (object) $group;
        }

        usort($tabpGroups, static function (object $a, object $b): int {
            if ($a->tier_level !== $b->tier_level) {
                return $a->tier_level <=> $b->tier_level;
            }

            return $a->min_price <=> $b->min_price;
        });

        $activeTabpSummary = null;
        if ($hasActiveTabp) {
            $activeTabpSummary = (object) [
                'order_id' => (int) $activeTabpOrder->id,
                'product_id' => (int) $activeTabpOrder->product_id,
                'product_name' => (string) $activeTabpOrder->product_name,
                'expire_date' => Tools::toDateTime((int) $activeTabpInfo['expire_time']),
                'remaining_days' => (int) $activeTabpInfo['remaining_days'],
                'remaining_traffic_gb' => (float) $activeTabpInfo['remaining_traffic_gb'],
                'can_renew_direct' => $activeProductAvailable,
            ];
        }

        foreach ($bandwidths as $bandwidth) {
            $bandwidth->content = json_decode($bandwidth->content);
        }

        foreach ($times as $time) {
            $time->content = json_decode($time->content);
        }

        return $response->write(
            $this->view()
                ->assign('tabps', $tabps)
                ->assign('tabp_groups', $tabpGroups)
                ->assign('bandwidths', $bandwidths)
                ->assign('times', $times)
                ->assign('has_active_tabp', $hasActiveTabp)
                ->assign('active_tabp_order', $activeTabpOrder)
                ->assign('active_tabp_summary', $activeTabpSummary)
                ->fetch('user/product.tpl')
        );
    }
}
