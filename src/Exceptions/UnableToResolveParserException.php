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

class UnableToResolveParserException extends ApiException
{
    private ?ResponseInterface $response;
    public function __construct(?ResponseInterface $response = null, string $message = 'Unable to resolve parser.')
    {
        $this->response = $response;

        parent::__construct($message);
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
