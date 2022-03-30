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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use PFUser;
use Planning;
use PlanningFactory;
use Tracker_Artifact_Changeset;

class ArtifactLinkRepository
{
    /** @var PlanningFactory */
    private $planning_factory;

    public function __construct(PlanningFactory $planning_factory)
    {
        $this->planning_factory = $planning_factory;
    }

    /**
     * Find ids of all artifacts linked to a given artifact at a given date time (i.e. given change set).
     * Warning: artifacts from tracker associated to a planning (on changeset project) as milestone are skipped.
     * @return int[]
     */
    public function findLinkedArtifactIds(
        PFUser $current_user,
        Tracker_Artifact_Changeset $changeset,
    ): array {
        $artifact_link_field = $changeset->getArtifact()->getAnArtifactLinkField($current_user);
        if ($artifact_link_field === null) {
            return [];
        }

        $tracker_artifacts = $artifact_link_field->getLinkedArtifacts($changeset, $current_user);

        $project_id            = (int) $changeset->getTracker()->getGroupId();
        $milestone_tracker_ids = $this->findMilestoneTrackerIds($current_user, $project_id);

        $backlog_artifacts = array_filter(
            $tracker_artifacts,
            static function (\Tuleap\Tracker\Artifact\Artifact $tracker_artifact) use ($milestone_tracker_ids) {
                return ! in_array($tracker_artifact->getTrackerId(), $milestone_tracker_ids, true);
            }
        );

        return array_values(
            array_map(
                static function (\Tuleap\Tracker\Artifact\Artifact $tracker_artifact) {
                    return $tracker_artifact->getId();
                },
                $backlog_artifacts
            )
        );
    }

    /**
     * @return int[]
     */
    private function findMilestoneTrackerIds(PFUser $current_user, int $project_id): array
    {
        $plannings = $this->planning_factory->getPlannings(
            $current_user,
            $project_id
        );

        $milestone_tracker_ids = array_map(
            static function (Planning $planning) {
                return $planning->getPlanningTrackerId();
            },
            $plannings
        );
        return $milestone_tracker_ids;
    }
}
