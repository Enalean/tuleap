<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\PlannableItems;

use Project;
use ProjectManager;
use TrackerFactory;

class PlannableItemsCollectionBuilder
{
    /**
     * @var PlannableItemsTrackersDao
     */
    private $dao;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        PlannableItemsTrackersDao $dao,
        TrackerFactory $tracker_factory,
        ProjectManager $project_manager
    ) {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
        $this->project_manager = $project_manager;
    }

    public function buildCollection(Project $project): PlannableItemsCollection
    {
        $plannable_items_rows = $this->dao->getPlannableItemsTrackerIds((int) $project->getID());

        $plannable_items = [];
        foreach ($plannable_items_rows as $plannable_item_row) {
            $project     = $this->project_manager->getProject((int) $plannable_item_row['project_id']);
            $tracker_ids = explode(',', $plannable_item_row['tracker_ids']);

            $trackers = [];
            foreach ($tracker_ids as $tracker_id) {
                $trackers[] = $this->tracker_factory->getTrackerById((int) $tracker_id);
            }

            $plannable_items[] = new PlannableItems(
                $project,
                $trackers
            );
        }

        return new PlannableItemsCollection($plannable_items);
    }
}
