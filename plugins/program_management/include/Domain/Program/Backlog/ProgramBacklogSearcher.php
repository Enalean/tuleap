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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\FeatureHasUserStoriesVerifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\VerifyHasAtLeastOnePlannedUserStory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Feature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureCrossReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureTitle;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveFeatureURI;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\RetrieveTrackerOfFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchPlannableFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsOpen;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Feature\RetrieveBackgroundColor;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramBacklogSearcher
{
    public function __construct(
        private BuildProgram $build_program,
        private SearchPlannableFeatures $search_plannable_features,
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
     *
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     */
    public function retrieveFeaturesToBePlanned(int $program_id, UserIdentifier $user): array
    {
        $program = ProgramIdentifier::fromId($this->build_program, $program_id, $user, null);

        $feature_identifiers = FeatureIdentifier::buildCollectionFromProgram(
            $this->search_plannable_features,
            $this->feature_verifier,
            $program,
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
