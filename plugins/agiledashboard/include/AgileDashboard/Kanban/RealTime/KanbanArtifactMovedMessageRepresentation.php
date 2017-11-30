<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban\RealTime;

use AgileDashboard_KanbanItemDao;
use Tracker_Artifact;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;

class KanbanArtifactMovedMessageRepresentation
{
    /**
     * @var array of all items ids in the destination column.
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
    public $ordered_destination_column_items_ids;
    public $artifact_id;
    public $in_column;
    public $from_column;

    public function __construct(array $ordered_destination_column_items_ids, $artifact_id, $in_column, $from_column)
    {
        $this->ordered_destination_column_items_ids = $ordered_destination_column_items_ids;
        $this->artifact_id                          = intval($artifact_id);
        $this->in_column                            = $in_column;
        $this->from_column                          = $from_column;
    }
}
