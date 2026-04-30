<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\RepeatableMiddleware;

final class RepeatableMiddlewareTest extends TestCase
{
    private function makeHandler(ResponseInterface ...$responses): HandlerInterface
    {
        $queue = $responses;

        return new class($queue) implements HandlerInterface {
            private array $queue;

            public function __construct(array $queue)
            {
                $this->queue = $queue;
            }

            public function handle(RequestInterface $request): ResponseInterface
            {
                return array_shift($this->queue);
            }
        };
    }

    private function makeResponse(int $statusCode): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);

        return $response;
    }

    public function testNoRetryOnSuccessResponse(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $success = $this->makeResponse(200);

        $handler = $this->makeHandler($success);

        $result = (new RepeatableMiddleware())->handle($request, $handler);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testRetriesOnServerError(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $failure = $this->makeResponse(503);
        $success = $this->makeResponse(200);

        $handler = $this->makeHandler($failure, $success);

        $result = (new RepeatableMiddleware(1))->handle($request, $handler);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testStopsAfterMaxRetries(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $handler = $this->makeHandler(
            $this->makeResponse(503),
            $this->makeResponse(503),
            $this->makeResponse(503),
            $this->makeResponse(200),
        );

        $result = (new RepeatableMiddleware(2))->handle($request, $handler);

        $this->assertSame(503, $result->getStatusCode());
    }

    public function testCustomShouldRetryCondition(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $tooMany = $this->makeResponse(429);
        $success = $this->makeResponse(200);

        $handler = $this->makeHandler($tooMany, $success);

        $middleware = new RepeatableMiddleware(
            1,
            static function (ResponseInterface $response): bool {
                return $response->getStatusCode() === 429;
            }
        );

        $result = $middleware->handle($request, $handler);

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testDefaultConditionDoesNotRetryOn4xx(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $clientError = $this->makeResponse(400);

        $handler = $this->makeHandler($clientError);

        $result = (new RepeatableMiddleware())->handle($request, $handler);

        $this->assertSame(400, $result->getStatusCode());
    }
}
