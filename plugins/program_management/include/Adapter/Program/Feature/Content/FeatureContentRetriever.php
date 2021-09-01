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
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\RetrieveFeatureContent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\ProgramSearcher;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;

final class FeatureContentRetriever implements RetrieveFeatureContent
{
    private ContentStore $content_store;
    private VerifyIsProgramIncrement $program_increment_verifier;
    private FeatureRepresentationBuilder $feature_representation_builder;
    private ProgramSearcher $program_searcher;
    private VerifyIsVisibleArtifact $visibility_verifier;

    public function __construct(
        VerifyIsProgramIncrement $program_increment_verifier,
        ContentStore $content_store,
        FeatureRepresentationBuilder $feature_representation_builder,
        ProgramSearcher $program_searcher,
        VerifyIsVisibleArtifact $visibility_verifier
    ) {
        $this->content_store                  = $content_store;
        $this->program_increment_verifier     = $program_increment_verifier;
        $this->feature_representation_builder = $feature_representation_builder;
        $this->program_searcher               = $program_searcher;
        $this->visibility_verifier            = $visibility_verifier;
    }

    public function retrieveProgramIncrementContent(int $id, \PFUser $user): array
    {
        $user_identifier   = UserProxy::buildFromPFUser($user);
        $program_increment = ProgramIncrementIdentifier::fromId(
            $this->program_increment_verifier,
            $this->visibility_verifier,
            $id,
            $user_identifier
        );
        $program           = $this->program_searcher->getProgramOfProgramIncrement($id, $user_identifier);
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
