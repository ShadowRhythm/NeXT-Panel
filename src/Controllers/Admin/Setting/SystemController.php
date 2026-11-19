<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Setting;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Services\QQWryIpdb;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use Smarty\Exception;

final class SystemController extends BaseController
{
    private array $update_field;
    private array $settings;

    public function __construct()
    {
        parent::__construct();
        $this->update_field = Config::getItemListByClass('system');
        $this->settings = Config::getClass('system');
    }

    /**
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        // qqwry.ipdb 更新时间
        $ipdbPath = BASE_PATH . '/storage/qqwry.ipdb';
        $ipdbInfo = null;
        if (is_readable($ipdbPath)) {
            try {
                $reader = new QQWryIpdb($ipdbPath);
                $meta = $reader->getMeta();
                // ipdb meta 里有 build 字段（UTC 时间戳）
                $buildTs = $meta['build'] ?? 0;
                $ipdbInfo = $buildTs > 0
                    ? date('Y-m-d H:i', $buildTs) . ' UTC'
                    : '文件存在，版本未知';
            } catch (\Exception) {
                $ipdbInfo = '文件损坏';
            }
        }

        // MaxMind GeoLite2-City 更新时间
        $mmdbPath = BASE_PATH . '/storage/GeoLite2-City/GeoLite2-City.mmdb';
        if (! is_readable($mmdbPath)) {
            $mmdbPath = BASE_PATH . '/storage/GeoLite2-City.mmdb';
        }
        $maxmindInfo = null;
        if (is_readable($mmdbPath)) {
            $maxmindInfo = date('Y-m-d H:i', filemtime($mmdbPath));
        }

        return $response->write(
            $this->view()
                ->assign('update_field', $this->update_field)
                ->assign('settings', $this->settings)
                ->assign('ipdb_info', $ipdbInfo)
                ->assign('maxmind_info', $maxmindInfo)
                ->fetch('admin/setting/system.tpl')
        );
    }

    public function save(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        foreach ($this->update_field as $item) {
            if (! Config::set($item, $request->getParam($item))) {
                return $response->withJson([
                    'ret' => 0,
                    'msg' => '保存 ' . $item . ' 时出错',
                ]);
            }
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => '保存成功',
        ]);
    }
}
