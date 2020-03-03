<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Release     ->   Epic
 * `- Sprint   ->   `- Story
 *
 * => if we create a story in a sprint, then the nearest milestone is the parent
 *    release of the sprint. The release contains epics that are suitable parents
 *    for the upcoming story.
 */
class Planning_ArtifactParentsSelector_NearestMilestoneWithBacklogTrackerCommand extends Planning_ArtifactParentsSelector_Command
{
    /**
     * @see Planning_ArtifactParentsSelector_Command
     *
     * @return array of Tracker_Artifact
     */
    public function getPossibleParents(Tracker $parent_tracker, Tracker_Artifact $source_artifact, PFUser $user)
    {
        $milestone = $this->findNearestMilestoneWithBacklogTracker($parent_tracker, $source_artifact, $user);
        if ($milestone) {
            $linked_artifacts = array();
            foreach ($milestone->getPlannedArtifacts($user)->getChildren() as $child) {
                $linked_artifacts[] = $child->getObject();
            }
            array_walk($linked_artifacts, array($this, 'keepOnlyArtifactsBelongingToParentTracker'), $parent_tracker);
            return array_values(array_filter($linked_artifacts));
        }
    }

    private function findNearestMilestoneWithBacklogTracker(Tracker $expected_backlog_tracker, Tracker_Artifact $source_artifact, PFUser $user)
    {
        $planning = $this->planning_factory->getPlanningByPlanningTracker($source_artifact->getTracker());
        if ($planning && in_array($expected_backlog_tracker->getId(), $planning->getBacklogTrackersIds())) {
            return $this->milestone_factory->getMilestoneFromArtifactWithPlannedArtifacts($source_artifact, $user);
        }

        $parent = $source_artifact->getParent($user);
        if ($parent) {
            return $this->findNearestMilestoneWithBacklogTracker($expected_backlog_tracker, $parent, $user);
        }
    }
}
