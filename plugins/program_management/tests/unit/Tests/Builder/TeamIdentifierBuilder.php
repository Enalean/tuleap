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

use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

final class TeamIdentifierBuilder
{
    public static function build(): TeamIdentifier
    {
        return self::buildWithId(116);
    }

    public static function buildWithId(int $team_id): TeamIdentifier
    {
        [$first_team_identifier] = TeamIdentifier::buildCollectionFromProgram(
            SearchVisibleTeamsOfProgramStub::withTeamIds($team_id),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
        return $first_team_identifier;
    }
}
