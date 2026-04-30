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

namespace Silencenjoyer\ApiSdk\Response;

use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolverInterface;

final class ResponseParser implements ResponseParserInterface
{
    private ParserResolverInterface $parserResolver;

    public function __construct(ParserResolverInterface $parserResolver)
    {
        $this->parserResolver = $parserResolver;
    }

    public function parse(ResponseInterface $response, ?ContentTypeDto $hint): ParsedResponseInterface
    {
        $parsed = $this->parserResolver
            ->resolve($response, $hint)
            ->parse($response->getBody()->getContents());

        $response->getBody()->rewind();

        return new ParsedResponse($parsed, $response);
    }
}
