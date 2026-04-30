<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareStack;

final class MiddlewareStackTest extends TestCase
{
    private function makeFinalHandler(ResponseInterface $response): HandlerInterface
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

    public function testHandleCallsMiddlewaresInDefinedOrder(): void
    {
        $order = [];

        $mw1 = $this->createMock(MiddlewareInterface::class);
        $mw1->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$order) {
                $order[] = 'first';
                return $next->handle($req);
            }
        );

        $mw2 = $this->createMock(MiddlewareInterface::class);
        $mw2->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$order) {
                $order[] = 'second';
                return $next->handle($req);
            }
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        (new MiddlewareStack([$mw1, $mw2]))->handle($request, $this->makeFinalHandler($response));

        $this->assertSame(['first', 'second'], $order);
    }

    public function testWithPrependedAddsToFront(): void
    {
        $order = [];

        $original = $this->createMock(MiddlewareInterface::class);
        $original->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$order) {
                $order[] = 'original';
                return $next->handle($req);
            }
        );

        $prepended = $this->createMock(MiddlewareInterface::class);
        $prepended->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$order) {
                $order[] = 'prepended';
                return $next->handle($req);
            }
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        (new MiddlewareStack([$original]))->withPrepended($prepended)
            ->handle($request, $this->makeFinalHandler($response));

        $this->assertSame(['prepended', 'original'], $order);
    }

    public function testWithAppendedAddsToBack(): void
    {
        $order = [];

        $original = $this->createMock(MiddlewareInterface::class);
        $original->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$order) {
                $order[] = 'original';
                return $next->handle($req);
            }
        );

        $appended = $this->createMock(MiddlewareInterface::class);
        $appended->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$order) {
                $order[] = 'appended';
                return $next->handle($req);
            }
        );

        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        (new MiddlewareStack([$original]))->withAppended($appended)
            ->handle($request, $this->makeFinalHandler($response));

        $this->assertSame(['original', 'appended'], $order);
    }

    public function testWithPrependedIsImmutable(): void
    {
        $stack = new MiddlewareStack([]);
        $this->assertNotSame($stack, $stack->withPrepended($this->createMock(MiddlewareInterface::class)));
    }

    public function testWithAppendedIsImmutable(): void
    {
        $stack = new MiddlewareStack([]);
        $this->assertNotSame($stack, $stack->withAppended($this->createMock(MiddlewareInterface::class)));
    }

    public function testFinalHandlerIsCalledWhenStackIsEmpty(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $result = (new MiddlewareStack([]))->handle($request, $this->makeFinalHandler($response));

        $this->assertSame($response, $result);
    }
}
