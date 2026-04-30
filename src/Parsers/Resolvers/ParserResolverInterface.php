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
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Exceptions\UnableToResolveParserException;
use Silencenjoyer\ApiSdk\Parsers\ParserInterface;

interface ParserResolverInterface
{
    /**
     * @param ResponseInterface $response
     * @param ContentTypeDto|null $contentTypeDto
     * @return ParserInterface
     * @throws UnableToResolveParserException
     */
    public function resolve(ResponseInterface $response, ?ContentTypeDto $contentTypeDto = null): ParserInterface;
}
