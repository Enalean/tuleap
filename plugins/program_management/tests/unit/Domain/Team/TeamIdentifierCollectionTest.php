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

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TeamIdentifierCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID  = 120;
    private const SECOND_TEAM_ID = 178;

    private ProgramIdentifier $program;
    private UserIdentifier $user;
    private SearchVisibleTeamsOfProgramStub $teams_searcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->program = ProgramIdentifierBuilder::build();
        $this->user    = UserIdentifierStub::buildGenericUser();

        $this->teams_searcher = SearchVisibleTeamsOfProgramStub::withTeamIds(self::FIRST_TEAM_ID, self::SECOND_TEAM_ID);
    }

    public function testItBuildsATeamIdentifierCollection(): void
    {
        $collection = TeamIdentifierCollection::fromProgram($this->teams_searcher, $this->program, $this->user);
        self::assertContains(self::FIRST_TEAM_ID, $collection->getArrayOfTeamsId());
        self::assertContains(self::SECOND_TEAM_ID, $collection->getArrayOfTeamsId());
    }
}
