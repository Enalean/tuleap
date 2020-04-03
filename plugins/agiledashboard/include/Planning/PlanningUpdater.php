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

use Project;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;

class PlanningUpdater
{
    /**
     * @var \PlanningFactory
     */
    private $planning_factory;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    public function __construct(
        \PlanningFactory $planning_factory,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao
    ) {
        $this->planning_factory                  = $planning_factory;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
    }

    public function update(\PFUser $user, Project $project, int $updated_planning_id, \PlanningParameters $planning_parameter): void
    {
        $this->planning_factory->updatePlanning(
            $updated_planning_id,
            $project->getID(),
            $planning_parameter
        );

        if ($this->isTheRootPlanning($user, $project, $updated_planning_id)) {
            $this->artifacts_in_explicit_backlog_dao->removeNoMoreSelectableItemsFromExplicitBacklogOfProject(
                $planning_parameter->backlog_tracker_ids,
                (int) $project->getID()
            );
        }
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
