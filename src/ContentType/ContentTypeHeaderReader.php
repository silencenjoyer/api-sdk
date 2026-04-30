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
use Silencenjoyer\ApiSdk\Constants\Format;

class ContentTypeHeaderReader implements ContentTypeReaderInterface
{
    protected const CONTENT_TYPE_HEADER = 'Content-Type';
    /**
     * @var list<string>
     */
    protected array $contentTypesSubstrings;

    public function __construct(array $contentTypesSubstrings)
    {
        $this->contentTypesSubstrings = $contentTypesSubstrings;
    }

    /**
     * @param MessageInterface $message
     * @return ContentTypeDto|null
     */
    public function readValue(MessageInterface $message): ?ContentTypeDto
    {
        $header = $message->getHeaderLine(self::CONTENT_TYPE_HEADER);

        if ($header === '') {
            return null;
        }

        $type = explode(';', $header, 2);
        $header = current($type);

        $contentType = null;

        foreach ($this->contentTypesSubstrings as $contentTypeSubstring) {
            if (stripos($header, $contentTypeSubstring) !== false) {
                $contentType = $contentTypeSubstring;
                break;
            }
        }

        if ($contentType === null) {
            return null;
        }

        return new ContentTypeDto($contentType);
    }
}
