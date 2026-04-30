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

use Closure;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;

final class RepeatableMiddleware implements MiddlewareInterface
{
    private int $maxRetries;
    /**
     * @var Closure(ResponseInterface): bool
     */
    private Closure $shouldRetry;

    /**
     * @param int $maxRetries Maximum number of retry attempts after the first failure.
     * @param Closure(ResponseInterface): bool|null $shouldRetry Returns true when the response warrants a retry.
     *                                                            Defaults to retrying on any 5xx status code.
     */
    public function __construct(int $maxRetries = 3, ?Closure $shouldRetry = null)
    {
        $this->maxRetries = $maxRetries;
        $this->shouldRetry = $shouldRetry ?? static function (ResponseInterface $response): bool {
            return $response->getStatusCode() >= 500;
        };
    }

    public function handle(RequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        for ($attempt = 0; $attempt < $this->maxRetries && ($this->shouldRetry)($response); $attempt++) {
            $response = $handler->handle($request);
        }

        return $response;
    }
}
