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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationProcessor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreationBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TeamIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\PlanProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\PlanUserStoriesInMirroredProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchVisibleTeamsOfProgramStub;

final class ProgramIncrementCreationProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_TEAM_ID        = 102;
    private const SECOND_TEAM_ID       = 149;
    private const PROGRAM_INCREMENT_ID = 43;
    private const USER_ID              = 119;

    private PlanUserStoriesInMirroredProgramIncrementsStub $user_stories_planner;
    private TestLogger $logger;
    private ProgramIncrementCreation $creation;
    private PlanProgramIncrementsStub $plan_program_increment;

    protected function setUp(): void
    {
        $this->logger                 = new TestLogger();
        $this->user_stories_planner   = PlanUserStoriesInMirroredProgramIncrementsStub::withCount();
        $this->plan_program_increment = PlanProgramIncrementsStub::build();

        $this->creation = ProgramIncrementCreationBuilder::buildWithIds(
            self::USER_ID,
            self::PROGRAM_INCREMENT_ID,
            54,
            6053
        );
    }

    private function getProcessor(): ProgramIncrementCreationProcessor
    {
        return new ProgramIncrementCreationProcessor(
            MessageLog::buildFromLogger($this->logger),
            $this->user_stories_planner,
            SearchVisibleTeamsOfProgramStub::withTeamIds(self::FIRST_TEAM_ID, self::SECOND_TEAM_ID),
            RetrieveProgramOfProgramIncrementStub::withProgram(146),
            BuildProgramStub::stubValidProgram(),
            $this->plan_program_increment
        );
    }

    public function testItProcessesProgramIncrementCreation(): void
    {
        $this->getProcessor()->processCreation($this->creation);

        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment creation with program increment #%d for user #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID
                )
            )
        );
        self::assertSame(1, $this->user_stories_planner->getCallCount());
        self::assertSame(1, $this->plan_program_increment->getCallCount());
    }

    public function testItProcessesProgramIncrementCreationForOneTeam(): void
    {
        $this->getProcessor()->synchronizeProgramIncrementAndIterationsForTeam($this->creation, TeamIdentifierBuilder::buildWithId(self::FIRST_TEAM_ID));

        self::assertTrue(
            $this->logger->hasDebug(
                sprintf(
                    'Processing program increment creation with program increment #%d for user #%d for team #%d',
                    self::PROGRAM_INCREMENT_ID,
                    self::USER_ID,
                    self::FIRST_TEAM_ID
                )
            )
        );
        self::assertSame(1, $this->user_stories_planner->getCallCount());
        self::assertSame(1, $this->plan_program_increment->getCallCount());
    }
}
