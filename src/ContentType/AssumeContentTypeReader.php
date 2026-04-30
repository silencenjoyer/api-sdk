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
use Silencenjoyer\ApiSdk\ContentType\Assume\ContentTypeAssumeInterface;

class AssumeContentTypeReader implements ContentTypeReaderInterface
{
    /**
     * @var list<ContentTypeAssumeInterface>
     */
    protected array $assumeList;

    /**
     * @param list<ContentTypeAssumeInterface> $assumeList
     */
    public function __construct(array $assumeList)
    {
        $this->assumeList = $assumeList;
    }

    public function readValue(MessageInterface $message): ?ContentTypeDto
    {
        $body = $message->getBody()->getContents();
        $message->getBody()->rewind();

        foreach ($this->assumeList as $assume) {
            if ($assume->supports($body)) {
                return $assume->getContentType();
            }
        }

        return null;
    }
}
