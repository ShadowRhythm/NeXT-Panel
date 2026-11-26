<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\Node;
use App\Services\I18n;
use App\Services\Notification;
use App\Utils\Tools;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception as SmartyException;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function htmlspecialchars;
use function json_decode;
use function json_encode;
use function round;
use function str_replace;
use function trim;
use const ENT_QUOTES;

final class NodeController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => '操作',
            'id' => '节点ID',
            'name' => '名称',
            'online_status' => '在线状态',
            'online_user' => '在线人数',
            'node_heartbeat' => '最后心跳',
            'type' => '显示状态',
            'server' => '地址',
            'sort' => '类型',
            'traffic_rate' => '倍率',
            'is_dynamic_rate' => '动态倍率',
            'node_class' => '等级',
            'node_bandwidth_limit' => '流量限制/GB',
            'node_bandwidth' => '已用流量/GB',
            'bandwidthlimit_resetday' => '重置日',
            'backend_version' => '后端版本',
            'backend_action' => '后端操作',
        ],
    ];

    private static array $update_field = [
        'name',
        'server',
        'traffic_rate',
        'is_dynamic_rate',
        'dynamic_rate_type',
        'max_rate',
        'max_rate_time',
        'min_rate',
        'min_rate_time',
        'node_group',
        'node_speedlimit',
        'sort',
        'node_class',
        'node_bandwidth_limit',
        'bandwidthlimit_resetday',
    ];

    /**
     * 后台节点页面
     *
     * @throws SmartyException
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/node/index.tpl')
        );
    }

    /**
     * 后台创建节点页面
     *
     * @throws SmartyException
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->fetch('admin/node/create.tpl')
        );
    }

    /**
     * 后台添加节点
     */
    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $name = trim((string) $this->antiXss->xss_clean((string) ($request->getParam('name') ?? '')));
        $server = trim((string) $this->antiXss->xss_clean((string) ($request->getParam('server') ?? '')));

        if ($name === '' || $server === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点名称和地址不能为空',
            ]);
        }

        $node = new Node();

        $node->name = $name;
        $node->node_group = $request->getParam('node_group');
        $node->server = $server;
        $node->traffic_rate = $request->getParam('traffic_rate') ?? 1;
        $node->is_dynamic_rate = $request->getParam('is_dynamic_rate') === 'true' ? 1 : 0;
        $node->dynamic_rate_type = $request->getParam('dynamic_rate_type') ?? 0;
        $node->dynamic_rate_config = json_encode([
            'max_rate' => $request->getParam('max_rate') ?? 1,
            'max_rate_time' => $request->getParam('max_rate_time') ?? 22,
            'min_rate' => $request->getParam('min_rate') ?? 1,
            'min_rate_time' => $request->getParam('min_rate_time') ?? 3,
        ]);

        $custom_config = $request->getParam('custom_config') ?? '{}';

        if ($custom_config !== '') {
            $node->custom_config = $custom_config;
        } else {
            $node->custom_config = '{}';
        }

        $node->node_speedlimit = $request->getParam('node_speedlimit');
        $node->type = $request->getParam('type') === 'true' ? 1 : 0;
        $node->sort = $request->getParam('sort');
        $node->node_class = $request->getParam('node_class');
        $node->node_bandwidth_limit = Tools::gbToB($request->getParam('node_bandwidth_limit'));
        $node->bandwidthlimit_resetday = $request->getParam('bandwidthlimit_resetday');
        $node->password = Tools::genRandomChar(32);

        if (! $node->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '添加失败',
            ]);
        }

        if (Config::obtain('im_bot_group_notify_add_node')) {
            try {
                Notification::notifyUserGroup(
                    str_replace(
                        '%node_name%',
                        $request->getParam('name'),
                        I18n::trans('bot.node_added', $_ENV['locale'])
                    )
                );
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 1,
                    'msg' => '添加成功，但 IM Bot 通知失败',
                    'node_id' => $node->id,
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '添加成功',
            'node_id' => $node->id,
        ]);
    }

    /**
     * 后台编辑指定节点页面
     *
     * @throws SmartyException
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $node = (new Node())->find($id);

        if ($node === null) {
            return $response->withStatus(302)->withHeader('Location', '/admin/node');
        }

        $dynamic_rate_config = json_decode((string) $node->dynamic_rate_config);
        $node->max_rate = $dynamic_rate_config?->max_rate ?? 1;
        $node->max_rate_time = $dynamic_rate_config?->max_rate_time ?? 22;
        $node->min_rate = $dynamic_rate_config?->min_rate ?? 1;
        $node->min_rate_time = $dynamic_rate_config?->min_rate_time ?? 3;

        $node->node_bandwidth = Tools::autoBytes((int) $node->node_bandwidth);
        $node->node_bandwidth_limit = Tools::bToGB((int) $node->node_bandwidth_limit);

        return $response->write(
            $this->view()
                ->assign('node', $node)
                ->assign('update_field', self::$update_field)
                ->fetch('admin/node/edit.tpl')
        );
    }

    /**
     * 后台更新指定节点内容
     */
    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if ($node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $name = trim((string) $this->antiXss->xss_clean((string) ($request->getParam('name') ?? '')));
        $server = trim((string) $this->antiXss->xss_clean((string) ($request->getParam('server') ?? '')));

        if ($name === '' || $server === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点名称和地址不能为空',
            ]);
        }

        $node->name = $name;
        $node->node_group = $request->getParam('node_group') ?? 0;
        $node->server = $server;
        $node->traffic_rate = $request->getParam('traffic_rate') ?? 1;
        $node->is_dynamic_rate = $request->getParam('is_dynamic_rate') === 'true' ? 1 : 0;
        $node->dynamic_rate_type = $request->getParam('dynamic_rate_type') ?? 0;
        $node->dynamic_rate_config = json_encode([
            'max_rate' => $request->getParam('max_rate') ?? 1,
            'max_rate_time' => $request->getParam('max_rate_time') ?? 0,
            'min_rate' => $request->getParam('min_rate') ?? 1,
            'min_rate_time' => $request->getParam('min_rate_time') ?? 0,
        ]);

        $custom_config = $request->getParam('custom_config') ?? '{}';

        if ($custom_config !== '') {
            $node->custom_config = $custom_config;
        } else {
            $node->custom_config = '{}';
        }

        $node->node_speedlimit = $request->getParam('node_speedlimit');
        $node->type = $request->getParam('type') === 'true' ? 1 : 0;
        $node->sort = $request->getParam('sort');
        $node->node_class = $request->getParam('node_class');
        $node->node_bandwidth_limit = Tools::gbToB($request->getParam('node_bandwidth_limit'));
        $node->bandwidthlimit_resetday = $request->getParam('bandwidthlimit_resetday');

        if (! $node->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '修改失败',
            ]);
        }

        Node::forgetTokenCache((string) $node->password);

        if (Config::obtain('im_bot_group_notify_update_node')) {
            try {
                Notification::notifyUserGroup(
                    str_replace(
                        '%node_name%',
                        $request->getParam('name'),
                        I18n::trans('bot.node_updated', $_ENV['locale'])
                    )
                );
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 1,
                    'msg' => '修改成功，但 IM Bot 通知失败',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '修改成功',
        ]);
    }

    public function resetPassword(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if ($node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $oldPassword = (string) $node->password;
        $node->password = Tools::genRandomChar(32);
        $node->save();
        Node::forgetTokenCache($oldPassword);

        return $response->withJson([
            'ret' => 1,
            'msg' => '重置节点通讯密钥成功',
        ]);
    }

    public function resetBandwidth(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if ($node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $node->node_bandwidth = 0;
        $node->save();
        Node::forgetTokenCache((string) $node->password);

        return $response->withJson([
            'ret' => 1,
            'msg' => '重置节点流量成功',
        ]);
    }

    public function triggerUpgrade(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if ($node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $node->upgrade_pending = 1;
        $node->save();
        Node::forgetTokenCache((string) $node->password);

        return $response->withJson([
            'ret' => 1,
            'msg' => '已标记升级，后端将在下次心跳时自动下载最新版并重启',
        ]);
    }

    public function triggerUpgradeAll(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $total = (new Node())->count();

        if ($total === 0) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '暂无节点可升级',
            ]);
        }

        (new Node())->where('id', '>', 0)->update(['upgrade_pending' => 1]);
        Node::forgetAllTokenCaches();

        return $response->withJson([
            'ret' => 1,
            'msg' => "已为全部 {$total} 个节点下发升级指令，后端将在下次心跳时自动下载最新版并重启",
        ]);
    }

    public function triggerRestart(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if ($node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $node->restart_pending = 1;
        $node->save();
        Node::forgetTokenCache((string) $node->password);

        return $response->withJson([
            'ret' => 1,
            'msg' => '已下发重启指令，后端将在下次心跳时自动重启',
        ]);
    }

    /**
     * 后台删除指定节点
     */
    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $node = (new Node())->find($args['id']);

        if ($node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $password = (string) $node->password;

        if (! $node->delete()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '删除失败',
            ]);
        }

        Node::forgetTokenCache($password);

        if (Config::obtain('im_bot_group_notify_delete_node')) {
            try {
                Notification::notifyUserGroup(
                    str_replace(
                        '%node_name%',
                        $node->name,
                        I18n::trans('bot.node_deleted', $_ENV['locale'])
                    )
                );
            } catch (TelegramSDKException | GuzzleException) {
                return $response->withJson([
                    'ret' => 1,
                    'msg' => '删除成功，但 IM Bot 通知失败',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function copy($request, $response, $args)
    {
        $old_node = (new Node())->find($args['id']);

        if ($old_node === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '节点不存在',
            ]);
        }

        $new_node = $old_node->replicate([
            'node_bandwidth',
        ]);
        $new_node->name .= ' (副本)';
        $new_node->node_bandwidth = 0;
        $new_node->password = Tools::genRandomChar(32);

        if (! $new_node->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '复制失败',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '复制成功',
        ]);
    }

    /**
     * 后台节点页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $nodes = (new Node())->orderBy('id', 'desc')->get();

        foreach ($nodes as $node) {
            $node->op = '<button class="btn btn-red" id="delete-node-' . $node->id . '" 
            onclick="deleteNode(' . $node->id . ')">删除</button>
            <button class="btn btn-orange" id="copy-node-' . $node->id . '" 
            onclick="copyNode(' . $node->id . ')">复制</button>
            <a class="btn btn-primary" href="/admin/node/' . $node->id . '/edit">编辑</a>';

            $online_status = $node->getNodeOnlineStatus();
            $node->online_status = match ($online_status) {
                1 => '<span class="badge bg-green text-green-fg">在线</span>',
                0 => '<span class="badge bg-orange text-orange-fg">未连接</span>',
                default => '<span class="badge bg-danger text-danger-fg">离线</span>',
            };

            $node->online_user = $online_status === 1
                ? '<span class="badge bg-blue-lt">' . (int) $node->online_user . '</span>'
                : '<span class="text-secondary">0</span>';

            $node->node_heartbeat = (int) $node->node_heartbeat === 0
                ? '<span class="text-secondary">从未连接</span>'
                : Tools::toDateTime((int) $node->node_heartbeat);

            $node->name = htmlspecialchars((string) $node->name, ENT_QUOTES, 'UTF-8');
            $node->server = htmlspecialchars((string) $node->server, ENT_QUOTES, 'UTF-8');
            $node->type = $node->type();
            $node->sort = $node->sort();
            $node->is_dynamic_rate = $node->isDynamicRate();
            $node->dynamic_rate_type = $node->dynamicRateType();
            $node->node_bandwidth = round(Tools::bToGB($node->node_bandwidth), 2);
            $node->node_bandwidth_limit = Tools::bToGB($node->node_bandwidth_limit);

            if ($node->backend_version !== '') {
                $node->backend_version = '<span class="badge bg-blue-lt">' . htmlspecialchars((string) $node->backend_version) . '</span>';
            } else {
                $node->backend_version = '<span class="text-secondary">暂未上报</span>';
            }

            $upgrade_btn = $node->upgrade_pending === 1
                ? '<button class="btn btn-warning" disabled><i class="icon ti ti-loader me-1"></i>升级中</button>'
                : '<button class="btn btn-warning" id="upgrade-node-' . $node->id . '" onclick="upgradeNode(' . $node->id . ')"><i class="icon ti ti-refresh me-1"></i>更新</button>';

            $restart_btn = $node->restart_pending === 1
                ? '<button class="btn btn-red" disabled><i class="icon ti ti-loader me-1"></i>重启中</button>'
                : '<button class="btn btn-red" id="restart-node-' . $node->id . '" onclick="restartNode(' . $node->id . ')"><i class="icon ti ti-rotate-clockwise me-1"></i>重启</button>';

            $node->backend_action = '<div class="btn-list flex-nowrap">' . $upgrade_btn . $restart_btn . '</div>';
        }

        return $response->withJson([
            'nodes' => $nodes,
        ]);
    }
}
