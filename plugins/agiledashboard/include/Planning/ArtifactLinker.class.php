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
 * Ensure consistency of backlogs.
 *
 * When an element is added to a Plannification item, it must be add to Parents
 * plannification item as well
 *
 * Given I have following plannings
 * Epic -> Product
 * Epic -> Release
 *
 * And following hierarchy
 * Product -> Release (let say "Product Toto" and "Release 1.0")
 *
 * When I add a new Epic into "Release 1.0" backlog, it must be added into
 * "Product Toto" backlog as well
 */
class Planning_ArtifactLinker {
    private $artifact_factory;
    private $planning_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory) {
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
    }

    public function linkWithParents(Codendi_Request $request, Tracker_Artifact $artifact) {
        $user      = $request->getCurrentUser();
        $ancestors = $artifact->getAllAncestors($user);
        if (count($ancestors) == 0) {
            $source_artifact = $this->getSourceArtifact($request, 'link-artifact-id');
            if ($source_artifact) {
                foreach ($source_artifact->getAllAncestors($user) as $ancestor) {
                    $planning = $this->planning_factory->getPlanningByPlanningTracker($ancestor->getTracker());
                    if ($planning->getBacklogTracker() == $artifact->getTracker()) {
                        $ancestor->linkArtifact($artifact->getId(), $user);
                    }
                }
            }
        }
    }

    private function getSourceArtifact(Codendi_Request $request, $key) {
        $artifact_id = (int) $request->getValidated($key, 'uint', 0);
        return $this->artifact_factory->getArtifactById($artifact_id);
    }

    public function linkWithPlanningParams(Codendi_Request $request, Tracker_Artifact $artifact) {
        $user = $request->getCurrentUser();
        $descendant_milestone_artifact = $this->getSourceArtifact($request, 'child_milestone');
        if ($descendant_milestone_artifact) {
            $this->linkWithPlanning($user, $artifact, $descendant_milestone_artifact);
        }
    }

    public function linkWithPlanning(User $user, Tracker_Artifact $new_artifact, Tracker_Artifact $descendant_milestone_artifact) {
        $milestone_artifact_ancestors = $descendant_milestone_artifact->getAllAncestors($user);
        foreach ($milestone_artifact_ancestors as $milestone_artifact_ancestor) {
            $planning = $this->planning_factory->getPlanningByPlanningTracker($milestone_artifact_ancestor->getTracker());
            if ($planning->getBacklogTracker() == $new_artifact->getTracker()) {
                $milestone_artifact_ancestor->linkArtifact($new_artifact->getId(), $user);
            }
        }
    }
}

?>
