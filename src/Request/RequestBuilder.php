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

namespace Silencenjoyer\ApiSdk\Request;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Silencenjoyer\ApiSdk\Authentication\AuthInterface;
use Silencenjoyer\ApiSdk\Commands\CommandMetaDto;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Exceptions\UnauthorizedPrivateApiException;
use Silencenjoyer\ApiSdk\Serializers\Resolvers\SerializerResolverInterface;

final class RequestBuilder implements RequestBuilderInterface
{
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private SerializerResolverInterface $serializerResolver;

    public function __construct(
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        SerializerResolverInterface $serializerResolver
    ) {
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->serializerResolver = $serializerResolver;
    }

    public function build(CommandMetaDto $dto, string $baseUrl, ?AuthInterface $auth): RequestInterface
    {
        $uri = sprintf('%s/%s', rtrim($baseUrl, '/'), ltrim($dto->path, '/'));
        $request = $this->requestFactory->createRequest($dto->method, $uri);

        if (!$dto->public) {
            if ($auth === null) {
                throw new UnauthorizedPrivateApiException();
            }
            $request = $auth->withCredentials($request);
        }

        foreach ($dto->headers as $key => $value) {
            $request = $request->withAddedHeader($key, $value);
        }

        return $this->applyParams($dto, $request);
    }

    private function applyParams(CommandMetaDto $dto, RequestInterface $request): RequestInterface
    {
        if ($dto->queryParams !== []) {
            $query = $this->serializerResolver
                ->resolve($request, new ContentTypeDto(Format::URLENCODED))
                ->serialize($dto->queryParams);
            $request = $request->withUri($request->getUri()->withQuery($query));
        }

        if ($dto->bodyParams !== []) {
            $body = $this->serializerResolver
                ->resolve($request, $dto->requestContentType)
                ->serialize($dto->bodyParams);
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        return $request;
    }
}
