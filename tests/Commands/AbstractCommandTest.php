<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Silencenjoyer\ApiSdk\Commands\AbstractCommand;
use Silencenjoyer\ApiSdk\Commands\CommandHandlerInterface;
use Silencenjoyer\ApiSdk\Commands\DispatchableCommandInterface;
use Silencenjoyer\ApiSdk\Response\ParsedResponseInterface;

final class AbstractCommandTest extends TestCase
{
    private function makeCommand(): AbstractCommand
    {
        return new class extends AbstractCommand {
            protected function isPublic(): bool { return true; }
            protected function getPath(): string { return '/test'; }
            protected function getMethod(): string { return 'GET'; }
        };
    }

    public function testWithHandlerIsImmutable(): void
    {
        $command = $this->makeCommand();
        $withHandler = $command->withHandler($this->createMock(CommandHandlerInterface::class));

        $this->assertNotSame($command, $withHandler);
        $this->assertInstanceOf(DispatchableCommandInterface::class, $withHandler);
    }

    public function testSendDelegatesToHandler(): void
    {
        $parsedResponse = $this->createMock(ParsedResponseInterface::class);

        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())->method('execute')->willReturn($parsedResponse);

        $this->assertSame($parsedResponse, $this->makeCommand()->withHandler($handler)->send());
    }

    public function testGetDtoReflectsAbstractMethodValues(): void
    {
        $dto = $this->makeCommand()->getDto();

        $this->assertTrue($dto->public);
        $this->assertSame('GET', $dto->method);
        $this->assertSame('/test', $dto->path);
    }

    public function testDefaultDtoHasEmptyCollections(): void
    {
        $dto = $this->makeCommand()->getDto();

        $this->assertSame([], $dto->bodyParams);
        $this->assertSame([], $dto->queryParams);
        $this->assertSame([], $dto->headers);
    }

    public function testDefaultDtoHasNullContentTypes(): void
    {
        $dto = $this->makeCommand()->getDto();

        $this->assertNull($dto->requestContentType);
        $this->assertNull($dto->answerContentType);
    }
}
