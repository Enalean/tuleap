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

namespace Tuleap\ProgramManagement\Domain\Team\Creation;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID     = 102;
    private const FIRST_TEAM_ID  = 140;
    private const SECOND_TEAM_ID = 119;
    private \PFUser $user;
    private ProgramForAdministrationIdentifier $program;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\Stub|BuildTeam
     */
    private $team_builder;

    protected function setUp(): void
    {
        $this->user         = UserTestBuilder::aUser()->withId(101)->build();
        $this->program      = ProgramForAdministrationIdentifierBuilder::buildWithId(self::PROGRAM_ID);
        $this->team_builder = $this->createStub(BuildTeam::class);
    }

    public function testItBuildsFromProgramAndTeams(): void
    {
        $this->team_builder->method('checkProjectIsATeam');
        $teams = array_map(
            fn(int $team_id): Team => Team::build($this->team_builder, $team_id, $this->user),
            [self::FIRST_TEAM_ID, self::SECOND_TEAM_ID]
        );

        $collection = TeamCollection::fromProgramAndTeams($this->program, ...$teams);
        $team_ids   = $collection->getTeamIds();
        self::assertContains(self::FIRST_TEAM_ID, $team_ids);
        self::assertContains(self::SECOND_TEAM_ID, $team_ids);
        self::assertSame(self::PROGRAM_ID, $collection->getProgram()->id);
    }
}
