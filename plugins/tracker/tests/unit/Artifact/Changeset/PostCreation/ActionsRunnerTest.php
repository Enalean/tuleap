<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation;

use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class ActionsRunnerTest extends TestCase
{
    public function testAllPostCreationTasksAreExecuted(): void
    {
        $task_1 = $this->createMock(PostCreationTask::class);
        $task_2 = $this->createMock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($task_1, $task_2);

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $task_1->expects(self::once())->method('execute')->with($changeset, true);
        $task_2->expects(self::once())->method('execute')->with($changeset, true);

        $actions_runner->processSyncPostCreationActions($changeset, true);
    }

    public function testOnlySyncTasksAreExecuted(): void
    {
        $task_1 = $this->createMock(PostCreationTask::class);
        $task_2 = $this->createMock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($task_1);
        $actions_runner->addAsyncPostCreationTasks($task_2);

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $task_1->expects(self::once())->method('execute')->with($changeset, true);
        $task_2->expects(self::never())->method('execute');

        $actions_runner->processSyncPostCreationActions($changeset, true);
    }

    public function testAsyncTasksAreAlsoExecutedWhenRunAsync(): void
    {
        $task_1 = $this->createMock(PostCreationTask::class);
        $task_2 = $this->createMock(PostCreationTask::class);

        $actions_runner = new ActionsRunner($task_1);
        $actions_runner->addAsyncPostCreationTasks($task_2);

        $changeset = ChangesetTestBuilder::aChangeset('1')->build();

        $task_1->expects(self::once())->method('execute')->with($changeset, true);
        $task_2->expects(self::once())->method('execute')->with($changeset, true);

        $actions_runner->processAsyncPostCreationActions($changeset, true);
    }

    public function testTasksAreExecutedInOrder(): void
    {
        $task_1         = $this->createMock(PostCreationTask::class);
        $task_2         = $this->createMock(PostCreationTask::class);
        $task_3         = $this->createMock(PostCreationTask::class);
        $actions_runner = new ActionsRunner($task_1, $task_2, $task_3);
        $changeset      = ChangesetTestBuilder::aChangeset('1')->build();
        $last_task_name = '';
        $task_1->method('execute')->willReturnCallback(function () use (&$last_task_name) {
            self::assertEmpty($last_task_name);
            $last_task_name = 'task_1';
        });
        $task_2->method('execute')->willReturnCallback(function () use (&$last_task_name) {
            self::assertSame($last_task_name, 'task_1');
            $last_task_name = 'task_2';
        });
        $task_3->method('execute')->willReturnCallback(function () use (&$last_task_name) {
            self::assertSame($last_task_name, 'task_2');
            $last_task_name = 'task_3';
        });
        $actions_runner->processSyncPostCreationActions($changeset, true);
        self::assertSame($last_task_name, 'task_3');
    }
}
