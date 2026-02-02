<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\Planning;

use PlanningFactory;
use PlanningPermissionsManager;
use Project;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\DB\DBTransactionExecutor;

class PlanningUpdater
{
    public function __construct(
        private readonly PlanningFactory $planning_factory,
        private readonly ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private readonly PlanningDao $planning_dao,
        private readonly PlanningPermissionsManager $permissions_manager,
        private readonly DBTransactionExecutor $transaction_executor,
    ) {
    }

    public function update(\PFUser $user, Project $project, \Planning $original_planning, \PlanningParameters $planning_parameter): void
    {
        $this->transaction_executor->execute(
            function () use ($user, $project, $original_planning, $planning_parameter) {
                $this->planning_dao->updatePlanning($original_planning->getId(), $planning_parameter);

                $original_planning_tracker_id = $original_planning->getPlanningTrackerId();
                if (
                    $original_planning_tracker_id !== null &&
                    $original_planning_tracker_id !== (int) $planning_parameter->planning_tracker_id
                ) {
                    $this->planning_dao->disableBurnupFieldInTracker($original_planning_tracker_id);
                }

                $this->permissions_manager->savePlanningPermissionForUgroups(
                    $original_planning->getId(),
                    $project->getID(),
                    PlanningPermissionsManager::PERM_PRIORITY_CHANGE,
                    $planning_parameter->priority_change_permission
                );

                if ($this->isTheRootPlanning($user, $project, $original_planning->getId())) {
                    $this->artifacts_in_explicit_backlog_dao->removeNoMoreSelectableItemsFromExplicitBacklogOfProject(
                        $planning_parameter->backlog_tracker_ids,
                        (int) $project->getID()
                    );
                }
            }
        );
    }

    private function isTheRootPlanning(\PFUser $user, Project $project, int $updated_planning_id): bool
    {
        $root_planning = $this->planning_factory->getRootPlanning(
            $user,
            (int) $project->getID()
        );

        return $root_planning && $root_planning->getId() === $updated_planning_id;
    }
}
