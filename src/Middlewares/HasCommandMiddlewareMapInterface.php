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

interface HasCommandMiddlewareMapInterface
{
    /**
     * @return static
     */
    public function withCommandMiddlewareMap(array $commandMiddlewareMap): self;

    /**
     * @return static
     */
    public function withAppendedCommandMiddleware(string $commandClass, MiddlewareInterface $middleware): self;
}
