<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Hierarchy;

use Tuleap\Option\Option;
use Tuleap\Tracker\RetrieveTracker;

final readonly class ParentInHierarchyRetriever
{
    public function __construct(
        private SearchParentTracker $search_parent_tracker,
        private RetrieveTracker $retrieve_tracker,
    ) {
    }

    /**
     * @return Option<\Tuleap\Tracker\Tracker>
     */
    public function getParentTracker(\Tuleap\Tracker\Tracker $child_tracker): Option
    {
        return $this->search_parent_tracker->searchParentId($child_tracker->getId())
            ->andThen(
                fn(int $parent_tracker_id) => Option::fromNullable(
                    $this->retrieve_tracker->getTrackerById($parent_tracker_id)
                )
            );
    }
}
