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
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Tuleap\Git\Notifications\UgroupsToNotifyDao;
use Tuleap\Git\Notifications\UsersToNotifyDao;
use Tuleap\Git\RetrieveGitRepository;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\GitBackendInterfaceStub;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventContent;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryAsynchronousEventHandlerTest extends TestCase
{
    public function testHandlesGitRepositoryCreationEvent(): void
    {
        $driver               = $this->createMock(Git_GitoliteDriver::class);
        $repository           = GitRepositoryTestBuilder::aProjectRepository()->withId(123)->build();
        $repository_retriever = RetrieveGitRepositoryStub::withGitRepositories($repository);
        $event_dispatcher     = EventDispatcherStub::withIdentityCallback();

        $handler = $this->buildHandler($driver, $repository_retriever, $event_dispatcher);

        $driver->expects($this->once())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('tuleap.git.repository-change', ['repository_id' => $repository->getId()])));

        self::assertEquals(0, $event_dispatcher->getCallCount());
    }

    public function testHandlesGitRepositoryDeletionEvent(): void
    {
        $driver               = $this->createMock(Git_GitoliteDriver::class);
        $backend              =  GitBackendInterfaceStub::build();
        $repository           = GitRepositoryTestBuilder::aProjectRepository()
            ->withId(123)
            ->withDeletionDate('2026-01-01')
            ->withBackend($backend)
            ->build();
        $repository_retriever = RetrieveGitRepositoryStub::withGitRepositories($repository);
        $event_dispatcher     = EventDispatcherStub::withIdentityCallback();

        $handler = $this->buildHandler($driver, $repository_retriever, $event_dispatcher);

        $driver->expects($this->once())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('tuleap.git.repository-change', ['repository_id' => $repository->getId()])));

        self::assertTrue($backend->has_been_deleted);
        self::assertEquals(1, $event_dispatcher->getCallCount());
    }

    public function testHandlesForkEvent(): void
    {
        $driver               = $this->createMock(Git_GitoliteDriver::class);
        $backend              =  GitBackendInterfaceStub::build();
        $parent_repository    = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build();
        $repository           = GitRepositoryTestBuilder::aForkOf($parent_repository)
            ->withId(123)
            ->withBackend($backend)
            ->build();
        $repository_retriever = RetrieveGitRepositoryStub::withGitRepositories($repository, $parent_repository);
        $event_dispatcher     = EventDispatcherStub::withIdentityCallback();

        $handler = $this->buildHandler($driver, $repository_retriever, $event_dispatcher);

        $driver->expects($this->once())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('tuleap.git.repository-fork', ['repository_id' => $repository->getId()])));

        self::assertTrue($backend->has_been_forked);
        self::assertEquals(1, $event_dispatcher->getCallCount());
    }

    public function testDoesNothingWhenTheRepositoryCannotBeFound(): void
    {
        $driver = $this->createMock(Git_GitoliteDriver::class);

        $handler = $this->buildHandler($driver, RetrieveGitRepositoryStub::withoutGitRepository(), EventDispatcherStub::withIdentityCallback());

        $driver->expects($this->never())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('tuleap.git.repository-change', ['repository_id' => 404])));
    }

    public function testDoesNothingWhenProcessingSomethingThatIsNotAGitRepositoryEvent(): void
    {
        $driver  = $this->createMock(Git_GitoliteDriver::class);
        $handler = $this->buildHandler($driver, $this->createStub(RetrieveGitRepository::class), EventDispatcherStub::withIdentityCallback());

        $driver->expects($this->never())->method('dumpProjectRepoConf');

        $handler->handle(new WorkerEvent(new NullLogger(), new WorkerEventContent('something.not.git', [])));
    }

    private function buildHandler(
        Git_GitoliteDriver $driver,
        RetrieveGitRepository $repository_retriever,
        EventDispatcherInterface $event_dispatcher,
    ): GitRepositoryAsynchronousEventHandler {
        $ugroups_to_notify = $this->createStub(UgroupsToNotifyDao::class);
        $ugroups_to_notify->method('deleteByRepositoryId');
        $users_to_notify = $this->createStub(UsersToNotifyDao::class);
        $users_to_notify->method('deleteByRepositoryId');
        return new GitRepositoryAsynchronousEventHandler(
            new MapperBuilder(),
            new DBTransactionExecutorPassthrough(),
            $driver,
            $repository_retriever,
            $ugroups_to_notify,
            $users_to_notify,
            $event_dispatcher,
            new NullLogger(),
        );
    }
}
