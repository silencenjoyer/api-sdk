<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeReaderInterface;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveParserException;
use Silencenjoyer\ApiSdk\Parsers\ParserInterface;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolver;

final class ParserResolverTest extends TestCase
{
    public function testResolveWithPredefinedType(): void
    {
        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $resolver = new ParserResolver($reader, []);

        $dummyParser = new class implements ParserInterface {
            public function parse(string $contents): array
            {
                return ['ok' => $contents];
            }
        };

        $resolver = $resolver->withContentTypeParser('application/custom', $dummyParser);

        $response = $this->createMock(ResponseInterface::class);
        $parser = $resolver->resolve($response, new ContentTypeDto('application/custom'));
        $this->assertSame($dummyParser, $parser);
    }

    public function testResolveFallsBackToReader(): void
    {
        $dummyParser = new class implements ParserInterface {
            public function parse(string $contents): array { return ['via' => 'reader']; }
        };

        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $reader->method('readValue')->willReturn(new ContentTypeDto(Format::JSON));

        $resolver = new ParserResolver($reader, [Format::JSON => $dummyParser]);
        $parser = $resolver->resolve($this->createMock(ResponseInterface::class), null);

        $this->assertSame($dummyParser, $parser);
    }

    public function testResolveThrowsWhenUnknown(): void
    {
        $reader = $this->createMock(ContentTypeReaderInterface::class);
        $resolver = new ParserResolver($reader, []);

        $response = $this->createMock(ResponseInterface::class);
        $this->expectException(UnableToResolveParserException::class);
        $resolver->resolve($response, new ContentTypeDto('application/unknown'));
    }
}
