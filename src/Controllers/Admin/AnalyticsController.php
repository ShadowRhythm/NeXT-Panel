<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Analytics;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function date;

final class AnalyticsController extends BaseController
{
    /**
     * 运营分析首页
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->fetch('admin/analytics/index.tpl')
        );
    }

    /**
     * AJAX 数据接口
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $type  = $request->getQueryParam('type', '');
        $mode  = $request->getQueryParam('mode', 'day');      // day | month | year
        $month = $request->getQueryParam('month', date('Y-m'));
        $year  = $request->getQueryParam('year', date('Y'));

        $data = match ($type) {
            'site_stats' => match ($mode) {
                'month' => Analytics::getSiteMonthlyStats(),
                'year'  => Analytics::getSiteYearlyStats($year),
                default => Analytics::getSiteDailyByMonth($month),
            },

            'product_sales' => match ($mode) {
                'month' => Analytics::getProductSalesRankingRecentMonths(12),
                'year'  => Analytics::getProductSalesRankingByYear($year),
                default => Analytics::getProductSalesRanking($month),
            },

            'revenue_analysis' => Analytics::getRevenueAnalysis($mode, $month, $year),

            'traffic_clients' => Analytics::getTrafficAndClientStats($mode, $month, $year),

            'node_retention' => Analytics::getNodeAndRetentionStats($mode, $month, $year),

            default => ['error' => 'unknown type'],
        };

        return $response->withJson($data);
    }
}
