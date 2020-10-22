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
use PlanningFactory;
use Tuleap\ScaledAgile\Program\Administration\PlannableItems\PlannableItemsCollection;

class PlannableItemsPerTeamPresenterCollectionBuilder
{
    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    public function buildPresenterCollectionFromObjectCollection(
        PFUser $user,
        PlannableItemsCollection $plannable_items_collection
    ): PlannableItemsPerTeamPresenterCollection {
        $presenters = [];
        foreach ($plannable_items_collection->getPlannableItems() as $plannable_items) {
            $plannable_item_presenters = [];
            foreach ($plannable_items->getTrackers() as $tracker) {
                $plannable_item_presenters[] = new PlannableItemPresenter(
                    (string) $tracker->getName(),
                    $tracker->getColor()
                );
            }

            $team_project       = $plannable_items->getProject();
            $team_root_planning = $this->planning_factory->getRootPlanning(
                $user,
                (int) $team_project->getID()
            );

            $url = null;
            if ($team_root_planning) {
                $url = '/plugins/agiledashboard/?' . http_build_query([
                    'group_id' => $team_project->getID(),
                    'planning_id' => $team_root_planning->getId(),
                    'action' => 'edit',
                ]);
            }

            $presenters[] = new PlannableItemsPerTeamPresenter(
                (string) $team_project->getPublicName(),
                $plannable_item_presenters,
                $url
            );
        }

        return new PlannableItemsPerTeamPresenterCollection($presenters);
    }
}
