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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use PFUser;
use Planning_MilestoneFactory;
use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue_ArtifactLink;

class DirectArtifactLinkCleaner
{
    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    /**
     * @var ArtifactsInExplicitBacklogDao
     */
    private $artifacts_in_explicit_backlog_dao;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        ExplicitBacklogDao $explicit_backlog_dao,
        ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao
    ) {
        $this->milestone_factory                 = $milestone_factory;
        $this->explicit_backlog_dao              = $explicit_backlog_dao;
        $this->artifacts_in_explicit_backlog_dao = $artifacts_in_explicit_backlog_dao;
    }

    public function cleanDirectlyMadeArtifactLinks(
        Tracker_Artifact $milestone_artifact,
        PFUser $user
    ): void {
        $project_id = (int) $milestone_artifact->getTracker()->getGroupId();

        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return;
        }

        $milestone = $this->milestone_factory->getBareMilestoneByArtifact(
            $user,
            $milestone_artifact
        );

        if ($milestone === null) {
            return;
        }

        $milestone_artifact_link_field = $milestone_artifact->getAnArtifactLinkField($user);
        if ($milestone_artifact_link_field === null) {
            return;
        }

        $last_changeset = $milestone_artifact->getLastChangeset();
        if ($last_changeset === null) {
            return;
        }

        $last_changeset_value = $last_changeset->getValue($milestone_artifact_link_field);
        if ($last_changeset_value === null) {
            return;
        }
        assert($last_changeset_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        $linked_artifact_ids = $last_changeset_value->getArtifactIds();
        if (count($linked_artifact_ids) > 0) {
            $this->artifacts_in_explicit_backlog_dao->cleanUpDirectlyPlannedItemsInArtifact(
                (int) $milestone_artifact->getId(),
                $linked_artifact_ids
            );
        }
    }
}
