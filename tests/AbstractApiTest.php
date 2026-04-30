<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\AbstractApi;
use Silencenjoyer\ApiSdk\Authentication\AuthInterface;
use Silencenjoyer\ApiSdk\Commands\AbstractCommand;
use Silencenjoyer\ApiSdk\Commands\CommandInterface;
use Silencenjoyer\ApiSdk\Commands\CommandMetaDto;
use Silencenjoyer\ApiSdk\Commands\DispatchableCommandInterface;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Exceptions\UnauthorizedPrivateApiException;
use Silencenjoyer\ApiSdk\Exceptions\UnsupportedCommandException;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareStack;
use Psr\Http\Message\RequestInterface;
use Silencenjoyer\ApiSdk\Request\RequestBuilderInterface;
use Silencenjoyer\ApiSdk\Response\ParsedResponseInterface;
use Silencenjoyer\ApiSdk\Response\ResponseParserInterface;

final class AbstractApiTest extends TestCase
{
    private function makeApi(
        ?RequestBuilderInterface $requestBuilder = null,
        ?ResponseParserInterface $responseParser = null,
        ?HandlerInterface $finalHandler = null
    ): AbstractApi {
        return new class(
            $requestBuilder ?? $this->createMock(RequestBuilderInterface::class),
            $responseParser ?? $this->createMock(ResponseParserInterface::class),
            $finalHandler ?? $this->createMock(HandlerInterface::class),
            new MiddlewareStack([])
        ) extends AbstractApi {
            public bool $supported = true;

            protected function getUrl(): string
            {
                return 'https://example.com';
            }

            protected function supports(CommandInterface $command): bool
            {
                return $this->supported;
            }
        };
    }

    private function makeCommand(bool $public = true, ?ContentTypeDto $answerType = null): CommandInterface
    {
        return new class($public, $answerType) implements CommandInterface {
            private bool $public;
            private ?ContentTypeDto $answerType;

            public function __construct(bool $public, ?ContentTypeDto $answerType)
            {
                $this->public = $public;
                $this->answerType = $answerType;
            }

            public function getDto(): CommandMetaDto
            {
                return new CommandMetaDto($this->public, 'GET', '/test', [], [], [], null, $this->answerType);
            }
        };
    }

    public function testExecuteThrowsOnUnsupportedCommand(): void
    {
        $api = $this->makeApi();
        $api->supported = false;

        $this->expectException(UnsupportedCommandException::class);
        $api->execute($this->makeCommand());
    }

    public function testExecuteThrowsForPrivateCommandWithoutAuth(): void
    {
        $requestBuilder = $this->createMock(RequestBuilderInterface::class);
        $requestBuilder->method('build')->willThrowException(new UnauthorizedPrivateApiException());

        $api = $this->makeApi($requestBuilder);

        $this->expectException(UnauthorizedPrivateApiException::class);
        $api->execute($this->makeCommand(false));
    }

    public function testExecuteReturnsParsedResponse(): void
    {
        $psr17 = new Psr17Factory();
        $parsedResponse = $this->createMock(ParsedResponseInterface::class);

        $requestBuilder = $this->createMock(RequestBuilderInterface::class);
        $requestBuilder->method('build')->willReturn($psr17->createRequest('GET', 'https://example.com/test'));

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('handle')->willReturn($psr17->createResponse(200));

        $responseParser = $this->createMock(ResponseParserInterface::class);
        $responseParser->method('parse')->willReturn($parsedResponse);

        $api = $this->makeApi($requestBuilder, $responseParser, $handler);

        $this->assertSame($parsedResponse, $api->execute($this->makeCommand()));
    }

    public function testExecutePassesAuthToRequestBuilder(): void
    {
        $psr17 = new Psr17Factory();
        $auth = $this->createMock(AuthInterface::class);

        $requestBuilder = $this->createMock(RequestBuilderInterface::class);
        $requestBuilder->expects($this->once())
            ->method('build')
            ->with($this->anything(), $this->anything(), $auth)
            ->willReturn($psr17->createRequest('GET', 'https://example.com'));

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('handle')->willReturn($psr17->createResponse());

        $responseParser = $this->createMock(ResponseParserInterface::class);
        $responseParser->method('parse')->willReturn($this->createMock(ParsedResponseInterface::class));

        $this->makeApi($requestBuilder, $responseParser, $handler)
            ->withAuthentication($auth)
            ->execute($this->makeCommand());
    }

    public function testWithAuthenticationIsImmutable(): void
    {
        $api = $this->makeApi();
        $api2 = $api->withAuthentication($this->createMock(AuthInterface::class));

        $this->assertNotSame($api, $api2);
        $this->assertInstanceOf(AbstractApi::class, $api2);
    }

    public function testWithCommandMiddlewareMapIsImmutable(): void
    {
        $api = $this->makeApi();
        $this->assertNotSame($api, $api->withCommandMiddlewareMap([]));
    }

    public function testCreateCommandThrowsOnUnsupported(): void
    {
        $api = $this->makeApi();
        $api->supported = false;

        $commandClass = get_class(new class extends AbstractCommand {
            public function isPublic(): bool { return true; }
            public function getPath(): string { return '/'; }
            public function getMethod(): string { return 'GET'; }
        });

        $this->expectException(UnsupportedCommandException::class);
        $api->createCommand($commandClass);
    }

    public function testCommandSpecificMiddlewareIsCalled(): void
    {
        $psr17 = new Psr17Factory();
        $called = false;

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('handle')->willReturnCallback(
            function (RequestInterface $req, HandlerInterface $next) use (&$called) {
                $called = true;
                return $next->handle($req);
            }
        );

        $requestBuilder = $this->createMock(RequestBuilderInterface::class);
        $requestBuilder->method('build')->willReturn($psr17->createRequest('GET', 'https://example.com'));

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('handle')->willReturn($psr17->createResponse());

        $responseParser = $this->createMock(ResponseParserInterface::class);
        $responseParser->method('parse')->willReturn($this->createMock(ParsedResponseInterface::class));

        $command = $this->makeCommand();
        $commandClass = get_class($command);

        $this->makeApi($requestBuilder, $responseParser, $handler)
            ->withCommandMiddlewareMap([$commandClass => [$middleware]])
            ->execute($command);

        $this->assertTrue($called);
    }

    public function testCreateCommandReturnsDispatchableCommand(): void
    {
        $psr17 = new Psr17Factory();

        $requestBuilder = $this->createMock(RequestBuilderInterface::class);
        $requestBuilder->method('build')->willReturn($psr17->createRequest('GET', 'https://example.com'));

        $handler = $this->createMock(HandlerInterface::class);
        $handler->method('handle')->willReturn($psr17->createResponse());

        $responseParser = $this->createMock(ResponseParserInterface::class);
        $responseParser->method('parse')->willReturn($this->createMock(ParsedResponseInterface::class));

        $api = $this->makeApi($requestBuilder, $responseParser, $handler);

        $commandClass = get_class(new class extends AbstractCommand {
            public function isPublic(): bool { return true; }
            public function getPath(): string { return '/cmd'; }
            public function getMethod(): string { return 'POST'; }
        });

        $this->assertInstanceOf(DispatchableCommandInterface::class, $api->createCommand($commandClass));
    }
}
