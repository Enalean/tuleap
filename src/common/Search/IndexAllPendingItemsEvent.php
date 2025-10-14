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

namespace Tuleap\Search;

use Tuleap\Event\Dispatchable;

final class IndexAllPendingItemsEvent implements Dispatchable
{
    public const string NAME = 'indexAllPendingItems';

    /**
     * @psalm-param callable(string):ProgressQueueIndexItemCategory $progress_queue_item_factory
     */
    public function __construct(private ItemToIndexBatchQueue $item_to_index_batch_queue, private $progress_queue_item_factory)
    {
    }

    public function getProcessQueueForItemCategory(string $item_category): ProgressQueueIndexItemCategory
    {
        return ($this->progress_queue_item_factory)($item_category);
    }

    public function getItemToIndexBatchQueue(): ItemToIndexBatchQueue
    {
        return $this->item_to_index_batch_queue;
    }
}
