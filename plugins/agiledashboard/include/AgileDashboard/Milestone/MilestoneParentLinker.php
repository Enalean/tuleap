<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\Link\ArtifactLinker;
use Tuleap\Tracker\Artifact\Link\ForwardLinkProxy;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\Tracker;

class MilestoneParentLinker
{
    public function __construct(
        private readonly Planning_MilestoneFactory $milestone_factory,
        private readonly AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
        private readonly ArtifactLinker $artifact_linker,
    ) {
    }

    public function linkToMilestoneParent(Planning_Milestone $milestone, PFUser $user, Artifact $artifact_added): void
    {
        $this->milestone_factory->addMilestoneAncestors($user, $milestone);

        $parent_milestone = $milestone->getParent();

        if (! $parent_milestone) {
            return;
        }

        $parent_milestone_artifact = $parent_milestone->getArtifact();

        if (! $this->parentMilestoneHasItemTrackerInItsBacklogTracker($user, $parent_milestone, $artifact_added)) {
            return;
        }

        if (
            ! $this->isParentLinkedToParentMilestone(
                $artifact_added,
                $parent_milestone_artifact,
                $user
            )
        ) {
            $this->artifact_linker->linkArtifact($parent_milestone_artifact, new CollectionOfForwardLinks([
                ForwardLinkProxy::buildFromData($artifact_added->getId(), ArtifactLinkField::DEFAULT_LINK_TYPE),
            ]), $user);
            $this->linkToMilestoneParent($parent_milestone, $user, $artifact_added);
        }
    }

    /**
     * @return Tracker[]
     */
    private function getBacklogTrackers(PFUser $user, Planning_Milestone $milestone): array
    {
        return $this->backlog_factory->getBacklog($user, $milestone)->getDescendantTrackers();
    }

    private function parentMilestoneHasItemTrackerInItsBacklogTracker(
        PFUser $user,
        Planning_Milestone $parent_milestone,
        Artifact $artifact_added,
    ): bool {
        $backlog_trackers = $this->getBacklogTrackers($user, $parent_milestone);

        foreach ($backlog_trackers as $backlog_tracker) {
            if ($backlog_tracker->getId() === $artifact_added->getTrackerId()) {
                return true;
            }
        }

        return false;
    }

    private function isParentLinkedToParentMilestone(
        Artifact $artifact_added,
        Artifact $parent_milestone_artifact,
        PFUser $user,
    ): bool {
        $parent = $artifact_added->getParent($user);

        if (! $parent) {
            return false;
        }

        $linked_artifacts = $parent_milestone_artifact->getLinkedArtifacts($user);

        foreach ($linked_artifacts as $linked_artifact) {
            if ($linked_artifact->getId() === $parent->getId()) {
                return true;
            }
        }

        return false;
    }
}
