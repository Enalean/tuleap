<?php
/**
 * Copyright (c) Enalean 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\Tracker\Artifact\Artifact;

class UnplannedArtifactsAdder
{
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    /**
     * @var PlannedArtifactDao
     */
    private $planned_artifact_dao;

    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        PlannedArtifactDao $planned_artifact_dao
    ) {
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->planned_artifact_dao              = $planned_artifact_dao;
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
    }

    /**
     * @throws ArtifactAlreadyPlannedException
     */
    public function addArtifactToTopBacklog(Artifact $artifact): void
    {
        $artifact_id = (int) $artifact->getId();
        $project_id  = (int) $artifact->getTracker()->getGroupId();

        $this->addArtifactToTopBacklogFromIds($artifact_id, $project_id);
    }

    /**
     * @throws ArtifactAlreadyPlannedException
     */
    public function addArtifactToTopBacklogFromIds(int $artifact_id, int $project_id): void
    {
        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return;
        }

        if ($this->planned_artifact_dao->isArtifactPlannedInAMilestoneOfTheProject($artifact_id, $project_id)) {
            throw new ArtifactAlreadyPlannedException();
        }

        $this->artifacts_in_explicit_backlog_dao->addArtifactToProjectBacklog($project_id, $artifact_id);
    }
}
