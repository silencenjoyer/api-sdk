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

namespace Silencenjoyer\ApiSdk\Parsers;

class JsonParser implements ParserInterface
{
    /**
     * @param string $data
     * @return array
     */
    public function parse(string $data): array
    {
        return json_decode($data, true);
    }
}
