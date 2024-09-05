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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;

final class TeamProjectsCollectionBuilder
{
    /**
     * @no-named-arguments
     */
    public static function withProjects(ProjectReference $first_team, ProjectReference ...$other_teams): TeamProjectsCollection
    {
        $all_teams = [$first_team, ...$other_teams];
        $team_ids  = array_map(static fn(ProjectReference $team) => $team->getId(), $all_teams);
        return TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::withTeamIds(...$team_ids),
            RetrieveProjectReferenceStub::withProjects(...$all_teams),
            ProgramIdentifierBuilder::build()
        );
    }

    public static function withEmptyTeams(): TeamProjectsCollection
    {
        return TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::withNoTeams(),
            RetrieveProjectReferenceStub::withNoProjects(),
            ProgramIdentifierBuilder::build()
        );
    }
}
