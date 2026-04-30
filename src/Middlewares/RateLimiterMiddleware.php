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
use Silencenjoyer\RateLimit\Limiters\LimiterInterface;

final class RateLimiterMiddleware implements MiddlewareInterface
{
    private LimiterInterface $limiter;

    public function __construct(LimiterInterface $limiter)
    {
        $this->limiter = $limiter;
    }

    public function handle(RequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        /** @var ResponseInterface $response */
        $response = $this->limiter->control(
            static function () use ($request, $handler): ResponseInterface {
                return $handler->handle($request);
            }
        );

        return $response;
    }
}
