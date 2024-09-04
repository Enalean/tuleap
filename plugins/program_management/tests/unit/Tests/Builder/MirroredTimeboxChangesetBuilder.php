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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTrackerOfArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;

final class MirroredTimeboxChangesetBuilder
{
    public static function buildWithValues(
        int $mirrored_timebox_id,
        int $mapped_status_bind_value_id,
        SynchronizedFieldsStubPreparation $fields,
        SourceTimeboxChangesetValues $source_values,
        UserIdentifier $user,
    ): MirroredTimeboxChangeset {
        return MirroredTimeboxChangeset::fromMirroredTimebox(
            RetrieveTrackerOfArtifactStub::withIds(1),
            GatherSynchronizedFieldsStub::withFieldsPreparations($fields),
            MapStatusByValueStub::withSuccessiveBindValueIds($mapped_status_bind_value_id),
            MirroredProgramIncrementIdentifierBuilder::buildWithId($mirrored_timebox_id),
            $source_values,
            $user
        );
    }
}
