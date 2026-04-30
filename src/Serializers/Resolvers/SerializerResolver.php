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

namespace Silencenjoyer\ApiSdk\Serializers\Resolvers;

use Psr\Http\Message\RequestInterface;
use Silencenjoyer\ApiSdk\ContentType\AbstractContentTypeResolver;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveSerializerException;
use Silencenjoyer\ApiSdk\Serializers\SerializerInterface;

/**
 * @extends AbstractContentTypeResolver<SerializerInterface>
 */
final class SerializerResolver extends AbstractContentTypeResolver implements SerializerResolverInterface
{
    public function withContentSerializer(string $contentType, SerializerInterface $serializer): self
    {
        $clone = clone $this;
        $clone->contentTypeMapper[$contentType] = $serializer;
        return $clone;
    }

    /**
     * @throws UnableToResolveSerializerException
     */
    public function resolve(RequestInterface $request, ?ContentTypeDto $contentTypeDto = null): SerializerInterface
    {
        return $this->resolveFromMapper($request, $contentTypeDto);
    }

    protected function throwNotResolved(string $reason): void
    {
        throw new UnableToResolveSerializerException('Unable to resolve serializer: ' . $reason);
    }
}
