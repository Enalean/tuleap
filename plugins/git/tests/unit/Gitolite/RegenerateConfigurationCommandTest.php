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

use PHPUnit\Framework\MockObject\Stub;
use Project_NotFoundException;
use ProjectManager;
use Symfony\Component\Console\Tester\CommandTester;
use Tuleap\Git\AsynchronousEvents\RefreshGitoliteProjectConfigurationTask;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RegenerateConfigurationCommandTest extends TestCase
{
    private ProjectManager&Stub $project_manager;
    private EnqueueTaskStub $enqueuer;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_manager = $this->createStub(ProjectManager::class);
        $this->enqueuer        = new EnqueueTaskStub();
    }

    public function testConfigurationForAllProjectsCanBeRegenerated(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->enqueuer);
        $command_tester = new CommandTester($command);

        $project_1 = ProjectTestBuilder::aProject()->withId(999)->build();
        $project_2 = ProjectTestBuilder::aProject()->withId(888)->build();
        $this->project_manager->method('getProjectsByStatus')->willReturn([$project_1, $project_2]);

        $command_tester->execute(['--all' => true, 'project_ids' => ['102', '103']]);
        self::assertSame(0, $command_tester->getStatusCode());

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(999), new RefreshGitoliteProjectConfigurationTask(888)], $this->enqueuer->queued_tasks);
    }

    public function testConfigurationForSomeProjectsCanBeRegenerated(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->enqueuer);
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

        $command_tester->execute(['project_ids' => ['102', '103']]);
        self::assertSame(0, $command_tester->getStatusCode());

        self::assertEquals([new RefreshGitoliteProjectConfigurationTask(102), new RefreshGitoliteProjectConfigurationTask(103)], $this->enqueuer->queued_tasks);
    }

    public function testInvalidProjectIDIsRejected(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->enqueuer);
        $command_tester = new CommandTester($command);

        $this->project_manager->method('getValidProject')->willThrowException(new Project_NotFoundException());

        $command_tester->execute(['project_ids' => ['999999999999999999', '103']]);
        self::assertSame(1, $command_tester->getStatusCode());

        self::assertEmpty($this->enqueuer->queued_tasks);
    }

    public function testNoUnnecessaryWorkIsDoneWhenNoProjectIDIsProvided(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->enqueuer);
        $command_tester = new CommandTester($command);

        $command_tester->execute(['project_ids' => []]);
        self::assertSame(0, $command_tester->getStatusCode());

        self::assertEmpty($this->enqueuer->queued_tasks);
    }
}
