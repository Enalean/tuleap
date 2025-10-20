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

readonly class UnplannedArtifactsAdder
{
    public function __construct(
        private ExplicitBacklogDao $explicit_backlog_dao,
        private ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private PlannedArtifactDao $planned_artifact_dao,
    ) {
    }

    /**
     * @throws ArtifactAlreadyPlannedException
     */
    public function addArtifactToTopBacklog(Artifact $artifact): void
    {
        $artifact_id = $artifact->getId();
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
