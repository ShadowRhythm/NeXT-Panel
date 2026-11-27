<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Node;
use App\Models\OnlineLog;
use App\Utils\Tools;
use Exception;
use MaxMind\Db\Reader\InvalidDatabaseException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;
use function strtolower;
use function time;

final class OnlineLogController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'id' => '事件ID',
                'user_id' => '用户ID',
                'node_id' => '节点ID',
                'node_name' => '节点名',
                'ip' => 'IP',
                'location' => 'IP归属地',
                'first_time' => '首次连接',
                'last_time' => '最后连接',
            ],
        ];

    /**
     * 后台在线 IP 页面
     *
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/log/online.tpl')
        );
    }

    /**
     * 后台在线 IP 页面 AJAX
     *
     * @throws InvalidDatabaseException
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $length = $request->getParam('length');
        $page = $request->getParam('start') / $length + 1;
        $draw = $request->getParam('draw');

        $online_log = OnlineLog::query()
            ->where('last_time', '>', time() - 90)
            ->where('kicked_at', 0);

        $search = $request->getParam('search')['value'];

        if ($search !== '') {
            $online_log->where(static function ($query) use ($search): void {
                $query->where('user_id', '=', $search)
                    ->orWhere('ip', 'LIKE', "%{$search}%")
                    ->orWhere('node_id', '=', $search);
            });
        }

        $whitelist = ['id', 'user_id', 'ip', 'node_id', 'first_time', 'last_time'];
        $order = strtolower((string) ($request->getParam('order')[0]['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($request->getParam('order')[0]['column'] !== '7') {
            $column_data = $request->getParam('columns')[$request->getParam('order')[0]['column']]['data'] ?? 'last_time';
            $order_by = in_array($column_data, $whitelist, true) ? $column_data : 'last_time';

            $online_log->orderBy($order_by, $order)->orderBy('last_time', 'desc');
        } else {
            $online_log->orderBy('last_time', $order);
        }

        $filtered = $online_log->count();
        $total = (new OnlineLog())->count();

        $onlines = $online_log->paginate($length, '*', '', $page);

        $nodeIds = $onlines->pluck('node_id')->unique()->filter()->values();
        $nodes = ! $nodeIds->isEmpty() ? Node::whereIn('id', $nodeIds)->pluck('name', 'id') : collect();

        foreach ($onlines as $online) {
            $online->node_name = $nodes[$online->node_id] ?? '未知节点';
            $online->ip = $online->ip();
            $online->location = Tools::getIpLocation($online->ip);
            $online->first_time = Tools::toDateTime($online->first_time);
            $online->last_time = Tools::toDateTime($online->last_time);
        }

        return $response->withJson([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'onlines' => $onlines,
        ]);
    }
}
