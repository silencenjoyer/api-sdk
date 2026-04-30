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

namespace Silencenjoyer\ApiSdk\Serializers;

class JsonSerializer implements SerializerInterface
{
    protected int $format;

    public function __construct(int $format = JSON_UNESCAPED_UNICODE)
    {
        $this->format = $format;
    }

    /**
     * This method serializes data to JSON format.
     *
     * @param array $data
     * @return string
     */
    public function serialize(array $data): string
    {
        return json_encode($data, $this->format);
    }
}
