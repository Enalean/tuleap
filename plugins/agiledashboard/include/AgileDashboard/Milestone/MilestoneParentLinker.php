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

class MilestoneParentLinker
{
    /**
     * @var AgileDashboard_Milestone_Backlog_BacklogFactory
     */
    private $backlog_factory;

    /**
     * @var Planning_MilestoneFactory
     */
    private $milestone_factory;

    public function __construct(
        Planning_MilestoneFactory $milestone_factory,
        AgileDashboard_Milestone_Backlog_BacklogFactory $backlog_factory,
    ) {
        $this->milestone_factory = $milestone_factory;
        $this->backlog_factory   = $backlog_factory;
    }

    public function linkToMilestoneParent(Planning_Milestone $milestone, PFUser $user, Artifact $artifact_added)
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
            $parent_milestone_artifact->linkArtifact($artifact_added->getId(), $user);
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
    ) {
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
