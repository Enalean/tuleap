<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LogUsersOutInstanceTaskTest extends TestCase
{
    public function testBuildsTaskToLogOutUsersOnAllInstances(): void
    {
        $task = LogUsersOutInstanceTask::logsOutUserOnAllInstances();

        self::assertEquals(['project_id' => null, 'user_id' => null], $task->getPayload());
    }

    public function testBuildsTaskToLogOutUsersOnSpecificInstanceByItsID(): void
    {
        $project = $this->createStub(\Project::class);
        $project->method('getID')->willReturn(200);
        $project->method('usesService')->willReturn(true);
        $project->method('isError')->willReturn(false);
        $project->method('isDeleted')->willReturn(false);
        $task = LogUsersOutInstanceTask::logsOutUserOfAProjectFromItsID(
            (int) $project->getID(),
            ProjectByIDFactoryStub::buildWith($project),
        );

        self::assertNotNull($task);
        self::assertEquals(['project_id' => 200, 'user_id' => null], $task->getPayload());
    }

    public function testBuildsTaskToLogOutUsersOnSpecificInstance(): void
    {
        $project = $this->createStub(\Project::class);
        $project->method('getID')->willReturn(200);
        $project->method('usesService')->willReturn(true);
        $project->method('isError')->willReturn(false);
        $project->method('isDeleted')->willReturn(false);
        $task = LogUsersOutInstanceTask::logsOutUserOfAProject($project);

        self::assertNotNull($task);
        self::assertEquals(['project_id' => 200, 'user_id' => null], $task->getPayload());
    }

    public function testBuildsTaskToLogOutSpecificUserOnSpecificInstance(): void
    {
        $project = $this->createStub(\Project::class);
        $project->method('getID')->willReturn(200);
        $project->method('usesService')->willReturn(true);
        $project->method('isError')->willReturn(false);
        $project->method('isDeleted')->willReturn(false);
        $task = LogUsersOutInstanceTask::logsSpecificUserOutOfAProjectFromItsID(
            (int) $project->getID(),
            ProjectByIDFactoryStub::buildWith($project),
            103
        );

        self::assertNotNull($task);
        self::assertEquals(['project_id' => 200, 'user_id' => 103], $task->getPayload());
    }

    public function testDoesNotBuildTaskWhenProjectDoesNotExist(): void
    {
        $task = LogUsersOutInstanceTask::logsOutUserOfAProjectFromItsID(
            404,
            ProjectByIDFactoryStub::buildWithoutProject(),
        );

        self::assertNull($task);
    }

    public function testDoesNotBuildTaskWhenProjectDoesNotUseTheService(): void
    {
        $project = $this->createStub(\Project::class);
        $project->method('getID')->willReturn(400);
        $project->method('usesService')->willReturn(false);
        $project->method('isError')->willReturn(false);
        $project->method('isDeleted')->willReturn(false);
        $task = LogUsersOutInstanceTask::logsOutUserOfAProjectFromItsID(
            (int) $project->getID(),
            ProjectByIDFactoryStub::buildWith($project),
        );

        self::assertNull($task);
    }
}
