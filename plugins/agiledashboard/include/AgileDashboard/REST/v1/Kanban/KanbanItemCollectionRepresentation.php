<?php
/**
 * Copyright (c) Enalean, 2014-2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1\Kanban;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanItemDao;
use Tracker_ArtifactFactory;
use PFUser;

class KanbanItemCollectionRepresentation {

    /** @var array */
    public $collection;

    /** @var int */
    public $total_size;

    public function build(PFUser $user, AgileDashboard_Kanban $kanban, $column_id, $limit, $offset) {
        $dao             = new AgileDashboard_KanbanItemDao();
        $factory         = Tracker_ArtifactFactory::instance();
        $timeinfofactory = new TimeInfoFactory($dao);

        $data    = $dao->searchPaginatedItemsInColumn($kanban->getTrackerId(), $column_id, $limit, $offset);

        $this->total_size = (int) $dao->foundRows();
        $this->collection = array();
        foreach ($data as $row) {
            $artifact = $factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $item_representation = new KanbanItemRepresentation();
                $item_representation->build(
                    $artifact,
                    $timeinfofactory->getTimeInfo($artifact),
                    $column_id
                );

                $this->collection[] = $item_representation;
            }
        }
    }
}
