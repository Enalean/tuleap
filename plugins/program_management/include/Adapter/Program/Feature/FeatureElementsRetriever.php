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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchPlannableFeatures;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\VerifyFeatureIsVisible;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;

final class FeatureElementsRetriever
{
    public function __construct(
        private BuildProgram $build_program,
        private SearchPlannableFeatures $search_plannable_features,
        private VerifyFeatureIsVisible $feature_verifier,
        private FeatureRepresentationBuilder $feature_representation_builder,
    ) {
    }

    /**
     * @return FeatureRepresentation[]
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
            fn(FeatureIdentifier $feature) => $this->feature_representation_builder->buildFeatureRepresentation(
                $feature,
                $user
            ),
            $feature_identifiers
        );
    }
}
