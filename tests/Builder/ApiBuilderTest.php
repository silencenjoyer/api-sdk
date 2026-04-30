<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Silencenjoyer\ApiSdk\AbstractApi;
use Silencenjoyer\ApiSdk\Builder\ApiBuilder;
use Silencenjoyer\ApiSdk\Builder\ApiBuilderInterface;
use Silencenjoyer\ApiSdk\Commands\CommandInterface;
use Silencenjoyer\ApiSdk\ContentType\Assume\ContentTypeAssumeInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareStack;
use Silencenjoyer\ApiSdk\Parsers\ParserInterface;
use Silencenjoyer\ApiSdk\Request\RequestBuilderInterface;
use Silencenjoyer\ApiSdk\Response\ResponseParserInterface;
use Silencenjoyer\ApiSdk\Serializers\SerializerInterface;

class ApiBuilderStub extends AbstractApi
{
    protected function getUrl(): string
    {
        return 'https://example.com';
    }

    protected function supports(CommandInterface $command): bool
    {
        return true;
    }
}

final class ApiBuilderTest extends TestCase
{
    private function makeBuilder(): ApiBuilderInterface
    {
        $psr17 = new Psr17Factory();
        $client = $this->createMock(ClientInterface::class);
        return ApiBuilder::create(ApiBuilderStub::class, $client, $psr17, $psr17);
    }

    public function testBuildReturnsAbstractApiInstance(): void
    {
        $this->assertInstanceOf(AbstractApi::class, $this->makeBuilder()->build());
    }

    public function testBuildWithFactoryUsesFactoryClosure(): void
    {
        $psr17 = new Psr17Factory();
        $client = $this->createMock(ClientInterface::class);
        $factoryCalled = false;

        $api = ApiBuilder::create(ApiBuilderStub::class, $client, $psr17, $psr17)
            ->withFactory(function (
                RequestBuilderInterface $rb,
                ResponseParserInterface $rp,
                HandlerInterface $h,
                MiddlewareStack $ms
            ) use (&$factoryCalled) {
                $factoryCalled = true;
                return new ApiBuilderStub($rb, $rp, $h, $ms);
            })
            ->build();

        $this->assertTrue($factoryCalled);
        $this->assertInstanceOf(ApiBuilderStub::class, $api);
    }

    public function testWithCommandMiddlewareIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withCommandMiddleware('SomeCommand', $this->createMock(MiddlewareInterface::class));
        $this->assertNotSame($builder, $modified);
    }

    public function testWithAddedParserIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withAddedParser('custom', $this->createMock(ParserInterface::class));
        $this->assertNotSame($builder, $modified);
    }

    public function testWithParsersIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withParsers(['custom' => $this->createMock(ParserInterface::class)]);
        $this->assertNotSame($builder, $modified);
    }

    public function testWithAddedSerializerIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withAddedSerializer('custom', $this->createMock(SerializerInterface::class));
        $this->assertNotSame($builder, $modified);
    }

    public function testWithSerializersIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withSerializers(['custom' => $this->createMock(SerializerInterface::class)]);
        $this->assertNotSame($builder, $modified);
    }

    public function testWithFormatsIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withFormats('json');
        $this->assertNotSame($builder, $modified);
    }

    public function testWithAddedFormatsIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withAddedFormats('xml');
        $this->assertNotSame($builder, $modified);
    }

    public function testWithParserContentTypeAssumeIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withParserContentTypeAssume($this->createMock(ContentTypeAssumeInterface::class));
        $this->assertNotSame($builder, $modified);
    }

    public function testWithMiddlewaresIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withMiddlewares([$this->createMock(MiddlewareInterface::class)]);
        $this->assertNotSame($builder, $modified);
    }

    public function testWithFactoryIsImmutable(): void
    {
        $builder = $this->makeBuilder();
        $modified = $builder->withFactory(static function () {});
        $this->assertNotSame($builder, $modified);
    }

    public function testBuildWithMiddlewareExecutesItDuringRequest(): void
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

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn(
            $psr17->createResponse(200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($psr17->createStream('[]'))
        );

        $api = ApiBuilder::create(ApiBuilderStub::class, $client, $psr17, $psr17)
            ->withMiddlewares([$middleware])
            ->build();

        $command = new class implements CommandInterface {
            public function getDto(): \Silencenjoyer\ApiSdk\Commands\CommandMetaDto
            {
                return new \Silencenjoyer\ApiSdk\Commands\CommandMetaDto(
                    true, 'GET', '/test', [], [], [], null, null
                );
            }
        };

        $api->execute($command);

        $this->assertTrue($called);
    }

    public function testBuildWithCommandMiddlewareAppliesItForMatchingCommand(): void
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

        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')->willReturn($psr17->createResponse(200)->withBody($psr17->createStream('[]'))->withHeader('Content-Type', 'application/json'));

        $command = new class implements CommandInterface {
            public function getDto(): \Silencenjoyer\ApiSdk\Commands\CommandMetaDto
            {
                return new \Silencenjoyer\ApiSdk\Commands\CommandMetaDto(
                    true, 'GET', '/test', [], [], [], null, null
                );
            }
        };

        $api = ApiBuilder::create(ApiBuilderStub::class, $client, $psr17, $psr17)
            ->withCommandMiddleware(get_class($command), $middleware)
            ->build();

        $api->execute($command);

        $this->assertTrue($called);
    }
}