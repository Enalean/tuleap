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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Content;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Artifact\RetrieveFullArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\Content\UserStoryPlanException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\AddFeature;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\AddFeatureException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Content\FeatureAddition;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\Artifact\ArtifactNotFoundException;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

final class FeatureAdditionProcessor implements AddFeature
{
    public function __construct(
        private RetrieveFullArtifact $artifact_retriever,
        private ArtifactLinkUpdater $artifact_link_updater,
        private RetrieveUser $retrieve_user,
    ) {
    }

    #[\Override]
    public function add(FeatureAddition $feature_addition): void
    {
        $program_increment_id = $feature_addition->program_increment->getId();
        try {
            $program_increment_artifact = $this->artifact_retriever->getNonNullArtifact(
                $feature_addition->program_increment
            );
        } catch (ArtifactNotFoundException $e) {
            throw new ProgramIncrementNotFoundException($program_increment_id);
        }
        try {
            $this->artifact_link_updater->updateArtifactLinks(
                $this->retrieve_user->getUserWithId($feature_addition->user),
                $program_increment_artifact,
                [$feature_addition->feature->id],
                [],
                \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::DEFAULT_LINK_TYPE
            );
        } catch (
            \Tracker_NoArtifactLinkFieldException
            | \Tracker_Exception
            | UserStoryPlanException $e // It can be thrown by ArtifactUpdatedHandler
        ) {
            throw new AddFeatureException(
                $feature_addition->feature->id,
                $program_increment_id,
                $e
            );
        }
    }
}
