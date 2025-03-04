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

namespace Tuleap\FullTextSearchCommon\Index\Asynchronous;

use Psr\Log\NullLogger;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RemoveItemsFromIndexTaskTest extends TestCase
{
    public function testInstantiatesTaskToQueueFromItemsToRemove(): void
    {
        $item_to_index = new IndexedItemsToRemove('type', ['A' => 'A']);

        $task = RemoveItemsFromIndexTask::fromItemsToRemove($item_to_index);

        self::assertEquals(
            ['type' => 'type', 'metadata' => ['A' => 'A']],
            $task->getPayload()
        );
    }

    public function testBuildsItemToIndexFromWorkerEvent(): void
    {
        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent(
                'tuleap.fts.remove-items-index',
                ['type' => 'type', 'metadata' => ['A' => 'A']]
            )
        );

        self::assertEquals(
            new IndexedItemsToRemove('type', ['A' => 'A']),
            RemoveItemsFromIndexTask::parseWorkerEventIntoItemsToRemoveWhenPossible($worker_event)
        );
    }

    public function testSkipsWorkerEventWithNotExpectedType(): void
    {
        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent(
                'something.else',
                ['type' => 'type', 'metadata' => ['A' => 'A']]
            )
        );

        self::assertNull(
            RemoveItemsFromIndexTask::parseWorkerEventIntoItemsToRemoveWhenPossible($worker_event)
        );
    }

    public function testSkipsWorkerEventWithUnexpectedPayload(): void
    {
        $worker_event = new WorkerEvent(
            new NullLogger(),
            new WorkerEventContent(
                'tuleap.fts.remove-items-index',
                []
            )
        );

        self::assertNull(
            RemoveItemsFromIndexTask::parseWorkerEventIntoItemsToRemoveWhenPossible($worker_event)
        );
    }
}
