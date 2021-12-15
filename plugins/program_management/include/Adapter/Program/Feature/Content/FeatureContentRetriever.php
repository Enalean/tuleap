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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\SearchFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FeatureContentRetriever
{
    public function __construct(
        private VerifyIsProgramIncrement $program_increment_verifier,
        private SearchFeatures $features_searcher,
        private VerifyFeatureIsVisible $feature_verifier,
        private FeatureRepresentationBuilder $feature_representation_builder,
        private VerifyIsVisibleArtifact $visibility_verifier,
    ) {
    }

    /**
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
            fn(FeatureIdentifier $feature) => $this->feature_representation_builder->buildFeatureRepresentation(
                $feature,
                $user
            ),
            $feature_identifiers
        );
    }
}
