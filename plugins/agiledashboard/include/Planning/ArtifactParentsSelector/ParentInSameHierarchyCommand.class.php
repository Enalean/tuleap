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

class Planning_ArtifactParentsSelector_ParentInSameHierarchyCommand extends Planning_ArtifactParentsSelector_Command
{

    /**
     * @see Planning_ArtifactParentsSelector_Command
     *
     * @return array of Tracker_Artifact
     */
    public function getPossibleParents(Tracker $parent_tracker, Tracker_Artifact $source_artifact, PFUser $user)
    {
        $parent_in_same_hierarchy = $this->getParentInSameHierarchy($parent_tracker, $source_artifact, $user);
        if ($parent_in_same_hierarchy) {
            return array($parent_in_same_hierarchy);
        }
    }

    private function getParentInSameHierarchy(Tracker $expected_parent_tracker, Tracker_Artifact $source_artifact, PFUser $user)
    {
        if ((int) $source_artifact->getTracker()->getId() === (int) $expected_parent_tracker->getId()) {
            return $source_artifact;
        } else {
            $parent = $source_artifact->getParent($user);
            if ($parent) {
                return $this->getParentInSameHierarchy($expected_parent_tracker, $parent, $user);
            }
        }
    }
}
