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
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveAnArtifactLinkField;

final readonly class DirectArtifactLinkCleaner
{
    public function __construct(
        private Planning_MilestoneFactory $milestone_factory,
        private ExplicitBacklogDao $explicit_backlog_dao,
        private ArtifactsInExplicitBacklogDao $artifacts_in_explicit_backlog_dao,
        private RetrieveAnArtifactLinkField $artifact_link_field_retriever,
    ) {
    }

    public function cleanDirectlyMadeArtifactLinks(
        Artifact $milestone_artifact,
        PFUser $user,
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

        $milestone_artifact_link_field = $this->artifact_link_field_retriever->getAnArtifactLinkField($user, $milestone_artifact->getTracker());
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
        assert($last_changeset_value instanceof ArtifactLinkChangesetValue);

        $linked_artifact_ids = $last_changeset_value->getArtifactIds();
        if (count($linked_artifact_ids) > 0) {
            $this->artifacts_in_explicit_backlog_dao->cleanUpDirectlyPlannedItemsInArtifact(
                $milestone_artifact->getId(),
                $linked_artifact_ids
            );
        }
    }
}
