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
use PHPUnit\Framework\TestCase;
use Tuleap\Git\Repository\GitRepositoryNameIsInvalidException;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryManagerCreateTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->creator    = \Mockery::spy(\GitRepositoryCreator::class);
        $this->repository = new GitRepository();

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->dao                      = Mockery::mock(GitDao::class);
        $this->backup_directory         = vfsStream::setup()->url();
        $this->mirror_updater           = \Mockery::spy(\GitRepositoryMirrorUpdater::class);
        $this->mirror_data_mapper       = \Mockery::spy(\Git_Mirror_MirrorDataMapper::class);

        $this->manager = \Mockery::mock(
            \GitRepositoryManager::class,
            [
                Mockery::mock('GitRepositoryFactory'),
                $this->git_system_event_manager,
                $this->dao,
                $this->backup_directory,
                $this->mirror_updater,
                $this->mirror_data_mapper,
                Mockery::mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
                Mockery::mock('ProjectHistoryDao'),
                Mockery::mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
                Mockery::mock(EventManager::class)
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
        $this->manager->create($this->repository, $this->creator, array());
    }

    public function testItThrowsAnExceptionIfNameIsNotCompliantToBackendStandards(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(false);

        $this->expectException(GitRepositoryNameIsInvalidException::class);
        $this->manager->create($this->repository, $this->creator, array());
    }

    public function testItCreatesOnRepositoryBackendIfEverythingIsClean()
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->dao->shouldReceive('save')->with($this->repository)->once();
        $this->manager->create($this->repository, $this->creator, array());
    }

    public function testItScheduleAnEventToCreateTheRepositoryInGitolite(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->dao->shouldReceive('save')->andReturns(54);

        $this->git_system_event_manager->shouldReceive('queueRepositoryUpdate')->with($this->repository)->once();

        $this->manager->create($this->repository, $this->creator, array());
    }

    public function testItSetRepositoryIdOnceSavedInDatabase(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->with($this->repository)->andReturns(false);
        $this->creator->shouldReceive('isNameValid')->andReturns(true);

        $this->dao->shouldReceive('save')->andReturns(54);

        $this->manager->create($this->repository, $this->creator, array());
        $this->assertEquals(54, $this->repository->getId());
    }
}
