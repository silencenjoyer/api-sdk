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

namespace Silencenjoyer\ApiSdk\Middlewares;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;

final class MiddlewareStack
{
    /**
     * @var list<MiddlewareInterface>
     */
    private array $middlewares;

    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    public function withAppended(MiddlewareInterface ...$middleware): self
    {
        $clone = clone $this;
        $clone->middlewares = array_merge($this->middlewares, $middleware);

        return $clone;
    }

    public function withPrepended(MiddlewareInterface ...$middleware): self
    {
        $clone = clone $this;
        array_unshift($clone->middlewares, ...$middleware);

        return $clone;
    }

    public function handle(RequestInterface $request, HandlerInterface $finalHandler): ResponseInterface
    {
        $handler = $finalHandler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = new MiddlewareHandler($middleware, $handler);
        }

        return $handler->handle($request);
    }
}
