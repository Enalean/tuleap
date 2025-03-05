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

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TeamIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID  = 134;
    private const SECOND_TEAM_ID = 407;

    private function getCollectionFromProgram(): array
    {
        return TeamIdentifier::buildCollectionFromProgram(
            SearchVisibleTeamsOfProgramStub::withTeamIds(
                self::FIRST_TEAM_ID,
                self::SECOND_TEAM_ID
            ),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsCollectionFromProgram(): void
    {
        $teams = $this->getCollectionFromProgram();
        $ids   = array_map(static fn(TeamIdentifier $team) => $team->getId(), $teams);
        self::assertContains(self::FIRST_TEAM_ID, $ids);
        self::assertContains(self::SECOND_TEAM_ID, $ids);
    }

    public function testItBuildsTeamOfProgramById(): void
    {
        $team = TeamIdentifier::buildTeamOfProgramById(
            SearchVisibleTeamsOfProgramStub::withTeamIds(
                self::FIRST_TEAM_ID,
                self::SECOND_TEAM_ID
            ),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser(),
            self::FIRST_TEAM_ID
        );

        self::assertEquals(self::FIRST_TEAM_ID, $team->getId());
    }
}
