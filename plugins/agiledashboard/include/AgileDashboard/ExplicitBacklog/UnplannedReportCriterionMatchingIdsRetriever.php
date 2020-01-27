<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use PFUser;
use Tracker;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Artifact\PlannedArtifactDao;

class UnplannedReportCriterionMatchingIdsRetriever
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var PlannedArtifactDao
     */
    private $planned_artifact_dao;
    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        PlannedArtifactDao $planned_artifact_dao,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
        $this->planned_artifact_dao              = $planned_artifact_dao;
        $this->artifact_factory                  = $artifact_factory;
    }

    /**
     * @psalm-return array<int, true>
     *
     * @throws ProjectNotUsingExplicitBacklogException
     */
    public function getMatchingIds(Tracker $tracker, PFUser $user): array
    {
        $matching_ids = [];
        $project      = $tracker->getProject();
        $project_id   = (int) $project->getID();
        $tracker_id   = (int) $tracker->getId();

        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id) === false) {
            throw new ProjectNotUsingExplicitBacklogException();
        }

        foreach ($this->getUnplannedArtifactIds($tracker_id, $project_id) as $unplanned_artifact_id) {
            $artifact = $this->artifact_factory->getArtifactByIdUserCanView(
                $user,
                $unplanned_artifact_id
            );

            if ($artifact !== null) {
                $matching_ids[(int) $unplanned_artifact_id] = true;
            }
        }

        return $matching_ids;
    }

    private function extractArtifactIdsFromResult(array $result): array
    {
        return array_map(
            function (array $item) {
                return $item['artifact_id'];
            },
            $result
        );
    }

    private function getUnplannedArtifactIds(int $tracker_id, int $project_id): array
    {
        $artifacts_not_in_top_backlog = $this->artifacts_in_explicit_backlog_dao->getAllArtifactNotInTopBacklogInTracker(
            $tracker_id
        );

        $artifacts_planned = $this->planned_artifact_dao->gatAllPlannedArtifactsOfTheProject(
            $project_id,
            $tracker_id
        );

        $artifact_ids_not_in_top_backlog = $this->extractArtifactIdsFromResult($artifacts_not_in_top_backlog);
        $artifact_ids_planned            = $this->extractArtifactIdsFromResult($artifacts_planned);

        return array_diff($artifact_ids_not_in_top_backlog, $artifact_ids_planned);
    }
}
