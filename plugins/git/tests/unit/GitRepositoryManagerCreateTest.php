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
use GitRepositoryAlreadyExistsException;
use GitRepositoryCreator;
use GitRepositoryFactory;
use GitRepositoryManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Exceptions\GitRepositoryInDeletionException;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\Repository\GitRepositoryNameIsInvalidException;
use Tuleap\Git\SystemEvent\OngoingDeletionDAO;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryManagerCreateTest extends TestCase
{
    private GitRepositoryCreator&MockObject $creator;
    private GitDao&MockObject $dao;
    private Git_SystemEventManager&MockObject $git_system_event_manager;
    private GitRepositoryManager&MockObject $manager;
    private GitRepository $repository;
    private OngoingDeletionDAO&MockObject $ongoing_dao;

    protected function setUp(): void
    {
        $this->creator    = $this->createMock(GitRepositoryCreator::class);
        $this->repository = new GitRepository();
        $this->repository->setPath('whatever/repo.git');

        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);
        $this->dao                      = $this->createMock(GitDao::class);
        $this->ongoing_dao              = $this->createMock(OngoingDeletionDAO::class);

        $this->manager = $this->getMockBuilder(GitRepositoryManager::class)
            ->setConstructorArgs([
                $this->createMock(GitRepositoryFactory::class),
                $this->git_system_event_manager,
                $this->dao,
                vfsStream::setup()->url(),
                $this->createMock(FineGrainedPermissionReplicator::class),
                $this->createMock(ProjectHistoryDao::class),
                $this->createMock(HistoryValueFormatter::class),
                $this->createMock(EventManager::class),
                $this->ongoing_dao,
            ])
            ->onlyMethods(['isRepositoryNameAlreadyUsed'])
            ->getMock();
    }

    public function testItThrowAnExceptionIfRepositoryNameCannotBeUsed(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(true);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->expectException(GitRepositoryAlreadyExistsException::class);
        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItThrowsAnExceptionIfNameIsNotCompliantToBackendStandards(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(false);
        $this->creator->method('getAllowedCharsInNamePattern');

        $this->expectException(GitRepositoryNameIsInvalidException::class);
        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItThrowsAnExceptionIfNThereIsAnOngoingDeletion(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(true);

        $this->expectException(GitRepositoryInDeletionException::class);
        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItCreatesOnRepositoryBackendIfEverythingIsClean(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->dao->expects($this->once())->method('save')->with($this->repository);
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);
        $this->git_system_event_manager->method('queueRepositoryUpdate');

        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItScheduleAnEventToCreateTheRepositoryInGitolite(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->dao->method('save')->willReturn(54);

        $this->git_system_event_manager->expects($this->once())->method('queueRepositoryUpdate')->with($this->repository, self::anything());
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);

        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItSetRepositoryIdOnceSavedInDatabase(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->with($this->repository)->willReturn(false);
        $this->creator->method('isNameValid')->willReturn(true);

        $this->dao->method('save')->willReturn(54);
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);
        $this->git_system_event_manager->method('queueRepositoryUpdate');

        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
        $this->assertEquals(54, $this->repository->getId());
    }
}
