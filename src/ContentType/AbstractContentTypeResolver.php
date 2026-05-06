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

/**
 * @template THandler of object
 */
abstract class AbstractContentTypeResolver
{
    protected ContentTypeReaderInterface $contentTypeReader;

    /**
     * @var array<string, THandler>
     */
    protected array $contentTypeMapper = [];

    /**
     * @return never
     */
    abstract protected function throwNotResolved(string $reason): void;

    /**
     * @param array<string, THandler> $contentTypeMapper
     */
    public function __construct(ContentTypeReaderInterface $contentTypeReader, array $contentTypeMapper)
    {
        $this->contentTypeReader = $contentTypeReader;
        $this->contentTypeMapper = $contentTypeMapper;
    }

    /**
     * @param MessageInterface $message
     * @param ContentTypeDto|null $hint
     * @return THandler
     */
    protected function resolveFromMapper(MessageInterface $message, ?ContentTypeDto $hint): object
    {
        $dto = $hint ?? $this->contentTypeReader->readValue($message);

        if ($dto === null) {
            $this->throwNotResolved('content type is not defined');
        }

        if (!isset($this->contentTypeMapper[$dto->contentType])) {
            $this->throwNotResolved('content type: ' . $dto->contentType);
        }

        return $this->contentTypeMapper[$dto->contentType];
    }
}
