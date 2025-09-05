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

use Tuleap\Search\IndexedItemsToRemove;
use Tuleap\Search\ItemToIndex;

final class NullIndexHandler implements SearchIndexedItem, InsertItemsIntoIndex, InsertPlaintextItemsIntoIndex, DeleteIndexedItems
{
    #[\Override]
    public function deleteIndexedItems(IndexedItemsToRemove $items_to_remove): void
    {
    }

    #[\Override]
    public function deleteIndexedItemsPerProjectID(int $project_id): void
    {
    }

    #[\Override]
    public function indexItems(ItemToIndex|PlaintextItemToIndex ...$items): void
    {
    }

    #[\Override]
    public function searchItems(string $keywords, int $limit, int $offset): SearchResultPage
    {
        return SearchResultPage::noHits();
    }
}
