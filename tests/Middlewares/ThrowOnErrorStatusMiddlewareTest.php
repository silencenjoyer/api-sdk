<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Exceptions\ClientHttpException;
use Silencenjoyer\ApiSdk\Exceptions\HttpResponseException;
use Silencenjoyer\ApiSdk\Exceptions\ServerHttpException;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\ThrowOnErrorStatusMiddleware;

final class ThrowOnErrorStatusMiddlewareTest extends TestCase
{
    private function makeHandler(int $statusCode): HandlerInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        return $handler;
    }

    public function testPassesThroughOnSuccess(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $result = (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(200));

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testPassesThroughOn3xx(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $result = (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(301));

        $this->assertSame(301, $result->getStatusCode());
    }

    public function testThrowsClientHttpExceptionOn400(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(ClientHttpException::class);
        (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(400));
    }

    public function testThrowsClientHttpExceptionOn404(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(ClientHttpException::class);
        (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(404));
    }

    public function testThrowsServerHttpExceptionOn500(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(ServerHttpException::class);
        (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(500));
    }

    public function testThrowsServerHttpExceptionOn503(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(ServerHttpException::class);
        (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(503));
    }

    public function testClientExceptionIsHttpResponseException(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(HttpResponseException::class);
        (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(422));
    }

    public function testServerExceptionIsHttpResponseException(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->expectException(HttpResponseException::class);
        (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(502));
    }

    public function testExceptionExposesResponse(): void
    {
        $request = $this->createMock(RequestInterface::class);

        try {
            (new ThrowOnErrorStatusMiddleware())->handle($request, $this->makeHandler(404));
            $this->fail('Expected ClientHttpException');
        } catch (ClientHttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
            $this->assertInstanceOf(ResponseInterface::class, $e->getResponse());
        }
    }
}
