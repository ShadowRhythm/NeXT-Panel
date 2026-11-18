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
        $monthlyComparison     = Analytics::getMonthlyIncomeComparison();
        $userClassDistribution = Analytics::getUserClassDistribution();
        $todayIncome           = Analytics::getIncome('today');
        $thisMonthIncome       = Analytics::getIncome('this month');
        $totalIncome           = Analytics::getIncome('total');
        $totalUser             = Analytics::getTotalUser();
        $activeUser            = Analytics::getActiveUser();
        $productSales          = Analytics::getProductSalesRanking(date('Y-m'));

        return $response->write(
            $this->view()
                ->assign('monthly_comparison', $monthlyComparison)
                ->assign('user_class_distribution', $userClassDistribution)
                ->assign('today_income', $todayIncome)
                ->assign('this_month_income', $thisMonthIncome)
                ->assign('total_income', $totalIncome)
                ->assign('total_user', $totalUser)
                ->assign('active_user', $activeUser)
                ->assign('product_sales', $productSales)
                ->fetch('admin/analytics/index.tpl')
        );
    }

    /**
     * AJAX 数据接口
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $type  = $request->getQueryParam('type', '');
        $mode  = $request->getQueryParam('mode', 'day');      // day | month
        $month = $request->getQueryParam('month', date('Y-m'));

        $data = match ($type) {
            'site_stats' => $mode === 'month'
                ? Analytics::getSiteMonthlyStats()
                : Analytics::getSiteDailyByMonth($month),

            'product_sales' => Analytics::getProductSalesRanking(
                $mode === 'month' ? null : $month
            ),

            default => ['error' => 'unknown type'],
        };

        return $response->withJson($data);
    }
}
