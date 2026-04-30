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

use Silencenjoyer\ApiSdk\AbstractApi;
use Silencenjoyer\ApiSdk\ContentType\Assume\ContentTypeAssumeInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareStack;
use Silencenjoyer\ApiSdk\Parsers\ParserInterface;
use Silencenjoyer\ApiSdk\Request\RequestBuilderInterface;
use Silencenjoyer\ApiSdk\Response\ResponseParserInterface;
use Silencenjoyer\ApiSdk\Serializers\SerializerInterface;

interface ApiBuilderInterface
{
    public function withCommandMiddleware(string $commandClass, MiddlewareInterface $middleware): self;

    public function withAddedParser(string $format, ParserInterface $parser): self;

    public function withParsers(array $parsers): self;

    public function withAddedSerializer(string $format, SerializerInterface $serializer): self;

    public function withSerializers(array $serializers): self;

    public function withFormats(string ...$formats): self;

    public function withAddedFormats(string ...$formats): self;

    public function withParserContentTypeAssume(ContentTypeAssumeInterface $assume): self;

    public function withMiddlewares(array $middlewares): self;

    /**
     * @param \Closure(RequestBuilderInterface, ResponseParserInterface, HandlerInterface, MiddlewareStack): AbstractApi $factory
     */
    public function withFactory(\Closure $factory): self;

    public function build(): AbstractApi;
}
