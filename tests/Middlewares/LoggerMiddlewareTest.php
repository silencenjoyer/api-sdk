<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\LoggerMiddleware;

final class LoggerMiddlewareTest extends TestCase
{
    private function makeHandler(ResponseInterface $response): HandlerInterface
    {
        return new class($response) implements HandlerInterface {
            private ResponseInterface $response;

            public function __construct(ResponseInterface $response)
            {
                $this->response = $response;
            }

            public function handle(RequestInterface $request): ResponseInterface
            {
                return $this->response;
            }
        };
    }

    public function testLogsRequestAndResponseBodies(): void
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('POST', 'https://example.com')
            ->withBody($psr17->createStream('{"key":"value"}'));
        $response = $psr17->createResponse(200)
            ->withBody($psr17->createStream('{"result":1}'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                ['Request body', ['body' => '{"key":"value"}']],
                ['Response body', ['body' => '{"result":1}']],
            );

        $middleware = new LoggerMiddleware($logger);
        $result = $middleware->handle($request, $this->makeHandler($response));

        $this->assertSame($response, $result);
    }

    public function testRewoundsRequestBodyAfterLogging(): void
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('POST', 'https://example.com')
            ->withBody($psr17->createStream('body content'));
        $response = $psr17->createResponse(200)
            ->withBody($psr17->createStream(''));

        $logger = $this->createMock(LoggerInterface::class);
        $middleware = new LoggerMiddleware($logger);
        $middleware->handle($request, $this->makeHandler($response));

        $this->assertSame(0, $request->getBody()->tell());
    }

    public function testRewoundsResponseBodyAfterLogging(): void
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('GET', 'https://example.com');
        $response = $psr17->createResponse(200)
            ->withBody($psr17->createStream('response body'));

        $logger = $this->createMock(LoggerInterface::class);
        $middleware = new LoggerMiddleware($logger);
        $result = $middleware->handle($request, $this->makeHandler($response));

        $this->assertSame(0, $result->getBody()->tell());
    }
}