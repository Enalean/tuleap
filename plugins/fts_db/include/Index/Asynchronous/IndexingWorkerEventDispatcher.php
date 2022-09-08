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

use Tuleap\FullTextSearchDB\Index\DeleteIndexedItems;
use Tuleap\FullTextSearchDB\Index\InsertItemIntoIndex;
use Tuleap\Queue\WorkerEvent;

final class IndexingWorkerEventDispatcher
{
    public function __construct(
        private InsertItemIntoIndex $item_into_index_inserter,
        private DeleteIndexedItems $indexed_items_remover,
    ) {
    }

    public function process(WorkerEvent $worker_event): void
    {
        $item_to_index = IndexItemTask::parseWorkerEventIntoItemToIndexWhenPossible($worker_event);
        if ($item_to_index !== null) {
            $this->item_into_index_inserter->indexItem($item_to_index);
        }

        $items_to_remove = RemoveItemsFromIndexTask::parseWorkerEventIntoItemsToRemoveWhenPossible($worker_event);
        if ($items_to_remove !== null) {
            $this->indexed_items_remover->deleteIndexedItems($items_to_remove);
        }
    }
}
