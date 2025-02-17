<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\include\CheckUserCanAccessProjectAndIsAdmin;
use Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamAccessException;
use Tuleap\ProgramManagement\Domain\Team\TeamMustHaveExplicitBacklogEnabledException;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;

final class TeamAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const TEAM_ID = 202;
    private const USER_ID = 192;
    private VerifyIsProgramStub $program_verifier;
    private ExplicitBacklogDao & Stub $explicit_backlog_dao;
    private RetrieveUserStub $retrieve_user;
    private \Project $team_project;
    private CheckUserCanAccessProjectAndIsAdmin $url_verification;

    protected function setUp(): void
    {
        $this->explicit_backlog_dao = $this->createStub(ExplicitBacklogDao::class);
        $this->program_verifier     = VerifyIsProgramStub::withNotValidProgram();
        $this->url_verification     = CheckUserCanAccessProjectStub::build();

        $this->team_project = ProjectTestBuilder::aProject()->withId(self::TEAM_ID)->build();

        $user = UserTestBuilder::aUser()
            ->withId(self::USER_ID)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($this->team_project)
            ->build();

        $this->retrieve_user    = RetrieveUserStub::withUser($user);
        $_SERVER['REQUEST_URI'] = '/';
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    private function check(): void
    {
        $adapter = new TeamAdapter(
            RetrieveFullProjectStub::withProject($this->team_project),
            $this->program_verifier,
            $this->explicit_backlog_dao,
            $this->retrieve_user,
            $this->url_verification,
        );
        $adapter->checkProjectIsATeam(self::TEAM_ID, UserIdentifierStub::withId(self::USER_ID));
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdmin(): void
    {
        $this->retrieve_user = RetrieveUserStub::withUser(
            UserTestBuilder::aUser()
                ->withId(self::USER_ID)
                ->withoutSiteAdministrator()
                ->withMemberOf($this->team_project)
                ->build()
        );

        $this->expectException(TeamAccessException::class);
        $this->check();
    }

    public function testItThrowExceptionWhenTeamProjectIsAlreadyAProgram(): void
    {
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withUserAdminOf(
            UserTestBuilder::buildWithId(self::USER_ID),
            $this->team_project,
        );
        $this->program_verifier = VerifyIsProgramStub::withValidProgram();
        $this->expectException(ProjectIsAProgramException::class);
        $this->check();
    }

    public function testThrowsExceptionWhenTeamProjectDoesNotHaveTheExplicitBacklogModeEnabled(): void
    {
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withUserAdminOf(
            UserTestBuilder::buildWithId(self::USER_ID),
            $this->team_project,
        );
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->expectException(TeamMustHaveExplicitBacklogEnabledException::class);
        $this->check();
    }

    public function testItChecksAProjectCanBecomeATeam(): void
    {
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withUserAdminOf(
            UserTestBuilder::buildWithId(self::USER_ID),
            $this->team_project,
        );
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(true);

        $this->expectNotToPerformAssertions();
        $this->check();
    }
}
