<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Parsers\JsonParser;

final class JsonParserTest extends TestCase
{
    public function testParsesJsonObjectToArray(): void
    {
        $parser = new JsonParser();
        $result = $parser->parse('{"name":"Alice","age":30}');
        $this->assertSame(['name' => 'Alice', 'age' => 30], $result);
    }

    public function testParsesNestedStructures(): void
    {
        $parser = new JsonParser();
        $result = $parser->parse('{"user":{"id":1,"tags":["a","b"]}}');
        $this->assertSame(['user' => ['id' => 1, 'tags' => ['a', 'b']]], $result);
    }

    public function testParsesJsonArray(): void
    {
        $parser = new JsonParser();
        $this->assertSame([1, 2, 3], $parser->parse('[1,2,3]'));
    }
}
