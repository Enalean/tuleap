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

namespace Tuleap\ScaledAgile\Program\Administration\PlannableItems\Presenter;

use PFUser;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsCollection;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;

class PlannableItemsPerTeamPresenterCollectionBuilder
{
    /**
     * @var PlanningAdapter
     */
    private $planning_adapter;

    public function __construct(PlanningAdapter $planning_adapter)
    {
        $this->planning_adapter = $planning_adapter;
    }

    public function buildPresenterCollectionFromObjectCollection(
        PFUser $user,
        PlannableItemsCollection $plannable_items_collection
    ): PlannableItemsPerTeamPresenterCollection {
        $presenters = [];
        foreach ($plannable_items_collection->getPlannableItems() as $plannable_items) {
            $plannable_item_presenters = [];
            foreach ($plannable_items->getTrackersData() as $tracker) {
                $plannable_item_presenters[] = new PlannableItemPresenter(
                    $tracker->getName(),
                    $tracker->getColor()
                );
            }

            $team_project = $plannable_items->getProjectData();
            try {
                $team_root_planning = $this->planning_adapter->buildRootPlanning(
                    $user,
                    $team_project->getID()
                );
            } catch (TopPlanningNotFoundInProjectException $e) {
                //ignore when team does not have a root planning
                $team_root_planning = null;
            }

            $url = null;
            if ($team_root_planning) {
                $url = '/plugins/agiledashboard/?' . http_build_query([
                    'group_id' => $team_project->getID(),
                    'planning_id' => $team_root_planning->getId(),
                    'action' => 'edit',
                ]);
            }

            $presenters[] = new PlannableItemsPerTeamPresenter(
                $team_project->getPublicName(),
                $plannable_item_presenters,
                $url
            );
        }

        return new PlannableItemsPerTeamPresenterCollection($presenters);
    }
}
