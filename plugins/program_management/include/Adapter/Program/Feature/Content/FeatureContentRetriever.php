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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class FeatureContentRetriever
{
    public function __construct(
        private VerifyIsProgramIncrement $program_increment_verifier,
        private ContentStore $content_store,
        private FeatureRepresentationBuilder $feature_representation_builder,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveProgramOfProgramIncrement $program_retriever,
        private BuildProgram $program_builder,
    ) {
    }

    public function retrieveProgramIncrementContent(int $id, UserIdentifier $user): array
    {
        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $id,
            $user
        );
        $program           = ProgramIdentifier::fromProgramIncrement(
            $this->program_retriever,
            $this->program_builder,
            $program_increment,
            $user
        );
        $planned_content   = $this->content_store->searchContent($program_increment->getId());

        $elements = [];
        foreach ($planned_content as $artifact) {
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
