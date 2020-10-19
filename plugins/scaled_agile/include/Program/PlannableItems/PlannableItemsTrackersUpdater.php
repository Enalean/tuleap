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

namespace Tuleap\ScaledAgile\Program\PlannableItems;

use PFUser;
use Planning;
use PlanningFactory;
use Tuleap\DB\DBTransactionExecutor;
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
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(
        TeamDao $team_dao,
        PlannableItemsTrackersDao $plannable_items_trackers_dao,
        PlanningFactory $planning_factory,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->team_dao              = $team_dao;
        $this->plannable_items_trackers_dao = $plannable_items_trackers_dao;
        $this->transaction_executor         = $transaction_executor;
        $this->planning_factory             = $planning_factory;
    }

    /**
     * @throws TopPlanningNotFoundInProgramProjectException
     */
    public function updatePlannableItemsTrackersFromPlanning(Planning $updated_planning, PFUser $user): void
    {
        $project_id = (int) $updated_planning->getGroupId();

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

                    $program_top_planning = $this->planning_factory->getRootPlanning(
                        $user,
                        $program_project_id
                    );

                    if (! $program_top_planning) {
                        throw new TopPlanningNotFoundInProgramProjectException($program_project_id);
                    }

                    $program_top_planning_id = (int) $program_top_planning->getId();

                    $this->plannable_items_trackers_dao->deletePlannableItemsTrackerIdsOfAGivenTeamProject(
                        $project_id,
                        $program_top_planning_id
                    );

                    $this->plannable_items_trackers_dao->addPlannableItemsTrackerIds(
                        $program_top_planning_id,
                        $updated_planning->getBacklogTrackersIds()
                    );
                }
            }
        );
    }
}
