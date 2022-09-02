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

final class IndexedItemFoundToSearchResult implements Dispatchable
{
    public const NAME = 'convertIndexedItemToSearchResult';

    /**
     * @var array<int,SearchResultEntry>
     * @psalm-readonly-allow-private-mutation
     */
    public array $search_results = [];

    /**
     * @param array<int,IndexedItemFound> $indexed_items
     */
    public function __construct(
        /** @psalm-readonly */
        public array $indexed_items,
        /** @psalm-readonly */
        public \PFUser $user,
    ) {
    }

    public function addSearchResult(int $priority, SearchResultEntry $entry): void
    {
        if (isset($this->search_results[$priority])) {
            throw new \LogicException(sprintf('A search result with the priority %d already exists, you cannot overwrite it', $priority));
        }
        if (! isset($this->indexed_items[$priority])) {
            throw new \LogicException(sprintf('The priority %d do not exist in the source dataset', $priority));
        }
        $this->search_results[$priority] = $entry;
    }
}
