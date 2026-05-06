<?php

/*
 * This file is part of the API-SDK package.
 *
 * (c) Andrew Gebrich <an_gebrich@outlook.com>
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Silencenjoyer\ApiSdk\Parsers\Resolvers;

use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\ContentType\AbstractContentTypeResolver;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveParserException;
use Silencenjoyer\ApiSdk\Parsers\ParserInterface;

/**
 * @extends AbstractContentTypeResolver<ParserInterface>
 */
final class ParserResolver extends AbstractContentTypeResolver implements ParserResolverInterface
{
    public function withContentTypeParser(string $contentType, ParserInterface $parser): self
    {
        $clone = clone $this;
        $clone->contentTypeMapper[$contentType] = $parser;
        return $clone;
    }

    /**
     * @throws UnableToResolveParserException
     */
    public function resolve(ResponseInterface $response, ?ContentTypeDto $contentTypeDto = null): ParserInterface
    {
        try {
            return $this->resolveFromMapper($response, $contentTypeDto);
        } catch (UnableToResolveParserException $e) {
            throw new UnableToResolveParserException($e->getMessage(), $response);
        }
    }

    protected function throwNotResolved(string $reason): void
    {
        throw new UnableToResolveParserException('Unable to resolve parser: ' . $reason);
    }
}
