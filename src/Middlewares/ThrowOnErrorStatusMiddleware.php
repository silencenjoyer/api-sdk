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
use Silencenjoyer\ApiSdk\Exceptions\ClientHttpException;
use Silencenjoyer\ApiSdk\Exceptions\ServerHttpException;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;

final class ThrowOnErrorStatusMiddleware implements MiddlewareInterface
{
    private const SERVER = 500;
    private const CLIENT = 400;

    public function handle(RequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $status = $response->getStatusCode();

        if ($status >= self::SERVER) {
            throw new ServerHttpException($response);
        }

        if ($status >= self::CLIENT) {
            throw new ClientHttpException($response);
        }

        return $response;
    }
}
