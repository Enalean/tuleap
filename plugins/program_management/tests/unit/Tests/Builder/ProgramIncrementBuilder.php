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
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Builder;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTimeframeValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUriStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;

final class ProgramIncrementBuilder
{
    public static function buildWithId(int $program_increment_id): ProgramIncrement
    {
        $user_identifier = UserIdentifierStub::withId(666);
        $increment       = ProgramIncrement::build(
            RetrieveStatusValueUserCanSeeStub::withValue('On going'),
            RetrieveTitleValueUserCanSeeStub::withValue('Increment 1'),
            RetrieveTimeframeValueUserCanSeeStub::withValues(1632812856, 1635412056),
            RetrieveUriStub::withDefault(),
            RetrieveCrossRefStub::withDefault(),
            VerifyUserCanUpdateTimeboxStub::withAllowed(),
            UserCanPlanInProgramIncrementVerifierBuilder::buildWithAllowed(),
            $user_identifier,
            ProgramIncrementIdentifierBuilder::buildWithIdAndUser($program_increment_id, $user_identifier)
        );
        assert($increment !== null);
        return $increment;
    }
}
