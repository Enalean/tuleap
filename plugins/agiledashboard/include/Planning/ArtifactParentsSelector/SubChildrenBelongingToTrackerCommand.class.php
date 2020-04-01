<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

class Planning_ArtifactParentsSelector_SubChildrenBelongingToTrackerCommand extends Planning_ArtifactParentsSelector_Command
{

    /**
     * @see Planning_ArtifactParentsSelector_Command
     *
     * @return array of Tracker_Artifact
     */
    public function getPossibleParents(Tracker $parent_tracker, Tracker_Artifact $source_artifact, PFUser $user)
    {
        $sub_childs = $this->getSubChildrenBelongingToTracker($source_artifact, $parent_tracker, $user);
        if ($sub_childs) {
            return $sub_childs;
        }
    }

    private function getSubChildrenBelongingToTracker(Tracker_Artifact $source_artifact, Tracker $expected_tracker, PFUser $user)
    {
        $hierarchy = $this->getParentTrackersAndStopAtGivenTracker($expected_tracker, $source_artifact->getTracker());
        if ($hierarchy) {
            return $this->recursivelyFindChildrenBelongingToTracker($source_artifact, $expected_tracker, $user, $hierarchy);
        }
    }

    private function recursivelyFindChildrenBelongingToTracker(Tracker_Artifact $source_artifact, Tracker $expected_tracker, PFUser $user, array $hierarchy)
    {
        $artifacts = array();
        $children = $source_artifact->getLinkedArtifactsOfHierarchy($user);
        if (isset($hierarchy[$source_artifact->getId()])) {
            array_walk($children, array($this, 'keepOnlyArtifactsBelongingToParentTracker'), $hierarchy[$source_artifact->getId()]);
            array_filter($children);
        }
        if ($children) {
            foreach ($children as $child) {
                if ((int) $child->getTracker()->getId() === (int) $expected_tracker->getId()) {
                    $artifacts[] = $child;
                } else {
                    $artifacts = array_merge($artifacts, $this->recursivelyFindChildrenBelongingToTracker($child, $expected_tracker, $user, $hierarchy));
                }
            }
        }
        return $artifacts;
    }

    private function getParentTrackersAndStopAtGivenTracker(Tracker $tracker, Tracker $stop)
    {
        $hierarchy = [];
        while (($parent = $this->hierarchy_factory->getParent($tracker)) &&
            (int) $parent->getId() !== (int) $stop->getId()) {
            $hierarchy[$parent->getId()] = $tracker;
            $tracker                     = $parent;
        }

        if (! $parent) {
            return null;
        }

        if ((int) $parent->getId() === (int) $stop->getId()) {
            $hierarchy[$stop->getId()] = $tracker;

            return $hierarchy;
        }
    }
}
