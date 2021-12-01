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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchPlannableFeatures;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\REST\v1\FeatureRepresentation;

final class FeatureElementsRetriever
{
    public function __construct(
        private BuildProgram $build_program,
        private SearchPlannableFeatures $search_plannable_features,
        private FeatureRepresentationBuilder $feature_representation_builder,
    ) {
    }

    /**
     * @return FeatureRepresentation[]
     *
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException
     * @throws \Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException
     */
    public function retrieveFeaturesToBePlanned(int $program_id, UserIdentifier $user): array
    {
        $program = ProgramIdentifier::fromId($this->build_program, $program_id, $user, null);

        $to_be_planned_artifacts = $this->search_plannable_features->searchPlannableFeatures($program);

        $elements = [];
        foreach ($to_be_planned_artifacts as $artifact) {
            $feature = $this->feature_representation_builder->buildFeatureRepresentation(
                $user,
                $program,
                $artifact['artifact_id'],
                $artifact['field_title_id'],
                $artifact['artifact_title']
            );
            if ($feature) {
                $elements[] = $feature;
            }
        }

        return $elements;
    }
}
