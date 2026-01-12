<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Git\AsynchronousEvents;

use CuyZ\Valinor\MapperBuilder;
use Git_GitoliteDriver;
use Psr\Log\NullLogger;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitProjectAsynchronousEventHandlerTest extends TestCase
{
    public function testDumpProjectRepoConfWhenReceivingAGitoliteProjectUpdateEvent(): void
    {
        $driver = $this->createMock(Git_GitoliteDriver::class);

        $project_retriever = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(102)->build());

        $handler = new GitProjectAsynchronousEventHandler(
            new MapperBuilder(),
            $project_retriever,
            $driver,
        );

        $driver->expects($this->once())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('tuleap.git.gitolite-project-configuration', ['project_id' => 102])));
    }

    public function testDoesNothingWhenProcessingSomethingThatIsNotAGitEvent(): void
    {
        $driver  = $this->createMock(Git_GitoliteDriver::class);
        $handler = new GitProjectAsynchronousEventHandler(
            new MapperBuilder(),
            ProjectByIDFactoryStub::buildWithoutProject(),
            $driver,
        );

        $driver->expects($this->never())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('something.not.git', [])));
    }
}
