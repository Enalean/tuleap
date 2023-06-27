<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\RealTime;

final class KanbanArtifactMovedMessageRepresentation
{
    /**
     * @param array $ordered_destination_column_items_ids of all items ids in the destination column.
     *
     * There may be kanban items that the current user cannot see (because
     * of permissions). Let's call this hidden item H.
     * - We can't use a "compared_to" style because if we reorder item A
     * after an item H, we won't know where H is.
     * - We can't rely on rank either, because when an item is reordered,
     * many item's ranks may change. We would have to get the ranks of all
     * the kanban items for every drag'n'drop.
     * Therefore, we send all the ids of the destination column. The
     * kanban app places items that it knows about and ignores those the
     * user can't see.
     */
    public function __construct(
        public readonly array $ordered_destination_column_items_ids,
        public readonly int $artifact_id,
        public readonly string|int $in_column,
        public readonly string|int $from_column,
    ) {
    }
}
