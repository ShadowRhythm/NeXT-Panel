<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\AuthController;
use App\Controllers\BaseController;
use App\Models\Config;
use App\Models\MFADevice;
use App\Models\User;
use App\Services\DataTable;
use App\Models\UserMoneyLog;
use App\Services\I18n;
use App\Utils\Hash;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

final class UserController extends BaseController
{
    private static array $details = [
        'field' => [
            'op' => '操作',
            'id' => '用户ID',
            'user_name' => '昵称',
            'email' => '邮箱',
            'money' => '余额',
            'ref_by' => '邀请人',
            'transfer_enable' => '流量限制',
            'transfer_used' => '当期用量',
            'transfer_today' => '今日流量/GB',
            'class' => '等级',
            'last_use_time' => '最后在线时间',
            'is_inactive' => '是否闲置',
            'reg_date' => '注册时间',
            'class_expire' => '等级过期',
            'last_check_in_time' => '最后签到时间',
            'last_login_time' => '最后登录时间',
        ],
        'create_dialog' => [
            [
                'id' => 'email',
                'info' => '登录邮箱',
                'type' => 'input',
                'placeholder' => '',
            ],
            [
                'id' => 'password',
                'info' => '登录密码',
                'type' => 'input',
                'placeholder' => '留空则随机生成',
            ],
            [
                'id' => 'ref_by',
                'info' => '邀请人',
                'type' => 'input',
                'placeholder' => '邀请人的用户id，可留空',
            ],
            [
                'id' => 'balance',
                'info' => '账户余额',
                'type' => 'input',
                'placeholder' => '-1为按默认设置，其他为指定值',
            ],
        ],
    ];

    private static array $update_field = [
        'email',
        'user_name',
        'pass',
        'money',
        'ref_by',
        'port',
        'method',
        'transfer_enable',
        'node_group',
        'class',
        'class_expire',
        'auto_reset_day',
        'auto_reset_bandwidth',
        'node_speedlimit',
        'node_iplimit',
        'locale',
        'banned_reason',
        'remark',
    ];

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/user/index.tpl')
        );
    }

    /**
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $email = $request->getParam('email');
        $ref_by = $request->getParam('ref_by');
        $password = $request->getParam('password');
        $balance = $request->getParam('balance');

        if ($email === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => '邮箱不能为空',
            ]);
        }

        $exist = (new User())->where('email', $email)->first();

        if ($exist !== null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '邮箱已存在',
            ]);
        }

        if ($password === '') {
            $password = Tools::genRandomChar(16);
        }

        (new AuthController())->registerHelper(
            $response,
            'user',
            $email,
            $password,
            '',
            0,
            '',
            $balance,
            1
        );
        $user = (new User())->where('email', $email)->first();

        if ($user === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '添加失败，用户未能创建',
            ]);
        }

        if ($ref_by !== '') {
            $user->ref_by = (int) $ref_by;
            $user->save();
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '添加成功，用户邮箱：' . $email . ' 密码：'.$password,
        ]);
    }

    /**
     * @throws Exception
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $user = (new User())->find($id);

        if ($user === null) {
            return $response->withStatus(302)->withHeader('Location', '/admin/user');
        }

        $user->last_use_time = Tools::toDateTime((int) $user->last_use_time);
        $user->last_check_in_time = Tools::toDateTime((int) $user->last_check_in_time);
        $user->last_login_time = Tools::toDateTime((int) $user->last_login_time);

        $hasMfa = (new MFADevice())->where('userid', $user->id)->exists();

        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->assign('edit_user', $user)
                ->assign('has_mfa', $hasMfa)
                ->assign('ss_methods', Tools::getSsMethod())
                ->assign('locales', I18n::getLocaleList())
                ->fetch('admin/user/edit.tpl')
        );
    }

    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $user = (new User())->find($id);

        if ($user === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '用户不存在',
            ]);
        }

        if ($request->getParam('pass') !== '' && $request->getParam('pass') !== null) {
            $user->pass = Hash::passwordHash($request->getParam('pass'));

            if (Config::obtain('enable_forced_replacement')) {
                $user->removeLink();
            }
        }

        if ($request->getParam('money') !== '' &&
            $request->getParam('money') !== null &&
            (float) $request->getParam('money') !== $user->money
        ) {
            $money = (float) $request->getParam('money');
            $diff = $money - $user->money;
            $remark = ($diff > 0 ? '管理员添加余额' : '管理员扣除余额');
            (new UserMoneyLog())->add($id, (float) $user->money, $money, $diff, $remark);
            $user->money = $money;
        }

        $user->email = trim((string) $request->getParam('email'));
        $user->user_name = trim((string) $request->getParam('user_name'));
        $user->ref_by = (int) ($request->getParam('ref_by') ?? 0);
        $user->port = (int) ($request->getParam('port') ?? 0);
        $user->method = (string) ($request->getParam('method') ?? 'aes-128-gcm');

        $transfer_raw = $request->getParam('transfer_enable');
        if ($transfer_raw !== null && $transfer_raw !== '') {
            $transfer_enable = Tools::autoBytesR((string) $transfer_raw);
            if ($transfer_enable >= 0) {
                $user->transfer_enable = $transfer_enable;
            }
        }

        $user->node_group = (int) ($request->getParam('node_group') ?? 0);
        $user->class = (int) ($request->getParam('class') ?? 0);

        $class_expire = trim((string) ($request->getParam('class_expire') ?? ''));
        if ($class_expire !== '') {
            $user->class_expire = $class_expire;
        }

        $user->auto_reset_day = (int) ($request->getParam('auto_reset_day') ?? 0);
        $user->auto_reset_bandwidth = (float) ($request->getParam('auto_reset_bandwidth') ?? 0);
        $user->node_speedlimit = (int) ($request->getParam('node_speedlimit') ?? 0);
        $user->node_iplimit = (int) ($request->getParam('node_iplimit') ?? 0);
        $user->locale = (string) ($request->getParam('locale') ?? 'zh_CN');
        $user->is_admin = $request->getParam('is_admin') === 'true' ? 1 : 0;
        $user->is_shadow_banned = $request->getParam('is_shadow_banned') === 'true' ? 1 : 0;
        $user->is_banned = $request->getParam('is_banned') === 'true' ? 1 : 0;
        $user->banned_reason = (string) ($request->getParam('banned_reason') ?? '');
        $user->remark = (string) ($request->getParam('remark') ?? '');

        try {
            if (! $user->save()) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => '修改失败',
                ]);
            }
        } catch (\Throwable $e) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '保存失败: ' . $e->getMessage(),
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '修改成功',
        ]);
    }

    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $user = (new User())->find((int) $id);

        if ($user === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '用户不存在',
            ]);
        }

        if (! $user->kill()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '删除失败',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '删除成功',
        ]);
    }

    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $users = DataTable::paginate(
            (new User())->newQuery(),
            $request,
            ['id', 'user_name', 'email'],
            [
                'id',
                'user_name',
                'email',
                'money',
                'ref_by',
                'transfer_enable',
                'transfer_used' => '(u + d)',
                'transfer_today',
                'class',
                'last_use_time',
                'is_inactive',
                'reg_date',
                'class_expire',
                'last_check_in_time',
                'last_login_time',
            ]
        );

        foreach ($users['data'] as $user) {
            $user->op = '<button class="btn btn-red" id="delete-user-' . $user->id . '" 
            onclick="deleteUser(' . $user->id . ')">删除</button>
            <a class="btn btn-primary" href="/admin/user/' . $user->id . '/edit">编辑</a>';
            $user->transfer_enable = $user->enableTraffic();
            $user->transfer_used = $user->usedTraffic();
            $user->transfer_today = round(Tools::bToGB($user->transfer_today), 2);
            $user->last_use_time = $user->lastUseTime();
            $user->last_check_in_time = $user->lastCheckInTime();
            $user->last_login_time = $user->lastLoginTime();
            $user->is_admin = $user->is_admin === 1 ? '是' : '否';
            $user->is_banned = $user->is_banned === 1 ? '是' : '否';
            $user->is_inactive = $user->is_inactive === 1 ? '是' : '否';
        }

        $users['users'] = $users['data'];
        unset($users['data']);
        return $response->withJson($users);
    }

    /**
     * 撤销用户的所有 MFA 设备
     */
    public function revokeMfa(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $user = (new User())->find($id);

        if ($user === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => '用户不存在',
            ]);
        }

        (new MFADevice())->where('userid', $user->id)->delete();

        return $response->withJson([
            'ret' => 1,
            'msg' => '已撤销该用户所有 MFA 设备',
        ]);
    }
}
