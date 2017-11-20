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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.w
 */

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanItemDao;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_ArtifactFactory;

class KanbanBacklogRepresentationBuilder
{
    /** @var AgileDashboard_KanbanItemDao */
    private $kanban_item_dao;
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(
        AgileDashboard_KanbanItemDao $kanban_item_dao,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->kanban_item_dao  = $kanban_item_dao;
        $this->artifact_factory = $artifact_factory;
    }

    public function build(PFUser $user, AgileDashboard_Kanban $kanban, $limit, $offset)
    {
        $data = $this->kanban_item_dao->searchPaginatedBacklogItemsByTrackerId(
            $kanban->getTrackerId(),
            $limit,
            $offset
        );

        $total_size = (int) $this->kanban_item_dao->foundRows();
        $collection = array();
        foreach ($data as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $item_representation = new KanbanItemRepresentation();
                $item_representation->build(
                    $artifact,
                    array(),
                    KanbanColumnRepresentation::BACKLOG_COLUMN
                );

                $collection[] = $item_representation;
            }
        }

        return new KanbanBacklogRepresentation($collection, $total_size);
    }
}
