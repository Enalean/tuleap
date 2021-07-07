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
use Tuleap\ProgramManagement\Domain\Program\ToBeCreatedProgram;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class TeamCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BuildProgramStub $program_adapter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|BuildTeam
     */
    private $team_adapter;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TeamStore
     */
    private $team_dao;
    private int $project_id;
    private int $team_project_id;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->program_adapter = BuildProgramStub::stubValidToBeCreatedProgram();
        $this->team_adapter    = $this->createMock(BuildTeam::class);
        $this->team_dao        = $this->createMock(TeamStore::class);

        $this->project_id      = 101;
        $this->team_project_id = 2;
        $this->user            = UserTestBuilder::aUser()->build();
    }

    public function testItCreatesAPlan(): void
    {
        $this->team_adapter->expects(self::once())->method('checkProjectIsATeam');

        $program    = ToBeCreatedProgram::fromId($this->program_adapter, $this->project_id, $this->user);
        $collection = new TeamCollection([Team::build($this->team_adapter, $this->team_project_id, $this->user)], $program);
        $this->team_adapter
            ->expects(self::once())
            ->method('buildTeamProject')
            ->willReturnCallback(
                function (array $team_ids, ToBeCreatedProgram $to_be_created_program, \PFUser $user) use ($program, $collection): TeamCollection {
                    if ($to_be_created_program->getId() !== $program->getId()) {
                        throw new \RuntimeException('program id #' . $program->getId() . ' is not same to ' . $to_be_created_program->getId());
                    }
                    return $collection;
                }
            );

        $this->team_dao->expects(self::once())->method('save')->with($collection);

        $team_adapter = new TeamCreator($this->program_adapter, $this->team_adapter, $this->team_dao);
        $team_adapter->create($this->user, $this->project_id, [$this->team_project_id]);
    }

    public function testThrowExceptionWhenTeamIdsContainProgram(): void
    {
        $this->expectException(ProgramIsTeamException::class);

        $team_adapter = new TeamCreator($this->program_adapter, $this->team_adapter, $this->team_dao);
        $team_adapter->create($this->user, $this->project_id, [$this->team_project_id, $this->project_id]);
    }
}
