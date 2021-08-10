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

use ProjectManager;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamAccessException;
use Tuleap\ProgramManagement\Domain\Team\TeamMustHaveExplicitBacklogEnabledException;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class TeamAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const TEAM_ID = 202;
    private VerifyIsProgram $program_verifier;
    private ProgramForAdministrationIdentifier $program;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\Stub|ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    /**
     * @var mixed|\PFUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;
    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|ProjectManager
     */
    private $project_manager;
    private \Project $team_project;

    protected function setUp(): void
    {
        $this->project_manager      = $this->createMock(ProjectManager::class);
        $this->explicit_backlog_dao = $this->createStub(ExplicitBacklogDao::class);
        $this->program_verifier     = VerifyIsProgramStub::withValidProgram();
        $this->user                 = $this->createMock(\PFUser::class);
        $this->program              = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            $this->user,
            ProjectTestBuilder::aProject()->withId(101)->build()
        );

        $this->team_project = new \Project(['group_id' => self::TEAM_ID, 'status' => 'A', 'access' => 'public']);
        $this->project_manager->expects(self::once())
            ->method('getProject')
            ->with(self::TEAM_ID)
            ->willReturn($this->team_project);

        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    private function getAdapter(): TeamAdapter
    {
        return new TeamAdapter($this->project_manager, $this->program_verifier, $this->explicit_backlog_dao);
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdmin(): void
    {
        $this->user->method('isAdmin')->with(self::TEAM_ID)->willReturn(false);
        $this->user->method('isMember')->with(self::TEAM_ID)->willReturn(false);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isRestricted')->willReturn(false);

        $this->expectException(TeamAccessException::class);
        $this->getAdapter()->checkProjectIsATeam(self::TEAM_ID, $this->user);
    }

    public function testItThrowExceptionWhenTeamProjectIsAlreadyAProgram(): void
    {
        $this->user->method('isAdmin')->with(self::TEAM_ID)->willReturn(true);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(true);

        $this->expectException(ProjectIsAProgramException::class);
        $this->getAdapter()->checkProjectIsATeam(self::TEAM_ID, $this->user);
    }

    public function testThrowsExceptionWhenTeamProjectDoesNotHaveTheExplicitBacklogModeEnabled(): void
    {
        $this->user->method('isAdmin')->willReturn(true);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(true);

        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->expectException(TeamMustHaveExplicitBacklogEnabledException::class);
        $this->getAdapter()->checkProjectIsATeam(self::TEAM_ID, $this->user);
    }

    public function testItChecksAProjectCanBecomeATeam(): void
    {
        $this->user->method('isAdmin')->with(self::TEAM_ID)->willReturn(true);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(true);

        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(true);

        $this->getAdapter()->checkProjectIsATeam(self::TEAM_ID, $this->user);
    }
}
