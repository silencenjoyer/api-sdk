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

use Psr\Http\Message\MessageInterface;

class CompositeContentTypeReader implements ContentTypeReaderInterface
{
    /**
     * @var list<ContentTypeReaderInterface>
     */
    private array $readers;

    /**
     * @param list<ContentTypeReaderInterface> $readers
     */
    public function __construct(array $readers)
    {
        $this->readers = $readers;
    }

    public function readValue(MessageInterface $message): ?ContentTypeDto
    {
        foreach ($this->readers as $reader) {
            $contentType = $reader->readValue($message);

            if ($contentType !== null) {
                return $contentType;
            }
        }

        return null;
    }
}
