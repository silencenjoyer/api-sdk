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

namespace Silencenjoyer\ApiSdk;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Silencenjoyer\ApiSdk\Authentication\AuthInterface;
use Silencenjoyer\ApiSdk\Builder\ApiBuilder;
use Silencenjoyer\ApiSdk\Builder\ApiBuilderInterface;
use Silencenjoyer\ApiSdk\Commands\CommandFactoryInterface;
use Silencenjoyer\ApiSdk\Commands\CommandInterface;
use Silencenjoyer\ApiSdk\Commands\DispatchableCommandInterface;
use Silencenjoyer\ApiSdk\Exceptions\UnsupportedCommandException;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\HasCommandMiddlewareMapInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareStack;
use Silencenjoyer\ApiSdk\Request\RequestBuilderInterface;
use Silencenjoyer\ApiSdk\Response\ParsedResponseInterface;
use Silencenjoyer\ApiSdk\Response\ResponseParserInterface;

/**
 * Base class for API client implementations.
 *
 * ## Layer 2 — integration developer
 *
 * Extend this class to build a typed API client:
 *
 * 1. Implement {@see getUrl()} — return the base URL of the API.
 * 2. Implement {@see supports()} — return true for commands this API accepts.
 * 3. Optionally override {@see getBuilder()} to apply defaults (format, middleware)
 *    that are always needed for this API.
 *
 * ## Layer 1 — end user
 *
 * Instantiate via the static shortcuts:
 * - {@see build()} — zero-config, PSR implementations are auto-discovered.
 * - {@see getBuilder()} — advanced configuration (middleware, parsers, formats).
 */
abstract class AbstractApi implements ApiInterface, CommandFactoryInterface, HasCommandMiddlewareMapInterface
{
    private HandlerInterface $finalHandler;
    private MiddlewareStack $middlewareStack;
    private array $commandMiddlewareMap = [];
    /** Authentication strategy used to sign private requests. */
    private ?AuthInterface $auth = null;
    private RequestBuilderInterface $requestBuilder;
    private ResponseParserInterface $responseParser;

    public function __construct(
        RequestBuilderInterface $requestBuilder,
        ResponseParserInterface $responseParser,
        HandlerInterface $finalHandler,
        MiddlewareStack $middlewareStack
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->responseParser = $responseParser;
        $this->finalHandler = $finalHandler;
        $this->middlewareStack = $middlewareStack;
    }

    /**
     * The base URL of the API (e.g. 'https://api.example.com/v1').
     *
     * No trailing slash. Command paths returned by {@see \Silencenjoyer\ApiSdk\Commands\AbstractCommand::getPath()}
     * are appended directly to this value.
     */
    abstract protected function getUrl(): string;

    /**
     * Whether this API instance can handle the given command.
     *
     * The recommended implementation is an instanceof check against a marker interface
     * shared by all commands belonging to this API:
     *
     * ```php
     * protected function supports(CommandInterface $command): bool
     * {
     *     return $command instanceof NbuCommandInterface;
     * }
     * ```
     */
    abstract protected function supports(CommandInterface $command): bool;

    final public function execute(CommandInterface $command): ParsedResponseInterface
    {
        if (!$this->supports($command)) {
            $this->throwUnsupportedCommandException($command);
        }

        $dto = $command->getDto();
        $request = $this->requestBuilder->build($dto, $this->getUrl(), $this->auth);

        $response = $this->middlewareStack
            ->withPrepended(...$this->getMiddlewaresForCommand($command))
            ->handle($request, $this->finalHandler)
        ;

        return $this->responseParser->parse($response, $dto->answerContentType);
    }

    /**
     * @template T of DispatchableCommandInterface
     *
     * @param class-string<T> $commandClass
     * @param array $params
     *
     * @return T
     */
    final public function createCommand(string $commandClass, array $params = []): DispatchableCommandInterface
    {
        $command = new $commandClass(...$params);

        if (!$this->supports($command)) {
            $this->throwUnsupportedCommandException($command);
        }

        return $command->withHandler($this);
    }

    /**
     * Attach an authentication strategy used to sign non-public commands.
     *
     * Immutable — returns a new instance; the original is unchanged.
     * The strategy is invoked automatically for every command whose
     * {@see \Silencenjoyer\ApiSdk\Commands\AbstractCommand::isPublic()} returns false.
     */
    final public function withAuthentication(AuthInterface $auth): self
    {
        $clone = clone $this;
        $clone->auth = $auth;
        return $clone;
    }

    /**
     * @param array<class-string<CommandInterface>, list<MiddlewareInterface>> $commandMiddlewareMap
     * @return static
     */
    final public function withCommandMiddlewareMap(array $commandMiddlewareMap): self
    {
        $clone = clone $this;
        $clone->commandMiddlewareMap = $commandMiddlewareMap;
        return $clone;
    }

    /**
     * @return static
     */
    final public function withAppendedCommandMiddleware(string $commandClass, MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->commandMiddlewareMap[$commandClass][] = $middleware;
        return $clone;
    }

    /**
     * Create a ready-to-use instance with auto-discovered PSR-18/PSR-17 implementations.
     *
     * Equivalent to calling {@see static::getBuilder()}->build().
     * Pass explicit PSR implementations to skip auto-discovery (useful in tests).
     *
     * For middleware or parser customization use {@see static::getBuilder()} instead.
     *
     * @return static
     */
    final public static function build(
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ): self {
        /** @var static $instance */
        $instance = static::getBuilder($client, $requestFactory, $streamFactory)->build();
        return $instance;
    }

    /**
     * Return a pre-configured builder for this API class.
     *
     * Override in a subclass to apply defaults that are always needed for this API
     * (e.g. a required format or a mandatory middleware). The signature must keep
     * all three parameters optional so that {@see build()} can call it without arguments:
     *
     * ```php
     * public static function getBuilder(
     *     ?ClientInterface $client = null,
     *     ?RequestFactoryInterface $requestFactory = null,
     *     ?StreamFactoryInterface $streamFactory = null
     * ): ApiBuilderInterface {
     *     return parent::getBuilder($client, $requestFactory, $streamFactory)
     *         ->withFormats(Format::JSON)
     *         ->withMiddlewares([new NbuRetryMiddleware()]);
     * }
     * ```
     *
     * If you need extra constructor parameters, add a dedicated named factory method
     * (e.g. forUrl()) that calls {@see ApiBuilderInterface::withFactory()} instead of
     * changing this signature.
     */
    public static function getBuilder(
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ): ApiBuilderInterface {
        return ApiBuilder::create(static::class, $client, $requestFactory, $streamFactory);
    }

    private function getMiddlewaresForCommand(CommandInterface $command): array
    {
        return $this->commandMiddlewareMap[get_class($command)] ?? [];
    }

    /**
     * @param CommandInterface $command
     * @return never
     */
    private function throwUnsupportedCommandException(CommandInterface $command): void
    {
        throw new UnsupportedCommandException(
            sprintf('Unsupported Command type: %s for %s', get_class($command), static::class)
        );
    }
}
