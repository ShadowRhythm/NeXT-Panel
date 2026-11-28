<?php

declare(strict_types=1);

use App\Middleware\Csrf;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Http\Factory\DecoratedServerRequestFactory;

function makeCsrfTestRequest(
    string $method,
    array $cookies = [],
    ?string $headerToken = null
): ServerRequestInterface {
    $httpFactory = new HttpFactory();
    $req = (new DecoratedServerRequestFactory($httpFactory, $httpFactory))
        ->createServerRequest($method, '/user/edit')
        ->withCookieParams($cookies);

    if ($headerToken !== null) {
        $req = $req->withHeader('X-CSRF-Token', $headerToken);
    }

    return $req;
}

function makeCsrfPassHandler(): RequestHandlerInterface
{
    return new class implements RequestHandlerInterface {
        public bool $called = false;

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $this->called = true;
            $httpFactory = new HttpFactory();
            return (new DecoratedResponseFactory($httpFactory, $httpFactory))
                ->createResponse(200)
                ->withJson(['ret' => 1]);
        }
    };
}

describe('Middleware\\Csrf', function () {
    beforeEach(function () {
        $this->prevCookie = $_COOKIE;
        $_COOKIE = [];
    });

    afterEach(function () {
        $_COOKIE = $this->prevCookie;
    });

    it('allows safe methods without CSRF token', function (string $method) {
        $middleware = new Csrf();
        $handler = makeCsrfPassHandler();

        $response = $middleware->process(makeCsrfTestRequest($method), $handler);

        expect($handler->called)->toBeTrue()
            ->and($response->getStatusCode())->toBe(200);
    })->with(['GET', 'HEAD', 'OPTIONS']);

    it('rejects state-changing requests with missing or mismatched tokens', function (
        array $cookies,
        ?string $header
    ) {
        $middleware = new Csrf();
        $handler = makeCsrfPassHandler();

        $response = $middleware->process(makeCsrfTestRequest('POST', $cookies, $header), $handler);

        expect($handler->called)->toBeFalse()
            ->and($response->getStatusCode())->toBe(403)
            ->and((string) $response->getBody())->toContain('CSRF verification failed');
    })->with([
        'no cookie and no header' => [[], null],
        'cookie without header' => [['_csrf' => 'secret123'], null],
        'header without cookie' => [[], 'secret123'],
        'mismatched token' => [['_csrf' => 'secret123'], 'wrong456'],
    ]);

    it('allows state-changing requests when cookie and header tokens match', function (string $method) {
        $middleware = new Csrf();
        $handler = makeCsrfPassHandler();

        $response = $middleware->process(
            makeCsrfTestRequest($method, ['_csrf' => 'valid-token-32'], 'valid-token-32'),
            $handler
        );

        expect($handler->called)->toBeTrue()
            ->and($response->getStatusCode())->toBe(200);
    })->with(['POST', 'PUT', 'PATCH', 'DELETE']);
});
