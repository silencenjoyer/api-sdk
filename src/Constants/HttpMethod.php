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

namespace Silencenjoyer\ApiSdk\Constants;

final class HttpMethod
{
    /**
     * {@see https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/GET}
     */
    public const GET = 'GET';
    /**
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/POST}
     */
    public const POST = 'POST';
    /**
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/PUT}
     */
    public const PUT = 'PUT';
    /**
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/DELETE}
     */
    public const DELETE = 'DELETE';
    /**
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/HEAD}
     */
    public const HEAD = 'HEAD';
    /**
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods/OPTIONS}
     */
    public const OPTIONS = 'OPTIONS';
}
