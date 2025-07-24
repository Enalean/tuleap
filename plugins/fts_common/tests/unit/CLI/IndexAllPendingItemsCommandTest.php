<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchCommon\CLI;

use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Search\ItemToIndexBatchQueue;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IndexAllPendingItemsCommandTest extends TestCase
{
    public function testCommandCanAskForIndexationOfAllPendingItems(): void
    {
        $index_queue      = new class implements ItemToIndexBatchQueue {
            #[\Override]
            public function startBatchingItemsIntoQueue(callable $callback): void
            {
            }
        };
        $event_dispatcher = EventDispatcherStub::withIdentityCallback();
        $command          = new IndexAllPendingItemsCommand($event_dispatcher, $index_queue);

        $command_tester = new CommandTester($command);

        $command_tester->execute([]);

        $command_tester->assertCommandIsSuccessful();
        self::assertEquals(1, $event_dispatcher->getCallCount());
    }
}
