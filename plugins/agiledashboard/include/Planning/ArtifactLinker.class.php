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

use Tuleap\Tracker\Artifact\Artifact;

/**
 * Ensure consistency of backlogs.
 *
 * When an element is added to a Plannification item, it must be added to Parents
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
class Planning_ArtifactLinker
{
    private $artifact_factory;
    private $planning_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory)
    {
        $this->artifact_factory = $artifact_factory;
        $this->planning_factory = $planning_factory;
    }

    /**
     * Ensure consistency of backlogs
     *
     * This method returns the last milestone artifact we linked $artifact with
     *
     * @param Codendi_Request $request  The comment about the request parameter
     * @param Artifact        $artifact The just created artifact
     *
     * @return Artifact
     */
    public function linkBacklogWithPlanningItems(Codendi_Request $request, Artifact $artifact)
    {
        $user               = $request->getCurrentUser();
        $milestone_artifact = $this->getMilestoneArtifact($user, $request, $artifact);
        return $this->linkWithMilestoneArtifact($user, $artifact, $milestone_artifact);
    }

    private function getMilestoneArtifact(PFUser $user, Codendi_Request $request, Artifact $artifact)
    {
        $source_artifact = null;
        if ($request->exist('link-artifact-id')) {
            $ancestors = $artifact->getAllAncestors($user);
            if (count($ancestors) == 0) {
                $source_artifact = $this->getSourceArtifact($request, 'link-artifact-id');
            }
        } else {
            $source_artifact = $this->getSourceArtifact($request, 'child_milestone');
        }
        return $source_artifact;
    }

    private function getSourceArtifact(Codendi_Request $request, $key)
    {
        $artifact_id = (int) $request->getValidated($key, 'uint', 0);
        return $this->artifact_factory->getArtifactById($artifact_id);
    }

    private function linkWithMilestoneArtifact(PFUser $user, Artifact $artifact, ?Artifact $source_artifact = null)
    {
        $last_ancestor = $source_artifact;
        if ($source_artifact) {
            foreach ($source_artifact->getAllAncestors($user) as $ancestor) {
                $planning = $this->planning_factory->getPlanningByPlanningTracker($ancestor->getTracker());
                if ($planning && in_array($artifact->getTracker(), $planning->getBacklogTrackers())) {
                    $ancestor->linkArtifact($artifact->getId(), $user);
                    $last_ancestor = $ancestor;
                }
            }
        }
        return $last_ancestor;
    }
}
