<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * @psalm-immutable
 */
final class Feature
{
    private function __construct(
        public FeatureIdentifier $feature_identifier,
        public string $uri,
        public string $cross_reference,
        public ?string $title,
        public bool $is_open,
        public bool $is_linked_to_at_least_one_planned_user_story,
        public bool $has_at_least_one_story,
        public BackgroundColor $background_color,
        public FeatureTrackerIdentifier $feature_tracker_identifier,
    ) {
    }

    public static function fromFeatureIdentifier(
        RetrieveFeatureTitle $title_retriever,
        RetrieveFeatureURI $uri_retriever,
        RetrieveFeatureCrossReference $cross_reference_retriever,
        VerifyFeatureIsOpen $open_verifier,
        VerifyHasAtLeastOnePlannedUserStory $planned_verifier,
        FeatureHasUserStoriesVerifier $story_verifier,
        RetrieveBackgroundColor $background_retriever,
        RetrieveTrackerOfFeature $tracker_retriever,
        FeatureIdentifier $feature_identifier,
        UserIdentifier $user_identifier,
    ): self {
        return new self(
            $feature_identifier,
            $uri_retriever->getFeatureURI($feature_identifier),
            $cross_reference_retriever->getFeatureCrossReference($feature_identifier),
            $title_retriever->getFeatureTitle($feature_identifier, $user_identifier),
            $open_verifier->isFeatureOpen($feature_identifier),
            $planned_verifier->hasAtLeastOnePlannedUserStory($feature_identifier, $user_identifier),
            $story_verifier->hasStoryLinked($feature_identifier, $user_identifier),
            $background_retriever->retrieveBackgroundColor($feature_identifier, $user_identifier),
            FeatureTrackerIdentifier::fromFeature($tracker_retriever, $feature_identifier),
        );
    }
}
