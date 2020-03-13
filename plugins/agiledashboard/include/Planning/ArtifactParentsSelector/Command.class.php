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

abstract class Planning_ArtifactParentsSelector_Command
{

    /**
     * @var Tracker_ArtifactFactory
     */
    protected $artifact_factory;

    /**
     * @var PlanningFactory
     */
    protected $planning_factory;

    /**
     * @var Planning_MilestoneFactory
     */
    protected $milestone_factory;

    /**
     * @var Tracker_HierarchyFactory
     */
    protected $hierarchy_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory, Planning_MilestoneFactory $milestone_factory, Tracker_HierarchyFactory $hierarchy_factory)
    {
        $this->artifact_factory  = $artifact_factory;
        $this->planning_factory  = $planning_factory;
        $this->milestone_factory = $milestone_factory;
        $this->hierarchy_factory = $hierarchy_factory;
    }

    abstract public function getPossibleParents(Tracker $parent_tracker, Tracker_Artifact $source_artifact, PFUser $user);

    /**
     * @return array of Tracker_Artifact
     */
    protected function keepOnlyArtifactsBelongingToParentTracker(&$artifact, $key, $parent_tracker)
    {
        if ((int) $artifact->getTracker()->getId() !== (int) $parent_tracker->getId()) {
            $artifact = null;
        }
    }
}
