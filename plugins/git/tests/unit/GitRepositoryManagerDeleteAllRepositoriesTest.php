<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use EventManager;
use Git_SystemEventManager;
use GitDao;
use GitRepository;
use GitRepositoryFactory;
use GitRepositoryManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use ProjectHistoryDao;
use Tuleap\Git\AsynchronousEvents\GitRepositoryChangeTask;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryManagerDeleteAllRepositoriesTest extends TestCase
{
    private Project $project;
    private GitRepositoryManager $git_repository_manager;
    private GitRepositoryFactory&Stub $repository_factory;
    private EnqueueTaskStub $enqueuer;

    #[\Override]
    protected function setUp(): void
    {
        $this->project            = ProjectTestBuilder::aProject()->withId(42)->build();
        $this->repository_factory = $this->createStub(GitRepositoryFactory::class);
        $this->enqueuer           = new EnqueueTaskStub();

        $this->git_repository_manager = new GitRepositoryManager(
            $this->repository_factory,
            $this->createStub(Git_SystemEventManager::class),
            $this->enqueuer,
            $this->createStub(GitDao::class),
            vfsStream::setup()->url(),
            $this->createStub(FineGrainedPermissionReplicator::class),
            $this->createStub(ProjectHistoryDao::class),
            $this->createStub(HistoryValueFormatter::class),
            $this->createStub(EventManager::class),
        );
    }

    public function testItDeletesNothingWhenThereAreNoRepositories(): void
    {
        $this->expectNotToPerformAssertions();

        $this->repository_factory->method('getAllRepositories')
            ->willReturn([]);

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }

    public function testItDeletesEachRepository(): void
    {
        $repository_1_id = 1;
        $repository_1    = $this->createMock(GitRepository::class);
        $repository_1->expects($this->once())->method('forceMarkAsDeleted');
        $repository_1->method('getId')->willReturn($repository_1_id);
        $repository_1->method('getProjectId')->willReturn($this->project);

        $repository_2_id = 2;
        $repository_2    = $this->createMock(GitRepository::class);
        $repository_2->expects($this->once())->method('forceMarkAsDeleted');
        $repository_2->method('getId')->willReturn($repository_2_id);
        $repository_2->method('getProjectId')->willReturn($this->project);

        $this->repository_factory->method('getAllRepositories')->willReturn([$repository_1, $repository_2]);

        $this->git_repository_manager->deleteProjectRepositories($this->project);

        self::assertEquals(
            [GitRepositoryChangeTask::fromRepository($repository_1), GitRepositoryChangeTask::fromRepository($repository_2)],
            $this->enqueuer->queued_tasks
        );
    }
}
