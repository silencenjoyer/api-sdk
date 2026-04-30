<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\RateLimiterMiddleware;
use Silencenjoyer\RateLimit\Limiters\LimiterInterface;

final class RateLimiterMiddlewareTest extends TestCase
{
    private function makeLimiter(): LimiterInterface
    {
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->method('control')->willReturnCallback(
            static function (Closure $closure) {
                return $closure();
            }
        );

        return $limiter;
    }

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

    public function testDelegatesRequestToHandler(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $result = (new RateLimiterMiddleware($this->makeLimiter()))
            ->handle($request, $this->makeHandler($response));

        $this->assertSame($response, $result);
    }

    public function testControlIsCalledOnEachRequest(): void
    {
        $limiter = $this->createMock(LimiterInterface::class);
        $limiter->expects($this->exactly(3))
            ->method('control')
            ->willReturnCallback(static function (Closure $closure) {
                return $closure();
            });

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler = $this->makeHandler($response);

        $middleware = new RateLimiterMiddleware($limiter);
        $middleware->handle($request, $handler);
        $middleware->handle($request, $handler);
        $middleware->handle($request, $handler);
    }
}
