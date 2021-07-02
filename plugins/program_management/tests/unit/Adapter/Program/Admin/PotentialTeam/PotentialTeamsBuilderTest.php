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

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\SearchTeamsOfProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class PotentialTeamsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\ProjectManager
     */
    private $project_manager;
    private SearchTeamsOfProgramStub $teams_of_program_searcher;
    private ProgramIdentifier $program;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->project_manager           = $this->createStub(\ProjectManager::class);
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::buildTeams(123);
        $this->user                      = UserTestBuilder::aUser()->build();
        $this->program                   = ProgramIdentifier::fromId(
            BuildProgramStub::stubValidProgram(),
            100,
            $this->user
        );
    }

    public function testBuildEmptyTeamsIfNoAggregatedTeamsAndNoProjectUserIsAdminOf(): void
    {
        $this->teams_of_program_searcher = SearchTeamsOfProgramStub::buildTeams();
        $this->project_manager->method('getProjectsUserIsAdmin')->willReturn([]);

        self::assertEmpty($this->getBuilder()->buildPotentialTeams($this->program, $this->user));
    }

    public function testBuildEmptyIfAggregatedTeamsEqualsProjectUserIsAdminOf(): void
    {
        $this->project_manager->method('getProjectsUserIsAdmin')->willReturn([new \Project(['group_id' => 123])]);

        self::assertEmpty($this->getBuilder()->buildPotentialTeams($this->program, $this->user));
    }

    public function testBuildPotentialTeamWhenUserIsAdminOfProjectThatNotAggregatedTeam(): void
    {
        $this->project_manager
            ->method('getProjectsUserIsAdmin')
            ->willReturn([
                new \Project(['group_id' => 123]),
                new \Project(['group_id' => 124, 'group_name' => 'potential_team'])
            ]);

        $potential_teams = $this->getBuilder()->buildPotentialTeams($this->program, $this->user);
        self::assertCount(1, $potential_teams);
        self::assertSame(124, $potential_teams[0]->id);
        self::assertSame('potential_team', $potential_teams[0]->public_name);
    }

    private function getBuilder(): PotentialTeamsBuilder
    {
        return new PotentialTeamsBuilder(
            $this->project_manager,
            $this->teams_of_program_searcher
        );
    }
}
