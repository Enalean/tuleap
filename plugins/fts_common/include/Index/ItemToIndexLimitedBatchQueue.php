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

namespace Tuleap\FullTextSearchCommon\Index;

use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexBatchQueue;
use Tuleap\Search\ItemToIndexQueue;

final class ItemToIndexLimitedBatchQueue implements ItemToIndexBatchQueue, ItemToIndexQueue
{
    /**
     * @var ItemToIndex[]
     */
    private array $items_to_index = [];

    /**
     * @psalm-param positive-int $max_items_per_batch
     */
    public function __construct(private InsertItemsIntoIndex $index_inserter, private int $max_items_per_batch)
    {
    }

    #[\Override]
    public function startBatchingItemsIntoQueue(callable $callback): void
    {
        $callback($this);
        $this->processBatch();
    }

    #[\Override]
    public function addItemToQueue(ItemToIndex $item_to_index): void
    {
        $this->items_to_index[] = $item_to_index;

        if (count($this->items_to_index) >= $this->max_items_per_batch) {
            $this->processBatch();
        }
    }

    private function processBatch(): void
    {
        $this->index_inserter->indexItems(...$this->items_to_index);
        $this->items_to_index = [];
    }
}
