<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Http;

use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\ResponseFactory;
use Http\Factory\Guzzle\StreamFactory;
use Http\Factory\Guzzle\UriFactory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

final class HTTPFactoryBuilder
{
    public static function requestFactory(): RequestFactoryInterface
    {
        return new RequestFactory();
    }

    public static function responseFactory(): ResponseFactoryInterface
    {
        return new ResponseFactory();
    }

    public static function streamFactory(): StreamFactoryInterface
    {
        return new StreamFactory();
    }

    public static function URIFactory(): UriFactoryInterface
    {
        return new UriFactory();
    }
}
