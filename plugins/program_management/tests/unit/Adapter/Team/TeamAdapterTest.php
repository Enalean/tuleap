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

use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\Team\ProjectIsAProgramException;
use Tuleap\ProgramManagement\Domain\Team\TeamAccessException;
use Tuleap\ProgramManagement\Domain\Team\TeamMustHaveExplicitBacklogEnabledException;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramStub;

final class TeamAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const TEAM_ID = 202;
    private VerifyIsProgram $program_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ExplicitBacklogDao
     */
    private $explicit_backlog_dao;
    private RetrieveFullProject $retrieve_full_project;
    private \Project $team_project;
    private UserIdentifierStub $user_identifier;
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        $this->explicit_backlog_dao = $this->createStub(ExplicitBacklogDao::class);
        $this->program_verifier     = VerifyIsProgramStub::withValidProgram();
        $this->user                 = $this->createMock(\PFUser::class);
        $this->user_identifier      = UserIdentifierStub::buildGenericUser();

        $this->team_project          = new \Project(['group_id' => self::TEAM_ID, 'status' => 'A', 'access' => 'public', 'unix_group_name' => 'a_project', 'group_name' => 'A project', 'icon_codepoint' => '']);
        $this->retrieve_full_project = RetrieveFullProjectStub::withProject($this->team_project);

        $_SERVER['REQUEST_URI'] = '/';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    private function getAdapter(RetrieveUser $retrieve_user): TeamAdapter
    {
        return new TeamAdapter($this->retrieve_full_project, $this->program_verifier, $this->explicit_backlog_dao, $retrieve_user);
    }

    public function testItThrowsErrorWhenUserIsNotProjectAdmin(): void
    {
        $this->user->method('isMember')->with(self::TEAM_ID)->willReturn(false);
        $this->user->method('isAnonymous')->willReturn(false);
        $this->user->method('isSuperUser')->willReturn(false);
        $this->user->method('isRestricted')->willReturn(false);

        $this->expectException(TeamAccessException::class);
        $this->getAdapter(RetrieveUserStub::buildMockedRegularUser($this->user))
            ->checkProjectIsATeam(self::TEAM_ID, $this->user_identifier);
    }

    public function testItThrowExceptionWhenTeamProjectIsAlreadyAProgram(): void
    {
        $this->expectException(ProjectIsAProgramException::class);
        $this->getAdapter(RetrieveUserStub::buildUserWhoCanAccessProjectAndIsProjectAdmin($this->user))
            ->checkProjectIsATeam(self::TEAM_ID, $this->user_identifier);
    }

    public function testThrowsExceptionWhenTeamProjectDoesNotHaveTheExplicitBacklogModeEnabled(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(false);

        $this->expectException(TeamMustHaveExplicitBacklogEnabledException::class);
        $this->getAdapter(RetrieveUserStub::buildUserWhoCanAccessProjectAndIsProjectAdmin($this->user))
            ->checkProjectIsATeam(self::TEAM_ID, $this->user_identifier);
    }

    public function testItChecksAProjectCanBecomeATeam(): void
    {
        $this->program_verifier = VerifyIsProgramStub::withNotValidProgram();
        $this->explicit_backlog_dao->method('isProjectUsingExplicitBacklog')->willReturn(true);

        $this->getAdapter(RetrieveUserStub::buildUserWhoCanAccessProjectAndIsProjectAdmin($this->user))
            ->checkProjectIsATeam(self::TEAM_ID, $this->user_identifier);

        $this->expectNotToPerformAssertions();
    }
}
