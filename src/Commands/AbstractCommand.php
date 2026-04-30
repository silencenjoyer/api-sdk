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

namespace Silencenjoyer\ApiSdk\Commands;

use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\Response\ParsedResponseInterface;

/**
 * Base class for all API commands.
 *
 * Extend this class to describe a single API endpoint. Override the abstract methods
 * to declare the HTTP semantics and the optional methods to attach parameters or headers.
 *
 * Implement a marker interface (e.g. NbuCommandInterface) so that the corresponding
 * {@see \Silencenjoyer\ApiSdk\AbstractApi} can identify commands it supports.
 */
abstract class AbstractCommand implements DispatchableCommandInterface
{
    private CommandHandlerInterface $handler;

    /**
     * Whether this command can be sent without authentication.
     *
     * Return false for endpoints that require a signed or authenticated request.
     * When false, the auth strategy attached via AbstractApi::withAuthentication() will sign the request.
     */
    abstract protected function isPublic(): bool;

    /**
     * The endpoint path relative to the API base URL.
     *
     * May include a static query string (e.g. '/exchange?json').
     * Dynamic query parameters should go in {@see getQueryParams()} instead.
     */
    abstract protected function getPath(): string;

    /**
     * The HTTP method for this command (GET, POST, PUT, DELETE, etc.).
     *
     * Use {@see \Silencenjoyer\ApiSdk\Constants\HttpMethod} constants to avoid typos.
     */
    abstract protected function getMethod(): string;

    /**
     * Request body parameters, serialized according to the active format.
     *
     * Override for commands that send a body.
     *
     * @return array<string, mixed>
     */
    protected function getBodyParams(): array
    {
        return [];
    }

    /**
     * Query string parameters appended to the URL.
     *
     * Merged with any static query string already present in {@see getPath()}.
     *
     * @return array<string, mixed>
     */
    protected function getQueryParams(): array
    {
        return [];
    }

    /**
     * Additional HTTP headers to include with this request.
     *
     * Merged with headers set by middleware (e.g. auth, content-type).
     * Use this for per-command headers such as Idempotency-Key or X-Api-Version.
     *
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        return [];
    }

    /**
     * Content-Type of the request body.
     *
     * Return null to let the SDK infer the content-type from the active format.
     * Override when this command must always use a specific format regardless of
     * the builder-level default (e.g. multipart/form-data for file uploads).
     */
    protected function getRequestContentType(): ?ContentTypeDto
    {
        return null;
    }

    /**
     * Expected Content-Type of the response.
     *
     * Return null to let the SDK detect the format from the response Content-Type header
     * (or fall back to the registered assumes when the header is absent).
     * Override when the endpoint returns a non-standard content-type that the SDK
     * cannot detect automatically.
     */
    protected function getAnswerContentType(): ?ContentTypeDto
    {
        return null;
    }

    final public function getDto(): CommandMetaDto
    {
        return new CommandMetaDto(
            $this->isPublic(),
            $this->getMethod(),
            $this->getPath(),
            $this->getHeaders(),
            $this->getQueryParams(),
            $this->getBodyParams(),
            $this->getRequestContentType(),
            $this->getAnswerContentType()
        );
    }

    final public function withHandler(CommandHandlerInterface $handler): DispatchableCommandInterface
    {
        $clone = clone $this;
        $clone->handler = $handler;
        return $clone;
    }

    final public function send(): ParsedResponseInterface
    {
        return $this->handler->execute($this);
    }
}
