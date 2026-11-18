<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\Config;
use App\Models\Node;
use App\Services\RateLimit;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RedisException;
use Slim\Factory\AppFactory;
use voku\helper\AntiXSS;
use function preg_match;
use function trim;

final class NodeBearerToken implements MiddlewareInterface
{
    /**
     * @throws RedisException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getHeaderLine('Authorization');

        if (! preg_match('/^Bearer\s+(.+)$/i', $auth, $matches)) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $token = trim($matches[1]);

        if ($token === '') {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $antiXss = new AntiXSS();

        if (Config::obtain('enable_rate_limit') &&
            (! (new RateLimit())->checkRateLimit('webapi_ip', (string) ($request->getServerParam('REMOTE_ADDR') ?? '')) ||
                ! (new RateLimit())->checkRateLimit('webapi_key', $antiXss->xss_clean($token)))
        ) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        $node = (new Node())->where('password', $token)->first();

        if ($node === null || $node->type === 0) {
            return AppFactory::determineResponseFactory()->createResponse(401)->withJson([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid token.',
                ],
            ]);
        }

        return $handler->handle($request->withAttribute('node', $node));
    }
}
