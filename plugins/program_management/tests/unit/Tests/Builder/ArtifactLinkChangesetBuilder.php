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

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkType;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\ArtifactLinkChangeset;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactLinkFieldReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveArtifactLinkFieldStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;

final class ArtifactLinkChangesetBuilder
{
    public static function buildWithValues(
        int $mirrored_program_increment_id,
        int $artifact_link_field_id,
        ArtifactLinkType $link_type,
        int $linked_artifact_id,
        UserIdentifier $user,
    ): ArtifactLinkChangeset {
        return ArtifactLinkChangeset::fromMirroredProgramIncrement(
            RetrieveTrackerOfArtifactStub::withIds(28),
            RetrieveArtifactLinkFieldStub::withFields(ArtifactLinkFieldReferenceStub::withId($artifact_link_field_id)),
            MirroredProgramIncrementIdentifierBuilder::buildWithId($mirrored_program_increment_id),
            $user,
            ArtifactLinkValue::fromArtifactAndType(ArtifactIdentifierStub::withId($linked_artifact_id), $link_type)
        );
    }
}
