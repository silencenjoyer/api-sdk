# 🛠️ API SDK

A lightweight PHP framework for building typed HTTP API integrations.

[![Tests](https://github.com/silencenjoyer/api-sdk/actions/workflows/tests.yml/badge.svg)](https://github.com/silencenjoyer/api-sdk/actions/workflows/tests.yml)
[![codecov](https://codecov.io/gh/silencenjoyer/api-sdk/branch/main/graph/badge.svg)](https://codecov.io/gh/silencenjoyer/api-sdk)
[![Static Analyze](https://github.com/silencenjoyer/api-sdk/actions/workflows/phpstan.yml/badge.svg)](https://github.com/silencenjoyer/api-sdk/actions/workflows/phpstan.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/silencenjoyer/api-sdk.svg)](https://packagist.org/packages/silencenjoyer/api-sdk)
[![PHP Version Require](https://img.shields.io/packagist/php-v/silencenjoyer/api-sdk.svg)](https://packagist.org/packages/silencenjoyer/api-sdk)
[![License](https://img.shields.io/github/license/silencenjoyer/api-sdk)](LICENSE)

## Requirements

- PHP 7.4 or 8.x
- PSR-18 HTTP client
- PSR-17 HTTP factories (request factory + stream factory)

## Installation

```bash
composer require silencenjoyer/api-sdk
```

The package includes `php-http/discovery` and will auto-discover PSR-18/PSR-17 implementations. Install any compatible package alongside:

```bash
# PSR-7 + PSR-17
composer require nyholm/psr7
# PSR-18 client, e.g. one of:
composer require symfony/http-client
composer require guzzlehttp/guzzle
```

## Overview

The SDK is organized around two layers:

- **Layer 1 — end users**: consume a ready-made API client (`NbuApi`, `StripeApi`, etc.)
- **Layer 2 — integration developers**: build those clients by extending `AbstractApi`

---

## Layer 1: Using an API client

### Zero-config instantiation

If `php-http/discovery` is installed alongside compatible PSR-18/PSR-17 packages, the client discovers them automatically:

```php
$api = NbuApi::build();
```

Explicit dependencies:

```php
$api = NbuApi::build($httpClient, $requestFactory, $streamFactory);
```

### Advanced builder

For middleware or parser customization, obtain the builder first:

```php
use Silencenjoyer\ApiSdk\Middlewares\LoggerMiddleware;

$api = NbuApi::getBuilder()
    ->withMiddlewares([new LoggerMiddleware($logger)])
    ->build();
```

### Authentication

Attach an auth strategy to a built instance. The call is immutable and returns a new instance:

```php
use Silencenjoyer\ApiSdk\Authentication\Bearer;

$api = NbuApi::build()->withAuthentication(new Bearer($token));
```

Implement `AuthInterface` to create custom strategies (API key header, HMAC signature, etc.).

### Executing commands

Two styles are available:

```php
// Immediate execution
$response = $api->execute(new GetExchangeRatesCommand(date: '2026-01-01'));

// Dispatchable — bind command to the API, send later
$command = $api->createCommand(GetExchangeRatesCommand::class, ['date' => '2026-01-01']);
// ... pass $command around ...
$response = $command->send();
```

### Working with the response

```php
$response->asArray();           // array
$response->asObject();          // stdClass
$response->getHttpResponse(); // PSR-7 ResponseInterface
```

---

## Layer 2: Building an API client

### Minimal implementation

```php
use Silencenjoyer\ApiSdk\AbstractApi;
use Silencenjoyer\ApiSdk\Commands\CommandInterface;

class NbuApi extends AbstractApi
{
    protected function getUrl(): string
    {
        return 'https://bank.gov.ua';
    }

    protected function supports(CommandInterface $command): bool
    {
        return $command instanceof NbuCommandInterface;
    }
}
```

### Providing default builder configuration

Override `getBuilder()` to apply defaults that are always needed for this API. The signature must match the parent:

```php
class NbuApi extends AbstractApi
{
    public static function getBuilder(
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ): ApiBuilderInterface {
        return parent::getBuilder($client, $requestFactory, $streamFactory)
            ->withFormats(Format::JSON)
            ->withMiddlewares([new NbuRetryMiddleware()]);
    }
}
```

`AbstractApi::build()` calls `static::getBuilder()`, so defaults are applied automatically for both `NbuApi::build()` and `NbuApi::getBuilder()`.

### Custom constructor parameters

If your API requires extra constructor arguments (e.g., an environment-specific base URL), add a named factory method that uses `withFactory()`:

```php
class NbuApi extends AbstractApi
{
    private string $baseUrl;

    public function __construct(
        string $baseUrl,
        RequestBuilderInterface $requestBuilder,
        ResponseParserInterface $responseParser,
        HandlerInterface $handler,
        MiddlewareStack $middlewareStack
    ) {
        parent::__construct($requestBuilder, $responseParser, $handler, $middlewareStack);
        $this->baseUrl = $baseUrl;
    }

    protected function getUrl(): string
    {
        return $this->baseUrl;
    }

    public static function forUrl(string $baseUrl): ApiBuilderInterface
    {
        return static::getBuilder()
            ->withFactory(fn($rb, $rp, $h, $ms) => new self($baseUrl, $rb, $rp, $h, $ms));
    }
}

// Usage:
$api = NbuApi::forUrl($_ENV['NBU_API_URL'])->build();
$api = NbuApi::forUrl($_ENV['NBU_API_URL'])->withMiddlewares([$logger])->build();
```

### Implementing commands

Extend `AbstractCommand` and declare the HTTP semantics:

```php
use Silencenjoyer\ApiSdk\Commands\AbstractCommand;
use Silencenjoyer\ApiSdk\Constants\HttpMethod;

class GetExchangeRatesCommand extends AbstractCommand implements NbuCommandInterface
{
    public function __construct(private string $date) {}

    protected function isPublic(): bool
    {
        return true;
    }

    protected function getMethod(): string
    {
        return HttpMethod::GET;
    }

    protected function getPath(): string
    {
        return '/NBUStatService/v1/statdirectory/exchange';
    }

    protected function getQueryParams(): array
    {
        return ['json' => '', 'date' => $this->date];
    }
}
```

Override `getBodyParams()`, `getHeaders()`, `getRequestContentType()`, or `getAnswerContentType()` as needed.

For private commands, `isPublic()` must return `false`. The authentication strategy attached via `withAuthentication()` will sign the request automatically.

### Per-command middleware

Attach middleware to a specific command class only:

```php
$api = NbuApi::getBuilder()
    ->withCommandMiddleware(GetExchangeRatesCommand::class, new RateLimiterMiddleware())
    ->build();
```

### Custom middleware

Implement `MiddlewareInterface`:

```php
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Silencenjoyer\ApiSdk\Handlers\HandlerInterface;
use Silencenjoyer\ApiSdk\Middlewares\MiddlewareInterface;

class RetryMiddleware implements MiddlewareInterface
{
    public function handle(RequestInterface $request, HandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response->getStatusCode() === 503) {
            $response = $handler->handle($request);
        }

        return $response;
    }
}
```

### Custom parsers and serializers

Register additional format support on the builder:

```php
use Silencenjoyer\ApiSdk\Constants\Format;

NbuApi::getBuilder()
    ->withAddedParser(Format::JSON, new MyJsonParser())
    ->withAddedSerializer('xml', new XmlSerializer())
    ->withFormats(Format::JSON, 'xml')
    ->build();
```

### Serializer resolution

When a command has body params, the SDK selects a serializer using this priority chain:

1. `getRequestContentType()` — explicit override on the command.
2. The `Content-Type` header already present on the request — set it via `getHeaders()` to let the SDK pick the serializer automatically.
3. If neither provides a recognized content type, `UnableToResolveSerializerException` is thrown.

```php
// Option 1 — override on the command
protected function getRequestContentType(): ?ContentTypeDto
{
    return new ContentTypeDto(Format::JSON);
}

// Option 2 — set the header, the SDK reads it
protected function getHeaders(): array
{
    return ['Content-Type' => 'application/json'];
}
```

The recognized content types are controlled by `withFormats()` on the builder.

### Parser resolution

After a response is received, the SDK selects a parser using this priority chain:

1. `getAnswerContentType()` — explicit override on the command; bypasses all automatic detection.
2. The `Content-Type` header on the response — matched against the content types registered via `withFormats()`.
3. Assumes — when the header is absent or unrecognized, the body is inspected by registered assume strategies. `JsonAssume` is active by default.
4. If none of the above resolves, `UnableToResolveParserException` is thrown.

```php
// Force a specific parser regardless of the response header
protected function getAnswerContentType(): ?ContentTypeDto
{
    return new ContentTypeDto(Format::JSON);
}
```

### Content-type inference

When a response carries no `Content-Type` header, the SDK uses registered "assumes" to infer the format from the body. JSON is assumed by default. Register additional strategies:

```php
NbuApi::getBuilder()
    ->withParserContentTypeAssume(new XmlAssume())
    ->build();
```

---

## Built-in components

| Component | Description |
|---|---|
| `LoggerMiddleware` | Logs request and response bodies via PSR-3 |
| `RepeatableMiddleware` | Retries requests on 5xx by default; configurable condition and retry count |
| `RateLimiterMiddleware` | Throttles outgoing requests via `silencenjoyer/rate-limiter`; blocks until the rate window allows |
| `Bearer` | Adds `Authorization: Bearer <token>` header |
| `JsonParser` | Parses JSON responses |
| `JsonSerializer` | Serializes request bodies as JSON |
| `UrlEncodedSerializer` | Serializes request bodies as `application/x-www-form-urlencoded` |

## License

MIT
