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

namespace Silencenjoyer\ApiSdk\Request;

use Psr\Http\Message\RequestInterface;
use Silencenjoyer\ApiSdk\Authentication\AuthInterface;
use Silencenjoyer\ApiSdk\Commands\CommandMetaDto;

interface RequestBuilderInterface
{
    public function build(CommandMetaDto $dto, string $baseUrl, ?AuthInterface $auth): RequestInterface;
}
