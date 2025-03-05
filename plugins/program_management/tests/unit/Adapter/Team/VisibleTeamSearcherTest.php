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

namespace Tuleap\ProgramManagement\Adapter\Team;

use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Team\ProgramHasNoTeamException;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotAggregatedByProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamIsNotVisibleException;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeamOfProgram;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamOfProgramStub;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VisibleTeamSearcherTest extends TestCase
{
    use GlobalLanguageMock;

    private const FIRST_TEAM_ID  = 184;
    private const SECOND_TEAM_ID = 101;
    private const USER_ID        = 745;
    private SearchTeamsOfProgramStub $teams_searcher;
    private CheckProjectAccess $access_checker;
    private ProgramIdentifier $program;
    private UserIdentifierStub $user;
    private VerifyIsTeamOfProgram $verify_is_team_of_program;

    protected function setUp(): void
    {
        $this->teams_searcher            = SearchTeamsOfProgramStub::withTeamIds(self::FIRST_TEAM_ID, self::SECOND_TEAM_ID);
        $this->access_checker            = CheckProjectAccessStub::withValidAccess();
        $this->program                   = ProgramIdentifierBuilder::build();
        $this->user                      = UserIdentifierStub::withId(self::USER_ID);
        $this->verify_is_team_of_program = VerifyIsTeamOfProgramStub::withTeamAggregatedByProgram();
    }

    private function getSearcher(): VisibleTeamSearcher
    {
        return new VisibleTeamSearcher(
            $this->teams_searcher,
            RetrieveUserStub::withUser(UserTestBuilder::buildWithId(self::USER_ID)),
            RetrieveFullProjectStub::withProjects(
                ProjectTestBuilder::aProject()->withId(self::FIRST_TEAM_ID)->build(),
                ProjectTestBuilder::aProject()->withId(self::SECOND_TEAM_ID)->build(),
            ),
            $this->access_checker,
            $this->verify_is_team_of_program
        );
    }

    public function testSearchTeamIdsOfProgram(): void
    {
        $team_ids = $this->getSearcher()->searchTeamIdsOfProgram($this->program, $this->user);
        self::assertContains(self::FIRST_TEAM_ID, $team_ids);
        self::assertContains(self::SECOND_TEAM_ID, $team_ids);
    }

    public function testItReturnsErrorWhenNoTeamsFoundForProgram(): void
    {
        $this->teams_searcher = SearchTeamsOfProgramStub::withNoTeams();
        $this->expectException(ProgramHasNoTeamException::class);
        $this->getSearcher()->searchTeamIdsOfProgram($this->program, $this->user);
    }

    public static function dataProviderAccessExceptions(): array
    {
        return [
            'with invalid project'                           => [CheckProjectAccessStub::withNotValidProject()],
            'with suspended project'                         => [CheckProjectAccessStub::withSuspendedProject()],
            'with deleted project'                           => [CheckProjectAccessStub::withDeletedProject()],
            'with user restricted without access to project' => [CheckProjectAccessStub::withRestrictedUserWithoutAccess()],
            'with private project and user not member'       => [CheckProjectAccessStub::withPrivateProjectWithoutAccess()],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderAccessExceptions')]
    public function testItReturnsErrorWhenUserCannotSeeOneOfTheTeams(CheckProjectAccess $access_checker): void
    {
        $this->access_checker = $access_checker;
        $this->expectException(TeamIsNotVisibleException::class);
        $this->getSearcher()->searchTeamIdsOfProgram($this->program, $this->user);
    }

    public function testItSearchTeamWithIdInProgram(): void
    {
        $team_id = $this->getSearcher()->searchTeamWithIdInProgram($this->program, $this->user, self::FIRST_TEAM_ID);
        self::assertEquals($team_id, self::FIRST_TEAM_ID);
    }

    public function testItThrowsWhenTeamIsNotAggregatedByProgram(): void
    {
        $this->verify_is_team_of_program = VerifyIsTeamOfProgramStub::withTeamNotAggregatedByProgram();
        $this->expectException(TeamIsNotAggregatedByProgramException::class);
        $this->getSearcher()->searchTeamWithIdInProgram($this->program, $this->user, self::FIRST_TEAM_ID);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderAccessExceptions')]
    public function testItThrowsWhenUserCannotSeeTheTeam(CheckProjectAccess $access_checker): void
    {
        $this->access_checker = $access_checker;
        $this->expectException(TeamIsNotVisibleException::class);
        $this->getSearcher()->searchTeamWithIdInProgram($this->program, $this->user, self::FIRST_TEAM_ID);
    }
}
