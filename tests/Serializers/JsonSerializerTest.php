<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Serializers\JsonSerializer;

final class JsonSerializerTest extends TestCase
{
    public function testSerializesArrayToJson(): void
    {
        $serializer = new JsonSerializer();
        $this->assertSame('{"a":1,"b":2}', $serializer->serialize(['a' => 1, 'b' => 2]));
    }

    public function testDoesNotEscapeUnicodeByDefault(): void
    {
        $serializer = new JsonSerializer();
        $this->assertStringContainsString('Алиса', $serializer->serialize(['name' => 'Алиса']));
    }

    public function testAcceptsCustomFlags(): void
    {
        $serializer = new JsonSerializer(JSON_PRETTY_PRINT);
        $this->assertStringContainsString("\n", $serializer->serialize(['a' => 1]));
    }

    public function testSerializesEmptyArray(): void
    {
        $this->assertSame('[]', (new JsonSerializer())->serialize([]));
    }
}
