<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Controllers\BaseController;
use App\Models\Config;
use App\Services\Cloudflare;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function in_array;

final class ClientController extends BaseController
{
    public function getClients(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $clientName = $this->antiXss->xss_clean($args['name']);

        if (! Config::obtain('enable_r2_client_download') || $clientName === '' || $clientName === null) {
            return $response->withStatus(404);
        }

        $clients = [
            'Clash.Nyanpasu.exe',
            'Clash.Nyanpasu.AppImage',
            'Clash.Nyanpasu_aarch64.dmg',
            'Clash.Verge.exe',
            'Clash.Verge.dmg',
            'Clash.Verge_aarch64.dmg',
            'Clash.Verge.deb',
            'CMFA.apk',
            'SFA.apk',
            'SFM.pkg',
            'SFM.dmg',
            'SFM.zip',
            'Hiddify.apk',
            'Hiddify.AppImage',
            'Hiddify.dmg',
            'Hiddify.exe',
            'FlClash.exe',
            'FlClash.dmg',
            'FlClash.deb',
            'FlClash.apk',
            'v2rayNG_universal.apk',
            'v2rayN-windows-64-desktop.zip',
            'clashmi.exe',
            'clashmi_arm64.apk',
            'clashmi.dmg',
        ];

        if (! in_array($clientName, $clients)) {
            return $response->withStatus(404);
        }

        $presignedUrl = Cloudflare::genR2PresignedUrl($clientName);

        return $response->withHeader('Location', $presignedUrl)->withStatus(302);
    }
}
