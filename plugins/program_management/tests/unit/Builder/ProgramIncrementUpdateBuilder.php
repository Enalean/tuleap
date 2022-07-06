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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

final class ProgramIncrementUpdateBuilder
{
    public static function build(): ProgramIncrementUpdate
    {
        return self::buildWithIds(141, 334, 20, 7516, 7515);
    }

    public static function buildWithIds(
        int $user_id,
        int $program_increment_id,
        int $tracker_id,
        int $changeset_id,
        int $old_changeset_id,
    ): ProgramIncrementUpdate {
        $event                    = ArtifactUpdatedEventStub::withIds($program_increment_id, $tracker_id, $user_id, $changeset_id, $old_changeset_id);
        $program_increment_update = ProgramIncrementUpdate::fromArtifactUpdatedEvent(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            $event
        );

        if (! $program_increment_update) {
            throw new \LogicException("ProgramIncrementUpdate have not been created");
        }

        return $program_increment_update;
    }
}
