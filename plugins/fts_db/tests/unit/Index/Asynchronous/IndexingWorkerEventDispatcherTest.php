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

namespace Tuleap\FullTextSearchDB\Index\Asynchronous;

use Psr\Log\NullLogger;
use Tuleap\FullTextSearchDB\Index\DeleteIndexedItems;
use Tuleap\FullTextSearchDB\Index\InsertItemsIntoIndex;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;
use Tuleap\Test\PHPUnit\TestCase;

final class IndexingWorkerEventDispatcherTest extends TestCase
{
    public function testDoesNothingWhenProcessingAnUnknownEvent(): void
    {
        $dispatcher = self::buildIndexingWorkerEventDispatcher($this->createStub(InsertItemsIntoIndex::class), $this->createStub(DeleteIndexedItems::class));

        $this->expectNotToPerformAssertions();
        $dispatcher->process(new WorkerEvent(new NullLogger(), ['event_name' => 'not.a.indexing.event', 'payload' => []]));
    }

    public function testCanProcessItemToIndexWorkerEvent(): void
    {
        $inserter = new class implements InsertItemsIntoIndex {
            public bool $has_been_called = false;
            public function indexItems(ItemToIndex ...$items): void
            {
                $this->has_been_called = true;
            }
        };

        $dispatcher = self::buildIndexingWorkerEventDispatcher($inserter, $this->createStub(DeleteIndexedItems::class));

        $task = IndexItemTask::fromItemToIndex(new ItemToIndex('type', 'content', ['A' => 'A']));

        $dispatcher->process(new WorkerEvent(new NullLogger(), ['event_name' => $task->getTopic(), 'payload' => $task->getPayload()]));

        self::assertTrue($inserter->has_been_called);
    }

    public function testCanProcessRemoveItemsFromIndexWorkerEvent(): void
    {
        $remover = new class implements DeleteIndexedItems {
            public bool $has_been_called = false;
            public function deleteIndexedItems(IndexedItemsToRemove $items_to_remove): void
            {
                $this->has_been_called = true;
            }
        };

        $dispatcher = self::buildIndexingWorkerEventDispatcher($this->createStub(InsertItemsIntoIndex::class), $remover);

        $task = RemoveItemsFromIndexTask::fromItemsToRemove(new IndexedItemsToRemove('type', ['A' => 'A']));

        $dispatcher->process(new WorkerEvent(new NullLogger(), ['event_name' => $task->getTopic(), 'payload' => $task->getPayload()]));

        self::assertTrue($remover->has_been_called);
    }

    private static function buildIndexingWorkerEventDispatcher(
        InsertItemsIntoIndex $item_into_index_inserter,
        DeleteIndexedItems $indexed_items_remover,
    ): IndexingWorkerEventDispatcher {
        return new IndexingWorkerEventDispatcher($item_into_index_inserter, $indexed_items_remover);
    }
}
