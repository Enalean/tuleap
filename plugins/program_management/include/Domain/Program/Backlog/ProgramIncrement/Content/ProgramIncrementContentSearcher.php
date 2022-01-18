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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureCrossReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveTrackerOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramIncrementContentSearcher
{
    public function __construct(
        private VerifyIsProgramIncrement $program_increment_verifier,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private SearchFeatures $features_searcher,
        private VerifyFeatureIsVisible $feature_verifier,
        private RetrieveFeatureTitle $title_retriever,
        private RetrieveFeatureURI $uri_retriever,
        private RetrieveFeatureCrossReference $cross_reference_retriever,
        private RetrieveTrackerOfFeature $tracker_retriever,
        private RetrieveBackgroundColor $background_retriever,
        private VerifyHasAtLeastOnePlannedUserStory $planned_verifier,
        private FeatureHasUserStoriesVerifier $story_verifier,
        private VerifyFeatureIsOpen $open_verifier,
    ) {
    }

    /**
     * @return Feature[]
     * @throws ProgramIncrementNotFoundException
     */
    public function retrieveProgramIncrementContent(int $id, UserIdentifier $user): array
    {
        $program_increment   = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $id,
            $user
        );
        $feature_identifiers = FeatureIdentifier::buildCollectionFromProgramIncrement(
            $this->features_searcher,
            $this->feature_verifier,
            $program_increment,
            $user
        );

        return array_map(
            fn(FeatureIdentifier $feature) => Feature::fromFeatureIdentifier(
                $this->title_retriever,
                $this->uri_retriever,
                $this->cross_reference_retriever,
                $this->open_verifier,
                $this->planned_verifier,
                $this->story_verifier,
                $this->background_retriever,
                $this->tracker_retriever,
                $feature,
                $user
            ),
            $feature_identifiers
        );
    }
}
