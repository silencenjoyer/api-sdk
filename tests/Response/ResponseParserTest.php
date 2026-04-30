<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeReaderInterface;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveParserException;
use Silencenjoyer\ApiSdk\Parsers\JsonParser;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolver;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolverInterface;
use Silencenjoyer\ApiSdk\Response\ParsedResponseInterface;
use Silencenjoyer\ApiSdk\Response\ResponseParser;

final class ResponseParserTest extends TestCase
{
    private function makeParserResolver(): ParserResolver
    {
        return new ParserResolver(
            $this->createMock(ContentTypeReaderInterface::class),
            [Format::JSON => new JsonParser()]
        );
    }

    public function testParsesBodyAndReturnsWrappedResult(): void
    {
        $psr17 = new Psr17Factory();
        $response = new \Nyholm\Psr7\Response(200, [], $psr17->createStream('{"x":42}'));

        $parsed = (new ResponseParser($this->makeParserResolver()))
            ->parse($response, new ContentTypeDto(Format::JSON));

        $this->assertInstanceOf(ParsedResponseInterface::class, $parsed);
        $this->assertSame(['x' => 42], $parsed->asArray());
    }

    public function testStreamIsRewoundAfterParsing(): void
    {
        $psr17 = new Psr17Factory();
        $response = new \Nyholm\Psr7\Response(200, [], $psr17->createStream('{"a":1}'));

        (new ResponseParser($this->makeParserResolver()))
            ->parse($response, new ContentTypeDto(Format::JSON));

        $this->assertSame(0, $response->getBody()->tell());
    }

    public function testOriginalResponseIsPreserved(): void
    {
        $psr17 = new Psr17Factory();
        $response = new \Nyholm\Psr7\Response(200, ['X-Custom' => 'yes'], $psr17->createStream('{}'));

        $parsed = (new ResponseParser($this->makeParserResolver()))
            ->parse($response, new ContentTypeDto(Format::JSON));

        $this->assertSame($response, $parsed->getOriginalResponse());
    }

    public function testThrowsWhenParserCannotBeResolved(): void
    {
        $parserResolver = $this->createMock(ParserResolverInterface::class);
        $parserResolver->method('resolve')->willThrowException(new UnableToResolveParserException('no parser'));

        $this->expectException(UnableToResolveParserException::class);
        (new ResponseParser($parserResolver))->parse((new Psr17Factory())->createResponse(), null);
    }
}
