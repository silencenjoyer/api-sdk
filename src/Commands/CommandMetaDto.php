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

namespace Silencenjoyer\ApiSdk\Commands;

use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;

final class CommandMetaDto
{
    public bool $public;
    public string $method;
    public string $path;
    public array $headers;
    public array $queryParams;
    public array $bodyParams;
    public ?ContentTypeDto $requestContentType;
    public ?ContentTypeDto $answerContentType;

    public function __construct(
        bool $public,
        string $method,
        string $path,
        array $headers,
        array $queryParams,
        array $bodyParams,
        ?ContentTypeDto $requestContentType,
        ?ContentTypeDto $answerContentType
    ) {
        $this->public = $public;
        $this->method = $method;
        $this->path = $path;
        $this->headers = $headers;
        $this->queryParams = $queryParams;
        $this->bodyParams = $bodyParams;
        $this->requestContentType = $requestContentType;
        $this->answerContentType = $answerContentType;
    }
}
