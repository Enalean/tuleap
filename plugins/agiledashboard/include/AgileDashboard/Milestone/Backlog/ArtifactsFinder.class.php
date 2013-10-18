<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * Given a Milestone backlog (list of artifacts), I extract all the artifacts
 * that match a given list of tracker ($submilestone_trackers).
 * If I don't find relevant artifacts in the initial set of artifacts, I continue
 * to search in their children until I find something that match what I expect.
 *
 * If thou read this, you might be one of those curious developers who like to
 * understand how things goes (so am I!). So here are the nifty details:
 *
 * Once upon a time you have a milestone (let say it's a Release) with 2 epics
 * and a submilestone type (sprints) that accept both Bugs and Stories as backlog
 * Items ($submilestone_trackers == [bug, story]):
 *
 * Release 1.0
 * |-- Epic 1
 * |   |-- Bug 55
 * |   `-- Story 4
 * |       `-- Bug 12
 * |
 * |-- Epic 2
 * |   `-- support request 36
 * `-- Story 33
 *
 * We do a Breadth-first traversal strategy:
 * #1 In [Epic 1, Epic 2, Story 33]
 *   #1.1 what can I pick => [Story 33]
 *   #1.2 continue to search in [Epic1, Epic2] children [Bug 55, Story 4, support request 36]
 * #2 In [Bug 55, Story 4, support request 36
 *   #2.1 what can I pick => [Bug 55, Story 4]
 *   #2.2 continue to search in [support request 36] children []
 * #3 In []
 *   -> staph
 *
 * Some explaination
 *   - at #1.2, I don't continue to search in Story 4 children as I already found something I can manage
 *   - there is 1 SQL query per tree depth until we found something we can manage
 *     so algorithm complexity is linear with tree depth.
 *
 * Algorythm resources:
 * - http://en.wikipedia.org/wiki/Tree_traversal (look at Breadth-first traversal strategy)
 * - Knut & Dijkstra probably wrote jokes about that too.
 *
 */
class AgileDashboard_Milestone_Backlog_ArtifactsFinder {

    /** @var Tracker_Artifact[] */
    private $milestone_backlog_artifacts;

    /** @var Integer[] */
    private $submilestone_tracker_id_cache;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, $milestone_backlog_artifacts, array $submilestone_trackers) {
        $this->artifact_factory = $artifact_factory;
        $this->milestone_backlog_artifacts = $milestone_backlog_artifacts;
        foreach ($submilestone_trackers as $tracker) {
            $this->submilestone_tracker_id_cache[$tracker->getId()] = true;
        }
    }

    /**
     * List the artifacts the user can see
     *
     * @param PFUser $user
     * @return Tracker_Artifact[]
     */
    public function getArtifacts(PFUser $user) {
        return $this->artifact_factory->sortByPriority(
            $this->getArtifactsManagableByChildMilestones($user, $this->milestone_backlog_artifacts)
        );
    }

    private function getArtifactsManagableByChildMilestones(PFUser $user, $artifacts) {
        if (count($artifacts) > 0) {
            $backlog = array();
            $artifacts_to_inspect = array();
            foreach ($artifacts as $artifact) {
                if ($this->artifactIsManagableByChildMilestones($artifact)) {
                    $backlog[] = $artifact;
                } else {
                    $artifacts_to_inspect[] = $artifact;
                }
            }
            return array_merge(
                $backlog,
                $this->getArtifactsManagableByChildMilestones(
                    $user,
                    $this->artifact_factory->getChildrenForArtifacts($user, $artifacts_to_inspect)
                )
            );
        } else {
            return array();
        }
    }

    private function artifactIsManagableByChildMilestones(Tracker_Artifact $artifact) {
        return isset($this->submilestone_tracker_id_cache[$artifact->getTrackerId()]);
    }
}

?>
