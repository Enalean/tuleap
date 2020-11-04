<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Administration\PlannableItems;

use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Adapter\ProjectDataAdapter;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\ScaledAgile\TrackerNotFoundException;

class PlannableItemsCollectionBuilder
{
    /**
     * @var PlannableItemsTrackersDao
     */
    private $dao;

    /**
     * @var TrackerDataAdapter
     */
    private $tracker_data_adapter;

    /**
     * @var ProjectDataAdapter
     */
    private $project_data_adapter;

    public function __construct(
        PlannableItemsTrackersDao $dao,
        TrackerDataAdapter $tracker_data_adapter,
        ProjectDataAdapter $project_data_adapter
    ) {
        $this->dao                  = $dao;
        $this->tracker_data_adapter = $tracker_data_adapter;
        $this->project_data_adapter = $project_data_adapter;
    }

    /**
     * @throws TrackerNotFoundException
     */
    public function buildCollection(PlanningData $project_root_planning): PlannableItemsCollection
    {
        $plannable_items_rows = $this->dao->getPlannableItemsTrackerIds($project_root_planning->getID());

        $plannable_items = [];
        foreach ($plannable_items_rows as $plannable_item_row) {
            $project     = $this->project_data_adapter->buildFromId((int) $plannable_item_row['project_id']);
            $tracker_ids = explode(',', $plannable_item_row['tracker_ids']);

            $trackers = [];
            foreach ($tracker_ids as $tracker_id) {
                $trackers[] = $this->tracker_data_adapter->buildByTrackerID((int) $tracker_id);
            }

            $plannable_items[] = new PlannableItems(
                $project,
                $trackers
            );
        }

        return new PlannableItemsCollection($plannable_items);
    }
}
