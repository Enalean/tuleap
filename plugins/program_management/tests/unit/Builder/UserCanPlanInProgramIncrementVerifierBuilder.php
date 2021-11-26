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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\UserCanPlanInProgramIncrementVerifier;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanLinkToProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;

final class UserCanPlanInProgramIncrementVerifierBuilder
{
    public static function buildWithAllowed(): UserCanPlanInProgramIncrementVerifier
    {
        return new UserCanPlanInProgramIncrementVerifier(
            VerifyUserCanUpdateTimeboxStub::withAllowed(),
            RetrieveProgramIncrementTrackerStub::withValidTracker(16),
            VerifyUserCanLinkToProgramIncrementStub::withAllowed(),
            RetrieveProgramOfProgramIncrementStub::withProgram(182),
            BuildProgramStub::stubValidProgram(),
            SearchVisibleTeamsOfProgramStub::withTeamIds(174, 153)
        );
    }
}
