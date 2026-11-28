<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Tools;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use function hash_equals;
use function headers_sent;
use function in_array;
use function is_string;
use function setcookie;
use function strtoupper;
use function time;

final class Csrf implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public static function ensureCookie(): void
    {
        if (isset($_COOKIE['_csrf']) && is_string($_COOKIE['_csrf']) && $_COOKIE['_csrf'] !== '') {
            return;
        }

        $token = Tools::genRandomChar(32);

        if (! headers_sent()) {
            setcookie('_csrf', $token, [
                'expires' => time() + 86400 * 30,
                'path' => '/',
                'secure' => (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
                    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        $_COOKIE['_csrf'] = $token;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        if (in_array($method, self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $cookieParams = $request->getCookieParams();
        $cookieToken = (string) ($cookieParams['_csrf'] ?? $_COOKIE['_csrf'] ?? '');
        $headerToken = $request->getHeaderLine('X-CSRF-Token');

        if ($cookieToken === '' || $headerToken === '' || ! hash_equals($cookieToken, $headerToken)) {
            $response = AppFactory::determineResponseFactory()->createResponse(403);
            $response->getBody()->write('{"ret":0,"msg":"CSRF verification failed"}');
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
