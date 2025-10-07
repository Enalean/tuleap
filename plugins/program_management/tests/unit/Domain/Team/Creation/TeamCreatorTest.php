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

namespace Tuleap\ProgramManagement\Domain\Team\Creation;

use Tuleap\ProgramManagement\Domain\Program\ProgramIsTeamException;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Stub\BuildTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\Program\ProjectHistory\SaveTeamUpdateInProjectHistoryStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TeamStoreStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyProjectPermissionStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TeamCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int PROGRAM_ID = 101;
    private const int TEAM_ID    = 102;
    private RetrieveProjectStub $project_retriever;
    private VerifyIsTeamStub $team_verifier;
    private VerifyProjectPermissionStub $permission_verifier;
    private BuildTeam $team_builder;
    private TeamStoreStub $team_store;
    private UserReference $user_identifier;
    private SaveTeamUpdateInProjectHistoryStub $log_history_saver;
    private SearchTeamsOfProgramStub $search_teams_of_program;
    private RetrieveProjectReferenceStub $retrieve_project_reference;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_retriever          = RetrieveProjectStub::withValidProjects(ProjectIdentifierStub::buildWithId(self::PROGRAM_ID));
        $this->team_verifier              = VerifyIsTeamStub::withNotValidTeam();
        $this->permission_verifier        = VerifyProjectPermissionStub::withAdministrator();
        $this->team_builder               = BuildTeamStub::withValidTeam();
        $this->team_store                 = TeamStoreStub::withCount();
        $this->user_identifier            = UserReferenceStub::withDefaults();
        $this->log_history_saver          = SaveTeamUpdateInProjectHistoryStub::withCount();
        $this->search_teams_of_program    = SearchTeamsOfProgramStub::withTeamIds(109);
        $this->retrieve_project_reference = RetrieveProjectReferenceStub::withProjects(ProjectReferenceStub::withId(self::PROGRAM_ID));
    }

    private function getCreator(): TeamCreator
    {
        return new TeamCreator(
            $this->project_retriever,
            $this->team_verifier,
            $this->permission_verifier,
            $this->team_builder,
            $this->team_store,
            $this->log_history_saver,
            $this->search_teams_of_program,
            $this->retrieve_project_reference
        );
    }

    public function testItCreatesAPlanAndSaveAnEntryInProjectHistory(): void
    {
        $this->getCreator()->create($this->user_identifier, self::PROGRAM_ID, [self::TEAM_ID]);
        self::assertEquals(1, $this->team_store->getCallCount());
        self::assertEquals(1, $this->log_history_saver->getCallCount());
    }

    public function testThrowExceptionWhenTeamIdsContainProgram(): void
    {
        $this->expectException(ProgramIsTeamException::class);

        $this->getCreator()->create($this->user_identifier, self::PROGRAM_ID, [self::TEAM_ID, self::PROGRAM_ID]);
        self::assertEquals(0, $this->team_store->getCallCount());
    }
}
