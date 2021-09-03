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

use Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation\PendingIterationCreationProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsUserStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

final class IterationCreationBuilder
{
    public static function buildWithIds(
        int $iteration_id,
        int $program_increment_id,
        int $user_id,
        int $changeset_id
    ): IterationCreation {
        $pending_creation = new PendingIterationCreationProxy(
            $iteration_id,
            $program_increment_id,
            $user_id,
            $changeset_id
        );
        return IterationCreation::fromPendingIterationCreation(
            VerifyIsUserStub::withValidUser(),
            VerifyIsIterationStub::withValidIteration(),
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsChangesetStub::withValidChangeset(),
            $pending_creation
        );
    }
}
