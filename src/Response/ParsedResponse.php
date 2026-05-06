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

namespace Silencenjoyer\ApiSdk\Response;

use Psr\Http\Message\ResponseInterface;
use stdClass;

final class ParsedResponse implements ParsedResponseInterface
{
    private array $body;
    private ResponseInterface $response;

    public function __construct(array $parsedBody, ResponseInterface $response)
    {
        $this->body = $parsedBody;
        $this->response = $response;
    }

    public function asArray(): array
    {
        return $this->body;
    }

    public function asObject(): stdClass
    {
        return (object) $this->body;
    }

    public function getHttpResponse(): ResponseInterface
    {
        return $this->response;
    }
}
