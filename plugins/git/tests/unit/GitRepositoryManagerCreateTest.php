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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Tuleap\Git\Branch\BranchName;
use Tuleap\Git\Exceptions\GitRepositoryInDeletionException;
use Tuleap\Git\Repository\GitRepositoryNameIsInvalidException;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class GitRepositoryManagerCreateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $creator;
    private $dao;
    private $git_system_event_manager;
    private $backup_directory;

    /**
     * @var \Mockery\Mock|GitRepositoryManager
     */
    private $manager;
    private GitRepository $repository;
    private \Tuleap\Git\SystemEvent\OngoingDeletionDAO&\PHPUnit\Framework\MockObject\MockObject $ongoing_dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator    = \Mockery::spy(\GitRepositoryCreator::class);
        $this->repository = new GitRepository();
        $this->repository->setPath('whatever/repo.git');

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->dao                      = Mockery::mock(GitDao::class);
        $this->backup_directory         = vfsStream::setup()->url();

        $this->ongoing_dao = $this->createMock(\Tuleap\Git\SystemEvent\OngoingDeletionDAO::class);

        $this->manager = \Mockery::mock(
            \GitRepositoryManager::class,
            [
                Mockery::mock('GitRepositoryFactory'),
                $this->git_system_event_manager,
                $this->dao,
                $this->backup_directory,
                Mockery::mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
                Mockery::mock('ProjectHistoryDao'),
                Mockery::mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
                Mockery::mock(EventManager::class),
                $this->ongoing_dao,
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItThrowAnExceptionIfRepositoryNameCannotBeUsed(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(true);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->expectException(GitRepositoryAlreadyExistsException::class);
        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItThrowsAnExceptionIfNameIsNotCompliantToBackendStandards(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(false);

        $this->expectException(GitRepositoryNameIsInvalidException::class);
        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItThrowsAnExceptionIfNThereIsAnOngoingDeletion(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(true);

        $this->expectException(GitRepositoryInDeletionException::class);
        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItCreatesOnRepositoryBackendIfEverythingIsClean(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->dao->shouldReceive('save')->with($this->repository)->once();
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);

        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItScheduleAnEventToCreateTheRepositoryInGitolite(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->dao->shouldReceive('save')->andReturns(54);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->with($this->repository, Mockery::any())->once();
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);

        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
    }

    public function testItSetRepositoryIdOnceSavedInDatabase(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->dao->shouldReceive('save')->andReturns(54);
        $this->ongoing_dao->method('isADeletionForPathOngoingInProject')->willReturn(false);

        $this->manager->create($this->repository, $this->creator, BranchName::defaultBranchName());
        $this->assertEquals(54, $this->repository->getId());
    }
}
