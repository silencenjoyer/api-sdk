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

namespace Silencenjoyer\ApiSdk\Authentication;

use Psr\Http\Message\RequestInterface;

abstract class AbstractHeaderAuth implements AuthInterface
{
    /**
     * @return string
     */
    abstract protected function getHeaderName(): string;

    /**
     * @return string
     */
    abstract protected function getHeaderValue(): string;

    /**
     * {@inheritDoc}
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function withCredentials(RequestInterface $request): RequestInterface
    {
        return $request->withHeader($this->getHeaderName(), $this->getHeaderValue());
    }
}
