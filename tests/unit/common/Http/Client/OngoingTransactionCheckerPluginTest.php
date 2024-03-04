<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Http\Client;

use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\DB\CheckThereIsAnOngoingTransactionStub;

final class OngoingTransactionCheckerPluginTest extends TestCase
{
    public function testItBlocksTheRequestChainIfThereIsAnOngoingTransaction(): void
    {
        $plugin = new OngoingTransactionCheckerPlugin(
            CheckThereIsAnOngoingTransactionStub::inTransaction(),
        );

        $this->expectException(\RuntimeException::class);

        $next_has_been_called  = false;
        $first_has_been_called = false;

        $plugin->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            static function () use (&$next_has_been_called) {
                $next_has_been_called = true;
            },
            static function () use (&$first_has_been_called) {
                $first_has_been_called = true;
            },
        );

        self::assertFalse($next_has_been_called);
        self::assertFalse($first_has_been_called);
    }

    public function testItDoesNotBlockTheRequestChainIfThereIsNoOngoingTransaction(): void
    {
        $plugin = new OngoingTransactionCheckerPlugin(
            CheckThereIsAnOngoingTransactionStub::notInTransaction(),
        );

        $promise = new \Http\Promise\FulfilledPromise(HTTPFactoryBuilder::responseFactory()->createResponse(200));

        $next_has_been_called  = false;
        $first_has_been_called = false;

        $result = $plugin->handleRequest(
            HTTPFactoryBuilder::requestFactory()->createRequest('GET', '/'),
            static function () use ($promise, &$next_has_been_called) {
                $next_has_been_called = true;

                return $promise;
            },
            static function () use (&$first_has_been_called) {
                $first_has_been_called = true;
            },
        );

        self::assertTrue($next_has_been_called);
        self::assertFalse($first_has_been_called);
        self::assertSame($promise, $result);
    }
}
