<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\RetrieveArtifact;

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
class Planning_ArtifactLinker // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string LINK_TO_MILESTONE_PARAMETER = 'link-to-milestone';

    public function __construct(
        private readonly RetrieveArtifact $artifact_factory,
        private readonly PlanningFactory $planning_factory,
    ) {
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string}|null $requested_planning
     */
    public function linkBacklogWithPlanningItems(
        Codendi_Request $request,
        Artifact $artifact,
        ?array $requested_planning,
    ): ?Artifact {
        $user               = $request->getCurrentUser();
        $milestone_artifact = $this->getMilestoneArtifact($user, $request, $artifact, $requested_planning);

        return $this->linkWithMilestoneArtifact($user, $artifact, $milestone_artifact);
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string}|null $requested_planning
     */
    private function getMilestoneArtifact(
        PFUser $user,
        Codendi_Request $request,
        Artifact $artifact,
        ?array $requested_planning,
    ): ?Artifact {
        if ($request->exist('link-artifact-id')) {
            return $this->getMilestoneThatHasJustBeenLinkedToTheArtifact($artifact, $user, $request);
        }

        if ($requested_planning && $request->get(self::LINK_TO_MILESTONE_PARAMETER)) {
            return $this->linkArtifactToTheMilestoneThatIsPartOfRedirectionParameter(
                $requested_planning,
                $artifact,
                $user
            );
        }

        return $this->getChildMilestone($request);
    }

    private function linkWithMilestoneArtifact(
        PFUser $user,
        Artifact $artifact,
        ?Artifact $source_artifact = null,
    ): ?Artifact {
        $last_ancestor = $source_artifact;
        if ($source_artifact) {
            foreach ($source_artifact->getAllAncestors($user) as $ancestor) {
                $planning = $this->planning_factory->getPlanningByPlanningTracker($user, $ancestor->getTracker());
                if ($planning && in_array($artifact->getTracker(), $planning->getBacklogTrackers())) {
                    $ancestor->linkArtifact($artifact->getId(), $user);
                    $last_ancestor = $ancestor;
                }
            }
        }

        return $last_ancestor;
    }

    private function getMilestoneThatHasJustBeenLinkedToTheArtifact(
        Artifact $artifact,
        PFUser $user,
        Codendi_Request $request,
    ): ?Artifact {
        $ancestors = $artifact->getAllAncestors($user);
        if (count($ancestors) !== 0) {
            return null;
        }

        return $this->artifact_factory->getArtifactById(
            (int) $request->getValidated('link-artifact-id', 'uint', 0)
        );
    }

    /**
     * @psalm-param array{planning_id: string, pane: string, aid: string} $requested_planning
     */
    private function linkArtifactToTheMilestoneThatIsPartOfRedirectionParameter(
        array $requested_planning,
        Artifact $artifact,
        PFUser $user,
    ): ?Artifact {
        $source_artifact = $this->artifact_factory->getArtifactById(
            (int) $requested_planning[AgileDashboard_PaneRedirectionExtractor::ARTIFACT_ID]
        );
        if ($source_artifact) {
            $source_artifact->linkArtifact($artifact->getId(), $user);
        }

        return $source_artifact;
    }

    private function getChildMilestone(Codendi_Request $request): ?Artifact
    {
        return $this->artifact_factory->getArtifactById(
            (int) $request->getValidated('child_milestone', 'uint', 0)
        );
    }
}
