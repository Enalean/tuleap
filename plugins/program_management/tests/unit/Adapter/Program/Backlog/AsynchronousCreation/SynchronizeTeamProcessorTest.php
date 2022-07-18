<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Adapter\Workspace\MessageLog;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\MissingProgramIncrementCreator;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirrorTimeboxesFromProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchOpenProgramIncrementsStub;
use Tuleap\ProgramManagement\Tests\Stub\TeamSynchronizationEventStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class SynchronizeTeamProcessorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID = 1;

    public function testItHandlesTeamSynchronizationEvents(): void
    {
        $logger       = new TestLogger();
        $event        = TeamSynchronizationEventStub::buildWithIds(self::PROGRAM_ID, 123, 456);
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserById')->willReturn(UserTestBuilder::buildWithDefaults());
        $project_manager = $this->createMock(\ProjectManager::class);
        $project_manager->method('getProject')->willReturn(new \Project(['group_id' => self::PROGRAM_ID, 'group_name' => "project", "unix_group_name" => "project", "icon_codepoint" => ""]));
        (new SynchronizeTeamProcessor(
            MessageLog::buildFromLogger($logger),
            $project_manager,
            $user_manager,
            new MissingProgramIncrementCreator(
                SearchOpenProgramIncrementsStub::withProgramIncrements(ProgramIncrementBuilder::buildWithId(self::PROGRAM_ID)),
                SearchMirrorTimeboxesFromProgramStub::buildWithoutMissingMirror()
            )
        ))->processTeamSynchronization($event);

        self::assertTrue($logger->hasDebugThatContains("Team 123 of Program 1 needs PI and Iterations synchronization"));
    }
}
