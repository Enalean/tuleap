<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactUpdatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationTrackerStub;

final class IterationUpdateBuilder
{
    public static function build(): IterationUpdate
    {
        return self::buildWithIds(141, 334, 20, 7516);
    }

    public static function buildWithIds(
        int $user_id,
        int $iteration_id,
        int $tracker_id,
        int $changeset_id
    ): IterationUpdate {
        $event            = ArtifactUpdatedEventStub::withIds($iteration_id, $tracker_id, $user_id, $changeset_id);
        $iteration_update = IterationUpdate::fromArtifactUpdateEvent(
            VerifyIsIterationTrackerStub::buildValidIteration(),
            $event
        );

        assert($iteration_update !== null);

        return $iteration_update;
    }
}
