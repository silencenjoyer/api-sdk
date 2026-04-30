<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Silencenjoyer\ApiSdk\ContentType\CompositeContentTypeReader;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeDto;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeReaderInterface;

final class CompositeContentTypeReaderTest extends TestCase
{
    public function testReturnsFirstNonNullResult(): void
    {
        $dto = new ContentTypeDto('json');

        $reader1 = $this->createMock(ContentTypeReaderInterface::class);
        $reader1->method('readValue')->willReturn(null);

        $reader2 = $this->createMock(ContentTypeReaderInterface::class);
        $reader2->method('readValue')->willReturn($dto);

        $reader3 = $this->createMock(ContentTypeReaderInterface::class);
        $reader3->expects($this->never())->method('readValue');

        $composite = new CompositeContentTypeReader([$reader1, $reader2, $reader3]);
        $result = $composite->readValue($this->createMock(MessageInterface::class));

        $this->assertSame($dto, $result);
    }

    public function testReturnsNullWhenAllReadersReturnNull(): void
    {
        $reader1 = $this->createMock(ContentTypeReaderInterface::class);
        $reader1->method('readValue')->willReturn(null);

        $reader2 = $this->createMock(ContentTypeReaderInterface::class);
        $reader2->method('readValue')->willReturn(null);

        $result = (new CompositeContentTypeReader([$reader1, $reader2]))
            ->readValue($this->createMock(MessageInterface::class));

        $this->assertNull($result);
    }

    public function testReturnsNullForEmptyReaderList(): void
    {
        $result = (new CompositeContentTypeReader([]))
            ->readValue($this->createMock(MessageInterface::class));

        $this->assertNull($result);
    }
}
