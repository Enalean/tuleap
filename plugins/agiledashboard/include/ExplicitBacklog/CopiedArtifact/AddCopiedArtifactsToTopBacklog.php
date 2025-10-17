<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog\CopiedArtifact;

use Project;
use Tracker_XML_Importer_ArtifactImportedMapping;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Planning\PlanningDao;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final readonly class AddCopiedArtifactsToTopBacklog
{
    public function __construct(
        private ExplicitBacklogDao $explicit_backlog_dao,
        private ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private PlannedArtifactDao $planned_artifact_dao,
        private RetrieveArtifact $artifact_retriever,
        private PlanningDao $planning_dao,
    ) {
    }

    public function addCopiedArtifactsToTopBacklog(
        Tracker_XML_Importer_ArtifactImportedMapping $artifact_imported_mapping,
        Project $project,
    ): void {
        $project_id = (int) $project->getID();

        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return;
        }

        foreach ($artifact_imported_mapping->getMapping() as $source_artifact_id => $copied_artifact_id) {
            $source_artifact = $this->artifact_retriever->getArtifactById($source_artifact_id);
            if ($source_artifact === null) {
                continue;
            }
            if ($this->planning_dao->searchBacklogTrackersByTrackerId($source_artifact->getTrackerId()) === []) {
                continue;
            }

            if (
                $this->artifacts_in_explicit_backlog_dao->isArtifactInTopBacklogOfProject($source_artifact_id, $project_id) ||
                $this->planned_artifact_dao->isArtifactPlannedInAMilestoneOfTheProject($source_artifact_id, $project_id)
            ) {
                $this->artifacts_in_explicit_backlog_dao->addArtifactToProjectBacklog($project_id, $copied_artifact_id);
            }
        }
    }
}
