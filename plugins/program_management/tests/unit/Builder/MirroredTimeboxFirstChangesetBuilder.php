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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\GatherSynchronizedFieldsStub;
use Tuleap\ProgramManagement\Tests\Stub\MapStatusByValueStub;
use Tuleap\ProgramManagement\Tests\Stub\SynchronizedFieldsStubPreparation;

final class MirroredTimeboxFirstChangesetBuilder
{
    public static function buildWithValues(
        TrackerIdentifier $mirrored_timebox_tracker,
        int $mapped_status_bind_value_id,
        SynchronizedFieldsStubPreparation $fields,
        SourceTimeboxChangesetValues $source_values,
        ArtifactLinkValue $artifact_link_value,
        UserIdentifier $user
    ): MirroredTimeboxFirstChangeset {
        return MirroredTimeboxFirstChangeset::fromMirroredTimeboxTracker(
            GatherSynchronizedFieldsStub::withFieldsPreparations($fields),
            MapStatusByValueStub::withValues($mapped_status_bind_value_id),
            $mirrored_timebox_tracker,
            $source_values,
            $artifact_link_value,
            $user
        );
    }
}
