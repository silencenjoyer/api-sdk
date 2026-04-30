<?php

declare(strict_types=1);

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\RequestHandler;

final class RequestHandlerTest extends TestCase
{
    public function testDelegatesToClient(): void
    {
        $psr17 = new Psr17Factory();
        $request = $psr17->createRequest('GET', 'https://example.com');
        $response = $psr17->createResponse(204);

        $client = new class($response) implements ClientInterface {
            private ResponseInterface $response;
            public ?RequestInterface $last = null;
            public function __construct(ResponseInterface $response) { $this->response = $response; }
            public function sendRequest(RequestInterface $request): ResponseInterface { $this->last = $request; return $this->response; }
        };

        $handler = new RequestHandler($client);
        $result = $handler->handle($request);

        $this->assertSame($response, $result);
        $this->assertSame($request, $client->last);
    }
}
