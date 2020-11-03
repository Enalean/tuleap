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

use PFUser;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\Adapter\Program\PlanningAdapter;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\TopPlanningNotFoundInProjectException;
use Tuleap\ScaledAgile\Team\TeamDao;

class PlannableItemsTrackersUpdater
{
    /**
     * @var TeamDao
     */
    private $team_dao;

    /**
     * @var PlannableItemsTrackersDao
     */
    private $plannable_items_trackers_dao;

    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    /**
     * @var PlanningAdapter
     */
    private $planning_adapter;

    public function __construct(
        TeamDao $team_dao,
        PlannableItemsTrackersDao $plannable_items_trackers_dao,
        DBTransactionExecutor $transaction_executor,
        PlanningAdapter $planning_adapter
    ) {
        $this->team_dao                     = $team_dao;
        $this->plannable_items_trackers_dao = $plannable_items_trackers_dao;
        $this->transaction_executor         = $transaction_executor;
        $this->planning_adapter             = $planning_adapter;
    }

    /**
     * @throws TopPlanningNotFoundInProjectException
     */
    public function updatePlannableItemsTrackersFromPlanning(PlanningData $updated_planning, PFUser $user): void
    {
        $project_id = $updated_planning->getProjectData()->getId();

        if (! $this->team_dao->isProjectATeamProject($project_id)) {
            return;
        }

        $this->transaction_executor->execute(
            function () use ($updated_planning, $user, $project_id) {
                $program_project_ids_rows = $this->team_dao->getProgramProjectsOfAGivenTeamProject(
                    $project_id
                );

                foreach ($program_project_ids_rows as $program_project_ids_row) {
                    $program_project_id = (int) $program_project_ids_row['program_project_id'];

                    $program_top_planning = $this->planning_adapter->buildRootPlanning($user, $program_project_id);

                    $program_top_planning_id = $program_top_planning->getId();

                    $this->plannable_items_trackers_dao->deletePlannableItemsTrackerIdsOfAGivenTeamProject(
                        $project_id,
                        $program_top_planning_id
                    );

                    $this->plannable_items_trackers_dao->addPlannableItemsTrackerIds(
                        $program_top_planning_id,
                        $updated_planning->getPlannableTrackerIds()
                    );
                }
            }
        );
    }
}
