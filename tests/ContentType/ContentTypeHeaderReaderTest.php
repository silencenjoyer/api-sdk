<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Constants\Format;
use Silencenjoyer\ApiSdk\ContentType\ContentTypeHeaderReader;

final class ContentTypeHeaderReaderTest extends TestCase
{
    public function testParsesHeaderWithParameters(): void
    {
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse()->withHeader('Content-Type', 'application/json; charset=UTF-8');
        $reader = new ContentTypeHeaderReader([Format::JSON, Format::URLENCODED]);
        $dto = $reader->readValue($response);
        $this->assertNotNull($dto);
        $this->assertSame('json', $dto->contentType);
    }

    public function testCaseInsensitiveSubstringsAndUnknown(): void
    {
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse()->withHeader('Content-Type', 'APPLICATION/X-WWW-FORM-URLENCODED');
        $reader = new ContentTypeHeaderReader([Format::JSON, Format::URLENCODED]);
        $dto = $reader->readValue($response);
        $this->assertNotNull($dto);
        $this->assertSame(Format::URLENCODED, $dto->contentType);

        $response2 = $psr17->createResponse();
        $this->assertNull($reader->readValue($response2));

        $response3 = $psr17->createResponse()->withHeader('Content-Type', 'application/xml');
        $this->assertNull($reader->readValue($response3));
    }
}
