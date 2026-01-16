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
use Git_Backend_Interface;
use Git_SystemEventManager;
use GitDao;
use GitRepository;
use GitRepositoryAlreadyExistsException;
use GitRepositoryCreator;
use GitRepositoryFactory;
use GitRepositoryManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use Tuleap\Git\AsynchronousEvents\GitRepositoryChangeTask;
use Tuleap\Git\Exceptions\GitRepositoryInDeletionException;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Repository\GitRepositoryNameIsInvalidException;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EnqueueTaskStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryManagerCreateTest extends TestCase
{
    private GitRepositoryCreator&MockObject $creator;
    private GitDao&MockObject $dao;
    private GitRepositoryManager&MockObject $manager;
    private GitRepository $repository;
    private EnqueueTaskStub $enqueuer;

    #[\Override]
    protected function setUp(): void
    {
        $this->creator    = $this->createMock(GitRepositoryCreator::class);
        $this->repository = new GitRepository();
        $this->repository->setPath('whatever/repo.git');

        $backend   = $this->createStub(Git_Backend_Interface::class);
        $root_path = vfsStream::setup()->url();
        $backend->method('getGitRootPath')->willReturn($root_path);
        $this->repository->setBackend($backend);

        $this->enqueuer = new EnqueueTaskStub();
        $this->dao      = $this->createMock(GitDao::class);

        $this->manager = $this->getMockBuilder(GitRepositoryManager::class)
            ->setConstructorArgs([
                $this->createStub(GitRepositoryFactory::class),
                $this->createStub(Git_SystemEventManager::class),
                $this->enqueuer,
                $this->dao,
                $root_path,
                $this->createStub(FineGrainedPermissionReplicator::class),
                $this->createStub(ProjectHistoryDao::class),
                $this->createStub(HistoryValueFormatter::class),
                $this->createStub(EventManager::class),
            ])
            ->onlyMethods(['isRepositoryNameAlreadyUsed'])
            ->getMock();
    }

    public function testItThrowAnExceptionIfRepositoryNameCannotBeUsed(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(true);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->expectException(GitRepositoryAlreadyExistsException::class);
        $this->manager->create($this->repository, $this->creator);
    }

    public function testItThrowsAnExceptionIfNameIsNotCompliantToBackendStandards(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(false);
        $this->creator->method('getAllowedCharsInNamePattern');

        $this->expectException(GitRepositoryNameIsInvalidException::class);
        $this->manager->create($this->repository, $this->creator);
    }

    public function testItThrowsAnExceptionIfNThereIsAnOngoingDeletion(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        \Psl\Filesystem\create_directory($this->repository->getFullPath());

        $this->expectException(GitRepositoryInDeletionException::class);
        $this->manager->create($this->repository, $this->creator);
    }

    public function testItCreatesOnRepositoryBackendIfEverythingIsClean(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->dao->expects($this->once())->method('save')->with($this->repository);

        $this->manager->create($this->repository, $this->creator);

        self::assertEquals([GitRepositoryChangeTask::fromRepository($this->repository)], $this->enqueuer->queued_tasks);
    }

    public function testItScheduleAnEventToCreateTheRepositoryInGitolite(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->dao->method('save')->willReturn(54);

        $this->manager->create($this->repository, $this->creator);

        self::assertEquals([GitRepositoryChangeTask::fromRepository($this->repository)], $this->enqueuer->queued_tasks);
    }

    public function testItSetRepositoryIdOnceSavedInDatabase(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->dao->method('save')->willReturn(54);

        $this->manager->create($this->repository, $this->creator);
        $this->assertEquals(54, $this->repository->getId());
    }
}
