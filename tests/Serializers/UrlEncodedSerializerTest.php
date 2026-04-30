<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Serializers\UrlEncodedSerializer;

final class UrlEncodedSerializerTest extends TestCase
{
    public function testSerializesArrayToQueryString(): void
    {
        $serializer = new UrlEncodedSerializer();
        $this->assertSame('foo=bar&baz=1', $serializer->serialize(['foo' => 'bar', 'baz' => '1']));
    }

    public function testEncodesSpacesAsPlus(): void
    {
        $serializer = new UrlEncodedSerializer();
        $this->assertStringContainsString('hello+world', $serializer->serialize(['q' => 'hello world']));
    }

    public function testCustomSeparator(): void
    {
        $serializer = new UrlEncodedSerializer(';');
        $result = $serializer->serialize(['a' => '1', 'b' => '2']);
        $this->assertStringContainsString(';', $result);
        $this->assertStringNotContainsString('&', $result);
    }

    public function testSerializesEmptyArray(): void
    {
        $this->assertSame('', (new UrlEncodedSerializer())->serialize([]));
    }
}
