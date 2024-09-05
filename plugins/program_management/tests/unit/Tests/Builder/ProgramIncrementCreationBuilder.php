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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactCreatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

final class ProgramIncrementCreationBuilder
{
    public static function buildWithProgramIncrementId(int $program_increment_id): ProgramIncrementCreation
    {
        return self::buildWithIds(162, $program_increment_id, 60, 2045);
    }

    public static function buildWithIds(
        int $user_id,
        int $program_increment_id,
        int $tracker_id,
        int $changeset_id,
    ): ProgramIncrementCreation {
        $event                      = ArtifactCreatedEventStub::withIds($program_increment_id, $tracker_id, $user_id, $changeset_id);
        $program_increment_creation = ProgramIncrementCreation::fromArtifactCreatedEvent(
            VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement(),
            $event
        );

        if (! $program_increment_creation) {
            throw new \LogicException('Program increment creation have not been created');
        }

        return $program_increment_creation;
    }
}
