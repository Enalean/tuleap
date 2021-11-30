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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\RetrieveArtifactLinkField;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold a change to the Artifact Link field value of a Mirrored Program Increment
 * @psalm-immutable
 */
final class ArtifactLinkChangeset
{
    private function __construct(
        public MirroredProgramIncrementIdentifier $mirrored_program_increment,
        public UserIdentifier $user,
        public ArtifactLinkFieldReference $artifact_link_field,
        public ArtifactLinkValue $artifact_link_value,
    ) {
    }

    /**
     * @throws NoArtifactLinkFieldException
     */
    public static function fromMirroredProgramIncrement(
        RetrieveTrackerOfArtifact $tracker_retriever,
        RetrieveArtifactLinkField $field_retriever,
        MirroredProgramIncrementIdentifier $mirrored_program_increment,
        UserIdentifier $user,
        ArtifactLinkValue $artifact_link_value,
    ): self {
        $mirrored_program_increment_tracker = $tracker_retriever->getTrackerOfArtifact($mirrored_program_increment);

        $field = $field_retriever->getArtifactLinkField($mirrored_program_increment_tracker, null);
        return new self(
            $mirrored_program_increment,
            $user,
            $field,
            $artifact_link_value
        );
    }
}
