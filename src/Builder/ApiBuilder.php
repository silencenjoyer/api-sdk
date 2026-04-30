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

namespace Silencenjoyer\ApiSdk\Builder;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Silencenjoyer\ApiSdk\AbstractApi;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\Assume\ContentTypeAssumeInterface;
use Silencenjoyer\ApiSdk\ContentType\Assume\JsonAssume;
use Silencenjoyer\ApiSdk\ContentType\AssumeContentTypeReader;
use Silencenjoyer\ApiSdk\ContentType\CompositeContentTypeReader;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeHeaderReader;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Handlers\RequestHandler;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareStack;
use Silencenjoyer\ApiSdk\Request\RequestBuilderInterface;
use Silencenjoyer\ApiSdk\Response\ResponseParserInterface;
use Silencenjoyer\ApiSdk\Request\RequestBuilder;
use Silencenjoyer\ApiSdk\Response\ResponseParser;
use Silencenjoyer\ApiSdk\Parsers\JsonParser;
use Silencenjoyer\ApiSdk\Parsers\ParserInterface;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolver;
use Silencenjoyer\ApiSdk\Parsers\Resolvers\ParserResolverInterface;
use Silencenjoyer\ApiSdk\Serializers\JsonSerializer;
use Silencenjoyer\ApiSdk\Serializers\Resolvers\SerializerResolver;
use Silencenjoyer\ApiSdk\Serializers\Resolvers\SerializerResolverInterface;
use Silencenjoyer\ApiSdk\Serializers\SerializerInterface;
use Silencenjoyer\ApiSdk\Serializers\UrlEncodedSerializer;

final class ApiBuilder implements ApiBuilderInterface
{
    /**
     * @var class-string<AbstractApi>
     */
    private string $className;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private array $middlewares = [];
    private array $parsers;
    private array $serializers;
    private array $assumes;
    private array $formats = [
        Format::JSON,
        Format::URLENCODED,
    ];
    private array $commandMiddlewareMap = [];
    /**
     * @var \Closure(RequestBuilderInterface, ResponseParserInterface, HandlerInterface, MiddlewareStack): AbstractApi|null
     */
    private ?\Closure $factory = null;

    /**
     * @param class-string<AbstractApi> $className
     */
    private function __construct(
        string $className,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory
    ) {
        $this->className = $className;
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;

        $this->parsers = $this->getDefaultParsers();
        $this->serializers = $this->getDefaultSerializers();
        $this->assumes = $this->getDefaultAssumes();
    }

    private function getDefaultParsers(): array
    {
        return [
            Format::JSON => new JsonParser(),
        ];
    }

    private function getDefaultSerializers(): array
    {
        return [
            Format::JSON => new JsonSerializer(),
            Format::URLENCODED => new UrlEncodedSerializer(),
        ];
    }

    /**
     * @return ParserResolverInterface
     */
    private function getParserResolver(): ParserResolverInterface
    {
        return new ParserResolver(
            $this->getParserContentTypeReader(),
            $this->parsers,
        );
    }

    private function getSerializerResolver(): SerializerResolverInterface
    {
        return new SerializerResolver(
            $this->createContentTypeHeaderReader(),
            $this->serializers,
        );
    }

    private function createContentTypeHeaderReader(): ContentTypeHeaderReader
    {
        return new ContentTypeHeaderReader($this->formats);
    }

    private function getDefaultAssumes(): array
    {
        return [
            new JsonAssume(),
        ];
    }

    private function getParserContentTypeReader(): CompositeContentTypeReader
    {
        return new CompositeContentTypeReader([
            $this->createContentTypeHeaderReader(),
            new AssumeContentTypeReader($this->assumes),
        ]);
    }

    /**
     * @param class-string<AbstractApi> $className
     */
    public static function create(
        string $className,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ): ApiBuilderInterface {
        return new self(
            $className,
            $client ?? self::discoverClient(),
            $requestFactory ?? self::discoverRequestFactory(),
            $streamFactory ?? self::discoverStreamFactory(),
        );
    }

    private static function discoverClient(): ClientInterface
    {
        return Psr18ClientDiscovery::find();
    }

    private static function discoverRequestFactory(): RequestFactoryInterface
    {
        return Psr17FactoryDiscovery::findRequestFactory();
    }

    private static function discoverStreamFactory(): StreamFactoryInterface
    {
        return Psr17FactoryDiscovery::findStreamFactory();
    }

    public function withCommandMiddleware(string $commandClass, MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->commandMiddlewareMap[$commandClass][] = $middleware;
        return $clone;
    }

    public function withAddedParser(string $format, ParserInterface $parser): self
    {
        $clone = clone $this;
        $clone->parsers[$format] = $parser;
        return $clone;
    }

    public function withParsers(array $parsers): self
    {
        $clone = clone $this;
        $clone->parsers = $parsers;
        return $clone;
    }

    public function withAddedSerializer(string $format, SerializerInterface $serializer): self
    {
        $clone = clone $this;
        $clone->serializers[$format] = $serializer;
        return $clone;
    }

    public function withSerializers(array $serializers): self
    {
        $clone = clone $this;
        $clone->serializers = $serializers;
        return $clone;
    }

    public function withFormats(string ...$formats): self
    {
        $clone = clone $this;
        $clone->formats = $formats;
        return $clone;
    }

    public function withAddedFormats(string ...$formats): self
    {
        $clone = clone $this;
        $clone->formats = array_merge($clone->formats, $formats);
        return $clone;
    }

    public function withParserContentTypeAssume(ContentTypeAssumeInterface $assume): self
    {
        $clone = clone $this;
        $clone->assumes[] = $assume;
        return $clone;
    }

    public function withMiddlewares(array $middlewares): self
    {
        $clone = clone $this;
        $clone->middlewares = array_merge($this->middlewares, $middlewares);
        return $clone;
    }

    /**
     * @param \Closure(RequestBuilderInterface, ResponseParserInterface, HandlerInterface, MiddlewareStack): AbstractApi $factory
     */
    public function withFactory(\Closure $factory): self
    {
        $clone = clone $this;
        $clone->factory = $factory;
        return $clone;
    }

    public function build(): AbstractApi
    {
        $requestBuilder = new RequestBuilder($this->requestFactory, $this->streamFactory, $this->getSerializerResolver());
        $responseParser = new ResponseParser($this->getParserResolver());
        $handler = new RequestHandler($this->client);
        $middlewareStack = new MiddlewareStack($this->middlewares);

        $instance = $this->factory !== null
            ? ($this->factory)($requestBuilder, $responseParser, $handler, $middlewareStack)
            : new $this->className($requestBuilder, $responseParser, $handler, $middlewareStack);

        return $instance->withCommandMiddlewareMap($this->commandMiddlewareMap);
    }
}
