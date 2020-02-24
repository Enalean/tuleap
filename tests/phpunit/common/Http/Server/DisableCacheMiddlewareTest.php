<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Http\Server;

use PHPUnit\Framework\TestCase;
use Tuleap\Http\HTTPFactoryBuilder;

final class DisableCacheMiddlewareTest extends TestCase
{
    public function testResponseExplicitAskToNotBePutInACache(): void
    {
        $middleware = new DisableCacheMiddleware();

        $response = $middleware->process(
            new NullServerRequest(),
            new AlwaysSuccessfulRequestHandler(HTTPFactoryBuilder::responseFactory())
        );

        $this->assertEquals('no-store', $response->getHeaderLine('Cache-Control'));
        $this->assertEquals('no-cache', $response->getHeaderLine('Pragma'));
    }
}
