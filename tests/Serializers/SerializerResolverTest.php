<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeReaderInterface;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveSerializerException;
use Silencenjoyer\ApiSdk\Serializers\JsonSerializer;
use Silencenjoyer\ApiSdk\Serializers\Resolvers\SerializerResolver;
use Silencenjoyer\ApiSdk\Serializers\SerializerInterface;

final class SerializerResolverTest extends TestCase
{
    public function testResolveWithPredefinedType(): void
    {
        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $resolver = new SerializerResolver($reader, []);

        $custom = new class implements SerializerInterface {
            public function serialize(array $data): string { return 'X'; }
        };

        $resolver = $resolver->withContentSerializer('application/custom', $custom);

        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('POST', 'https://e/');
        $serializer = $resolver->resolve($request, new ContentTypeDto('application/custom'));
        $this->assertSame($custom, $serializer);
    }

    public function testResolveFallsBackToReader(): void
    {
        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $reader->method('readValue')->willReturn(new ContentTypeDto(Format::JSON));

        $resolver = new SerializerResolver($reader, [Format::JSON => new JsonSerializer()]);

        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('POST', 'https://e/')->withHeader('Content-Type', 'application/json');
        $serializer = $resolver->resolve($request, null);

        $this->assertInstanceOf(JsonSerializer::class, $serializer);
    }

    public function testResolveThrowsWhenContentTypeNotDefined(): void
    {
        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $reader->method('readValue')->willReturn(null);
        $resolver = new SerializerResolver($reader, []);

        $request = (new Psr17Factory())->createRequest('POST', 'https://e/');

        $this->expectException(UnableToResolveSerializerException::class);
        $this->expectExceptionMessageMatches('/not defined/i');
        $resolver->resolve($request, null);
    }

    public function testResolveThrowsWhenContentTypeUnknown(): void
    {
        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $reader->method('readValue')->willReturn(new ContentTypeDto('application/unknown'));
        $resolver = new SerializerResolver($reader, []);

        $request = (new Psr17Factory())->createRequest('POST', 'https://e/');

        $this->expectException(UnableToResolveSerializerException::class);
        $resolver->resolve($request, null);
    }
}
