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
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveSerializerException;
use Silencenjoyer\ApiSdk\Serializers\SerializerInterface;

interface SerializerResolverInterface
{
    /**
     * @param RequestInterface $request
     * @param ContentTypeDto|null $contentTypeDto
     * @return SerializerInterface
     * @throws UnableToResolveSerializerException
     */
    public function resolve(RequestInterface $request, ?ContentTypeDto $contentTypeDto = null): SerializerInterface;
}
