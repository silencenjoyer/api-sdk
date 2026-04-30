<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeReaderInterface;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveParserException;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolver;

final class ParserResolverNullTest extends TestCase
{
    public function testThrowsCleanlyWhenReaderReturnsNull(): void
    {
        $reader = new class implements ContentTypeReaderInterface {
            public function readValue(MessageInterface $message): ?ContentTypeDto
            {
                return null;
            }
        };

        $resolver = new ParserResolver($reader, []);
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse(200);

        $this->expectException(UnableToResolveParserException::class);
        $resolver->resolve($response, null);
    }
}
