<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Authentication\Bearer;

final class BearerTest extends TestCase
{
    public function testAddsAuthorizationHeader(): void
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('GET', 'https://e/')->withHeader('X-Test', '1');

        $auth = new Bearer('token-123');
        $out = $auth->withCredentials($request);

        $this->assertSame('Bearer token-123', $out->getHeaderLine('Authorization'));
        $this->assertSame('1', $out->getHeaderLine('X-Test'));
    }
}
