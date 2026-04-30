<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\ContentType\Assume\JsonAssume;
use Silencenjoyer\ApiSdk\ContentType\AssumeContentTypeReader;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;

final class ContentTypeReadersTest extends TestCase
{
    public function testAssumeContentTypeReaderDetectsJsonAndRewinds(): void
    {
        $psr17 = new Psr17Factory();
        $response = new \Nyholm\Psr7\Response(200, [], $psr17->createStream('{"k":1}'));

        $reader = new AssumeContentTypeReader([new JsonAssume()]);
        $dto = $reader->readValue($response);

        $this->assertInstanceOf(ContentTypeDto::class, $dto);
        $this->assertSame('json', $dto->contentType);
        $this->assertSame(0, $response->getBody()->tell());
    }

    public function testAssumeContentTypeReaderReturnsNullWhenNotSupported(): void
    {
        $psr17 = new Psr17Factory();
        $response = new \Nyholm\Psr7\Response(200, [], $psr17->createStream('not json'));
        $reader = new AssumeContentTypeReader([new JsonAssume()]);

        $this->assertNull($reader->readValue($response));
    }
}
