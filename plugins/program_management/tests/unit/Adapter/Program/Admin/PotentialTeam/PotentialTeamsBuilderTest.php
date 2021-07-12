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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam;

use Tuleap\ProgramManagement\Stub\AllProgramSearcherStub;
use Tuleap\ProgramManagement\Stub\SearchTeamsOfProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PotentialTeamsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;
    private SearchTeamsOfProgramStub $teams_of_program_searcher;
    private \PFUser $user;
    private AllProgramSearcherStub $all_program_searcher;

    protected function setUp(): void
    {
        $this->project_manager           = $this->createStub(\ProjectManager::class);
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::buildTeams(123);
        $this->all_program_searcher      = AllProgramSearcherStub::buildPrograms(126);
        $this->user                      = UserTestBuilder::aUser()->build();
    }

    public function testBuildEmptyTeamsIfNoAggregatedTeamsAndNoProjectUserIsAdminOf(): void
    {
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::buildTeams();
        $this->project_manager->method('getProjectsUserIsAdmin')->willReturn([]);

        self::assertEmpty($this->getBuilder()->buildPotentialTeams(101, $this->user));
    }

    public function testBuildEmptyIfAggregatedTeamsEqualsProjectUserIsAdminOf(): void
    {
        $this->project_manager->method('getProjectsUserIsAdmin')->willReturn([new \Project(['group_id' => 123])]);

        self::assertEmpty($this->getBuilder()->buildPotentialTeams(101, $this->user));
    }

    public function testBuildPotentialTeamWhenUserIsAdminOfPotentialTeamThatNotAggregatedTeamAndPotentialTeamIsNotProgram(): void
    {
        $this->project_manager
            ->method('getProjectsUserIsAdmin')
            ->willReturn([
                new \Project(['group_id' => '123', 'group_name' => 'is_team']),
                new \Project(['group_id' => '124', 'group_name' => 'potential_team']),
                new \Project(['group_id' => '125', 'group_name' => 'a_project']),
                new \Project(['group_id' => '126', 'group_name' => 'program']),
            ]);

        $potential_teams = $this->getBuilder()->buildPotentialTeams(125, $this->user);
        self::assertCount(1, $potential_teams);
        self::assertSame(124, $potential_teams[0]->id);
        self::assertSame('potential_team', $potential_teams[0]->public_name);
    }

    private function getBuilder(): PotentialTeamsBuilder
    {
        return new PotentialTeamsBuilder(
            $this->project_manager,
            $this->teams_of_program_searcher,
            $this->all_program_searcher
        );
    }
}
