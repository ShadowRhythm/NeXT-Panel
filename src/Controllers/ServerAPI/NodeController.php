<?php

declare(strict_types=1);

namespace App\Controllers\ServerAPI;

use App\Controllers\BaseController;
use App\Models\Node;
use App\Services\GlobalIpLimit;
use App\Utils\ResponseHelper;
use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function max;
use function time;

final class NodeController extends BaseController
{
    /**
     * GET /api/server/v1/info
     */
    public function getInfo(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        /** @var Node|null $node */
        $node = $request->getAttribute('node');

        if ($node === null) {
            return $response->withStatus(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $data = [
            'node_speedlimit' => $node->node_speedlimit,
            'sort' => $node->sort,
            'server' => $node->server,
            'custom_config' => Tools::decodeCustomConfig($node->custom_config),
        ];

        return ResponseHelper::successWithDataEtag($request, $response, $data);
    }

    /**
     * PUT /api/server/v1/heartbeat
     */
    public function heartbeat(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        /** @var Node|null $node */
        $node = $request->getAttribute('node');

        if ($node === null) {
            return $response->withStatus(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $body = json_decode($request->getBody()->__toString(), true);
        $online_user = isset($body['online_user']) ? (int) $body['online_user'] : 0;

        $update = [
            'online_user'    => max(0, $online_user),
            'node_heartbeat' => time(),
        ];

        if (isset($body['version']) && $body['version'] !== '') {
            $update['backend_version'] = (string) $body['version'];
        }

        $shouldUpgrade = $node->upgrade_pending === 1;
        $shouldRestart = $node->restart_pending === 1;

        $node->update($update);

        if ($shouldUpgrade) {
            Node::where('id', $node->id)->update(['upgrade_pending' => 0]);
        }
        if ($shouldRestart) {
            Node::where('id', $node->id)->update(['restart_pending' => 0]);
        }
        if ($shouldUpgrade || $shouldRestart) {
            Node::forgetTokenCache((string) $node->password);
        }

        $kickIps = GlobalIpLimit::drainKickIps((int) $node->id);

        return $response->withJson(array_filter([
            'data'     => 'ok',
            'upgrade'  => $shouldUpgrade ?: null,
            'restart'  => $shouldRestart ?: null,
            'kick_ips' => $kickIps !== [] ? $kickIps : null,
        ], static fn ($v) => $v !== null));
    }
}
