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

namespace Silencenjoyer\ApiSdk\Authentication;

interface SupportsAuthInterface
{
    /**
     * Sets authentication strategy for private endpoints.
     * Use it to provide token/key auth, etc.
     *
     * @param AuthInterface $auth
     * @return self
     */
    public function withAuthentication(AuthInterface $auth): self;
}
