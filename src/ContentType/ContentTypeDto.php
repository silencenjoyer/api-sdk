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

namespace Silencenjoyer\ApiSdk\ContentType;

class ContentTypeDto
{
    public string $contentType;

    public function __construct(string $contentType)
    {
        $this->contentType = $contentType;
    }
}
