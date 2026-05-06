<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Response\ParsedResponse;
use Silencenjoyer\ApiSdk\Response\ParsedResponseInterface;

final class ParsedResponseTest extends TestCase
{
    public function testAccessors(): void
    {
        $psr17 = new Psr17Factory();
        $response = $psr17->createResponse(200);
        $parsed = new ParsedResponse(['a' => 1, 'b' => ['c' => 2]], $response);

        $this->assertInstanceOf(ParsedResponseInterface::class, $parsed);
        $this->assertSame(['a' => 1, 'b' => ['c' => 2]], $parsed->asArray());
        $obj = $parsed->asObject();
        $this->assertIsObject($obj);
        $this->assertSame(1, $obj->a);
        $this->assertIsArray($parsed->asArray()['b']);
        $this->assertSame($response, $parsed->getHttpResponse());
    }
}
