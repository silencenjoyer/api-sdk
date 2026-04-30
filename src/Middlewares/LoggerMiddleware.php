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
use Psr\Log\LoggerInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;

final class LoggerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(RequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        $this->logger->info('Request body', ['body' => $request->getBody()->getContents()]);
        $request->getBody()->rewind();

        $response = $handler->handle($request);

        $this->logger->info('Response body', ['body' => $response->getBody()->getContents()]);
        $response->getBody()->rewind();

        return $response;
    }
}
