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
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var PlanningPermissionsManager
     */
    private $permissions_manager;
    /**
     * @var PlanningDao
     */
    private $planning_dao;
    /**
     * @var PlanningFactory
     */
    private $planning_factory;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        PlanningFactory $planning_factory,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        PlanningDao $planning_dao,
        PlanningPermissionsManager $permissions_manager,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->planning_factory                  = $planning_factory;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->planning_dao                      = $planning_dao;
        $this->permissions_manager               = $permissions_manager;
        $this->transaction_executor              = $transaction_executor;
    }

    public function update(\PFUser $user, Project $project, int $updated_planning_id, \PlanningParameters $planning_parameter): void
    {
        $this->transaction_executor->execute(
            function () use ($user, $project, $updated_planning_id, $planning_parameter) {
                $this->planning_dao->updatePlanning($updated_planning_id, $planning_parameter);

                $this->permissions_manager->savePlanningPermissionForUgroups(
                    $updated_planning_id,
                    $project->getID(),
                    PlanningPermissionsManager::PERM_PRIORITY_CHANGE,
                    $planning_parameter->priority_change_permission
                );

                if ($this->isTheRootPlanning($user, $project, $updated_planning_id)) {
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
            $project->getID()
        );

        return $root_planning && (int) $root_planning->getId() === $updated_planning_id;
    }
}
