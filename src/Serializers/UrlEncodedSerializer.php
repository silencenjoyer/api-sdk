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

class UrlEncodedSerializer implements SerializerInterface
{
    public const SEPARATOR = '&';

    public string $separator;
    public int $encodingType;

    public function __construct(string $separator = self::SEPARATOR, int $encodingType = PHP_QUERY_RFC1738)
    {
        $this->separator = $separator;
        $this->encodingType = $encodingType;
    }

    public function serialize(array $data): string
    {
        return http_build_query($data, '', $this->separator, $this->encodingType);
    }
}
