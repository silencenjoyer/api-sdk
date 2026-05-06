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

namespace Silencenjoyer\ApiSdk\Exceptions;

use Psr\Http\Message\ResponseInterface;

class HttpResponseException extends ApiException
{
    public function __construct(private ResponseInterface $response, string $message = '')
    {
        parent::__construct($message ?: 'HTTP error: ' . $response->getStatusCode());
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }
}
