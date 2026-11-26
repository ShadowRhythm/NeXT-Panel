<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Analytics;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function date;
use function strtotime;

final class AnalyticsController extends BaseController
{
    /**
     * 运营分析首页
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('current_date', date('Y-m-d'))
                ->assign('yesterday_date', date('Y-m-d', strtotime('-1 day')))
                ->assign('current_month', date('Y-m'))
                ->assign('last_month', date('Y-m', strtotime('first day of last month')))
                ->assign('current_year', date('Y'))
                ->assign('last_year', date('Y', strtotime('-1 year')))
                ->fetch('admin/analytics/index.tpl')
        );
    }

    /**
     * AJAX 数据接口
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $type  = $request->getQueryParam('type', '');
        $mode  = $request->getQueryParam('mode', 'month');     // day | month | year
        $date  = $request->getQueryParam('date', date('Y-m-d'));
        $month = $request->getQueryParam('month', date('Y-m'));
        $year  = $request->getQueryParam('year', date('Y'));

        $data = match ($type) {
            'site_stats' => match ($mode) {
                'day'   => Analytics::getSiteDailyStats($date),
                'year'  => Analytics::getSiteYearlyStats($year),
                default => Analytics::getSiteDailyByMonth($month),
            },

            'product_sales' => match ($mode) {
                'day'   => Analytics::getProductSalesRankingByDate($date),
                'year'  => Analytics::getProductSalesRankingByYear($year),
                default => Analytics::getProductSalesRanking($month),
            },

            'revenue_analysis' => Analytics::getRevenueAnalysis($mode, $month, $year, $date),

            'traffic_clients' => Analytics::getTrafficAndClientStats($mode, $month, $year, $date),

            'node_retention' => Analytics::getNodeAndRetentionStats($mode, $month, $year, $date),

            default => ['error' => 'unknown type'],
        };

        return $response->withJson($data);
    }
}
