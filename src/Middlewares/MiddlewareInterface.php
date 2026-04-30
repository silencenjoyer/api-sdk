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

interface MiddlewareInterface
{
    /**
     * @param RequestInterface $request
     * @param HandlerInterface $handler
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request, HandlerInterface $handler): ResponseInterface;
}
