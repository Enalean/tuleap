<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\AllProgramSearcherStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProjectsUserIsAdminStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PotentialTeamsCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TEAM    = 123;
    private const PROGRAM = 126;

    private SearchTeamsOfProgramStub $teams_of_program_searcher;
    private UserIdentifier $user_identifier;
    private AllProgramSearcherStub $all_program_searcher;
    private ProgramForAdministrationIdentifier $program;

    #[\Override]
    protected function setUp(): void
    {
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::withTeamIds(self::TEAM);

        $this->all_program_searcher = AllProgramSearcherStub::buildPrograms(self::PROGRAM);
        $this->user_identifier      = UserIdentifierStub::buildGenericUser();
        $this->program              = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testBuildEmptyTeamsIfNoAggregatedTeamsAndNoProjectUserIsAdminOf(): void
    {
        self::assertEmpty(
            PotentialTeamsCollection::buildPotentialTeams(
                SearchTeamsOfProgramStub::withNoTeams(),
                $this->all_program_searcher,
                SearchProjectsUserIsAdminStub::buildWithoutProject(),
                $this->program,
                $this->user_identifier
            )->getPotentialTeams()
        );
    }

    public function testBuildEmptyIfAggregatedTeamsEqualsProjectUserIsAdminOf(): void
    {
        self::assertEmpty(
            PotentialTeamsCollection::buildPotentialTeams(
                $this->teams_of_program_searcher,
                $this->all_program_searcher,
                SearchProjectsUserIsAdminStub::buildWithProjects(
                    ProjectReferenceStub::withValues(self::TEAM, 'project', 'project', '')
                ),
                $this->program,
                $this->user_identifier
            )->getPotentialTeams()
        );
    }

    public function testBuildPotentialTeamWhenUserIsAdminOfPotentialTeamThatNotAggregatedTeamAndPotentialTeamIsNotProgram(): void
    {
        $program_project = ProjectReferenceStub::withValues(self::PROGRAM, 'a_project', 'a_project', '');

        $potential_teams = PotentialTeamsCollection::buildPotentialTeams(
            $this->teams_of_program_searcher,
            $this->all_program_searcher,
            SearchProjectsUserIsAdminStub::buildWithProjects(
                ProjectReferenceStub::withValues(self::TEAM, 'is_team', 'is_team', ''),
                ProjectReferenceStub::withValues(124, 'potential_team', 'potential_team', ''),
                $program_project,
            ),
            ProgramForAdministrationIdentifierBuilder::buildWithId(self::PROGRAM),
            $this->user_identifier
        );

        self::assertCount(1, $potential_teams->getPotentialTeams());
        self::assertSame(124, $potential_teams->getPotentialTeams()[0]->id);
        self::assertSame('potential_team', $potential_teams->getPotentialTeams()[0]->public_name);
    }
}
