<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\ArtifactIdentifierProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\Links\FeatureIsNotPlannableException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\FeatureNotAccessException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Links\SearchChildrenOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanStore;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\UserStoryRepresentation;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

final class UserStoryRepresentationBuilder
{
    public function __construct(
        private SearchChildrenOfFeature $search_children_of_feature,
        private \Tracker_ArtifactFactory $artifact_factory,
        private PlanStore $plan_store,
        private RetrieveBackgroundColor $retrieve_background_color,
        private RetrieveUser $retrieve_user
    ) {
    }

    /**
     * @return UserStoryRepresentation[]
     * @throws FeatureNotAccessException
     * @throws FeatureIsNotPlannableException
     */
    public function buildFeatureStories(int $feature_id, UserIdentifier $user_identifier): array
    {
        $user    = $this->retrieve_user->getUserWithId($user_identifier);
        $feature = $this->artifact_factory->getArtifactByIdUserCanView($user, $feature_id);
        if (! $feature) {
            throw new FeatureNotAccessException();
        }
        $feature_tracker_is_plannable = $this->plan_store->isPlannable($feature->getTrackerId());

        if (! $feature_tracker_is_plannable) {
            throw new FeatureIsNotPlannableException($feature->getTrackerId());
        }

        $linked_children  = [];
        $planned_children = $this->search_children_of_feature->getChildrenOfFeatureInTeamProjects($feature_id);
        foreach ($planned_children as $planned_child) {
            $story = $this->artifact_factory->getArtifactByIdUserCanView($user, $planned_child['children_id']);
            if ($story) {
                $linked_children[] = new UserStoryRepresentation(
                    $story->getId(),
                    $story->getUri(),
                    $story->getXRef(),
                    $story->getTitle(),
                    $story->isOpen(),
                    new ProjectReference($story->getTracker()->getProject()),
                    MinimalTrackerRepresentation::build($story->getTracker()),
                    $this->retrieve_background_color->retrieveBackgroundColor(
                        ArtifactIdentifierProxy::fromArtifact($story),
                        $user_identifier
                    )->getBackgroundColorName(),
                );
            }
        }

        return $linked_children;
    }
}
