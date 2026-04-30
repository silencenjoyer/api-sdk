<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Silencenjoyer\ApiSdk\Authentication\AuthInterface;
use Silencenjoyer\ApiSdk\Commands\CommandMetaDto;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeReaderInterface;
use Silencenjoyer\ApiSdk\Exceptions\UnauthorizedPrivateApiException;
use Silencenjoyer\ApiSdk\Request\RequestBuilder;
use Silencenjoyer\ApiSdk\Serializers\JsonSerializer;
use Silencenjoyer\ApiSdk\Serializers\Resolvers\SerializerResolver;
use Silencenjoyer\ApiSdk\Serializers\UrlEncodedSerializer;

final class RequestBuilderTest extends TestCase
{
    private Psr17Factory $psr17;

    protected function setUp(): void
    {
        $this->psr17 = new Psr17Factory();
    }

    private function makeBuilder(): RequestBuilder
    {
        return new RequestBuilder(
            $this->psr17,
            $this->psr17,
            new SerializerResolver(
                $this->createMock(ContentTypeReaderInterface::class),
                [
                    Format::JSON => new JsonSerializer(),
                    Format::URLENCODED => new UrlEncodedSerializer(),
                ]
            )
        );
    }

    private function makeDto(
        bool $public = true,
        array $headers = [],
        array $queryParams = [],
        array $bodyParams = [],
        string $path = '/resource',
        ?ContentTypeDto $requestContentType = null
    ): CommandMetaDto {
        return new CommandMetaDto($public, 'GET', $path, $headers, $queryParams, $bodyParams, $requestContentType, null);
    }

    public function testBuildsUriFromBaseUrlAndCommandPath(): void
    {
        $request = $this->makeBuilder()->build($this->makeDto(), 'https://api.example.com/', null);
        $this->assertSame('https://api.example.com/resource', (string) $request->getUri());
    }

    public function testTrimsSlashesOnUriJoin(): void
    {
        $request = $this->makeBuilder()->build($this->makeDto(true, [], [], [], '/users/1'), 'https://api.example.com', null);
        $this->assertSame('https://api.example.com/users/1', (string) $request->getUri());
    }

    public function testPublicCommandSkipsAuth(): void
    {
        $auth = $this->createMock(AuthInterface::class);
        $auth->expects($this->never())->method('withCredentials');

        $request = $this->makeBuilder()->build($this->makeDto(), 'https://api.example.com', $auth);
        $this->assertFalse($request->hasHeader('Authorization'));
    }

    public function testPrivateCommandWithoutAuthThrows(): void
    {
        $this->expectException(UnauthorizedPrivateApiException::class);
        $this->makeBuilder()->build($this->makeDto(false), 'https://api.example.com', null);
    }

    public function testPrivateCommandWithAuthAppliesCredentials(): void
    {
        $auth = $this->createMock(AuthInterface::class);
        $auth->method('withCredentials')->willReturnCallback(
            function (RequestInterface $req) {
                return $req->withHeader('Authorization', 'Bearer token');
            }
        );

        $request = $this->makeBuilder()->build($this->makeDto(false), 'https://api.example.com', $auth);
        $this->assertSame('Bearer token', $request->getHeaderLine('Authorization'));
    }

    public function testCommandHeadersAreAddedToRequest(): void
    {
        $dto = $this->makeDto(true, ['X-Custom' => 'value', 'Accept' => 'application/json']);
        $request = $this->makeBuilder()->build($dto, 'https://api.example.com', null);

        $this->assertSame('value', $request->getHeaderLine('X-Custom'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testQueryParamsAreAppendedToUri(): void
    {
        $dto = $this->makeDto(true, [], ['page' => '2', 'limit' => '10']);
        $request = $this->makeBuilder()->build($dto, 'https://api.example.com', null);

        $this->assertStringContainsString('page=2', $request->getUri()->getQuery());
        $this->assertStringContainsString('limit=10', $request->getUri()->getQuery());
    }

    public function testBodyParamsAreSerializedToJsonBody(): void
    {
        $dto = $this->makeDto(true, [], [], ['name' => 'Alice', 'age' => 30], '/resource', new ContentTypeDto(Format::JSON));

        $request = $this->makeBuilder()->build($dto, 'https://api.example.com', null);
        $body = json_decode($request->getBody()->getContents(), true);

        $this->assertSame(['name' => 'Alice', 'age' => 30], $body);
    }

    public function testEmptyBodyProducesNoBody(): void
    {
        $request = $this->makeBuilder()->build($this->makeDto(), 'https://api.example.com', null);
        $this->assertSame('', $request->getBody()->getContents());
    }
}
