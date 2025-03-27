<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite;

use Git_SystemEventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Project_NotFoundException;
use ProjectManager;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RegenerateConfigurationCommandTest extends TestCase
{
    private ProjectManager&MockObject $project_manager;
    private Git_SystemEventManager&MockObject $event_manager;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->event_manager   = $this->createMock(Git_SystemEventManager::class);
    }

    public function testConfigurationForAllProjectsCanBeRegenerated(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $project_1 = ProjectTestBuilder::aProject()->withId(999)->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(888)->build();
        $this->project_manager->method('getProjectsByStatus')->with(Project::STATUS_ACTIVE)->willReturn([$project_1, $project_2]);
        $this->event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with(['999', '888']);

        $command_tester->execute(['--all' => true, 'project_ids' => ['102', '103']]);
        self::assertSame(0, $command_tester->getStatusCode());
    }

    public function testConfigurationForSomeProjectsCanBeRegenerated(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $project_1 = ProjectTestBuilder::aProject()->withId(102)->withStatusActive()->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(103)->withStatusActive()->build();
        $project_3 = ProjectTestBuilder::aProject()->withId(104)->withStatusSuspended()->build();
        $this->project_manager->method('getValidProject')
            ->willReturnCallback(static fn($id) => match ((int) $id) {
                102 => $project_1,
                103 => $project_2,
                104 => $project_3,
            });
        $this->event_manager->expects($this->once())->method('queueProjectsConfigurationUpdate')->with(['102', '103']);

        $command_tester->execute(['project_ids' => ['102', '103']]);
        self::assertSame(0, $command_tester->getStatusCode());
    }

    public function testInvalidProjectIDIsRejected(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $this->project_manager->method('getValidProject')->willThrowException(new Project_NotFoundException());
        $this->event_manager->expects(self::never())->method('queueProjectsConfigurationUpdate');

        $command_tester->execute(['project_ids' => ['999999999999999999', '103']]);
        self::assertSame(1, $command_tester->getStatusCode());
    }

    public function testNoUnnecessaryWorkIsDoneWhenNoProjectIDIsProvided(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $this->event_manager->expects(self::never())->method('queueProjectsConfigurationUpdate');

        $command_tester->execute(['project_ids' => []]);
        self::assertSame(0, $command_tester->getStatusCode());
    }
}
