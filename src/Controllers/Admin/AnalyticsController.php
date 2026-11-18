<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\Analytics;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

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
