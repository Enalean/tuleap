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
 * Assuming that :
 * Release    -> Epic
 * `- Sprint  -> `- Story
 *
 * you want to create a story in a sprint S.
 *
 * Then the selector on story artifact creation will propose only
 * epics associated to S->release
 */
class Planning_ArtifactParentsSelector
{

    /**
     * @var array of Planning_ArtifactParentsSelector_Command
     */
    private $commands;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory, Planning_MilestoneFactory $milestone_factory, Tracker_HierarchyFactory $hierarchy_factory)
    {
        $this->commands = array(
            new Planning_ArtifactParentsSelector_SameTrackerCommand($artifact_factory, $planning_factory, $milestone_factory, $hierarchy_factory),
            new Planning_ArtifactParentsSelector_NearestMilestoneWithBacklogTrackerCommand($artifact_factory, $planning_factory, $milestone_factory, $hierarchy_factory),
            new Planning_ArtifactParentsSelector_ParentInSameHierarchyCommand($artifact_factory, $planning_factory, $milestone_factory, $hierarchy_factory),
            new Planning_ArtifactParentsSelector_SubChildrenBelongingToTrackerCommand($artifact_factory, $planning_factory, $milestone_factory, $hierarchy_factory),
        );
    }

    /**
     * @return array of Tracker_Artifact
     */
    public function getPossibleParents(Tracker $parent_tracker, Tracker_Artifact $source_artifact, PFUser $user)
    {
        foreach ($this->commands as $command) {
            $artifacts = $command->getPossibleParents($parent_tracker, $source_artifact, $user);
            if ($artifacts) {
                return $artifacts;
            }
        }
        return array();
    }
}
