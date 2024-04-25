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
use Tuleap\Git\SystemEvent\OngoingDeletionDAO;

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class GitRepositoryManagerDeleteAllRepositoriesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private Project $project;
    private GitRepositoryManager $git_repository_manager;
    private $git_system_event_manager;
    private $dao;
    private $backup_directory;
    private $repository_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project                  = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withId(42)->build();
        $this->repository_factory       = \Mockery::spy(\GitRepositoryFactory::class);
        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->dao                      = Mockery::mock(GitDao::class);
        $this->backup_directory         = vfsStream::setup()->url();

        $this->git_repository_manager = new GitRepositoryManager(
            $this->repository_factory,
            $this->git_system_event_manager,
            $this->dao,
            $this->backup_directory,
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionReplicator::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(EventManager::class),
            $this->createMock(OngoingDeletionDAO::class),
        );
    }

    public function testItDeletesNothingWhenThereAreNoRepositories(): void
    {
        $this->repository_factory->shouldReceive('getAllRepositories')
            ->with($this->project)->once()
            ->andReturns([]);

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }

    public function testItDeletesEachRepository(): void
    {
        $repository_1_id = 1;
        $repository_1    = \Mockery::spy(\GitRepository::class);
        $repository_1->shouldReceive('forceMarkAsDeleted')->once();
        $repository_1->shouldReceive('getId')->andReturns($repository_1_id);
        $repository_1->shouldReceive('getProjectId')->andReturns($this->project);
        $repository_1->shouldReceive('getBackend')->andReturns(\Mockery::spy(\Git_Backend_Gitolite::class));

        $repository_2_id = 2;
        $repository_2    = \Mockery::spy(\GitRepository::class);
        $repository_2->shouldReceive('forceMarkAsDeleted')->once();
        $repository_2->shouldReceive('getId')->andReturns($repository_2_id);
        $repository_2->shouldReceive('getProjectId')->andReturns($this->project);
        $repository_2->shouldReceive('getBackend')->andReturns(\Mockery::spy(\Git_Backend_Gitolite::class));

        $this->git_system_event_manager->shouldReceive('queueRepositoryDeletion')->times(2);
        $this->git_system_event_manager->shouldReceive('queueRepositoryDeletion')->with($repository_1)->ordered();
        $this->git_system_event_manager->shouldReceive('queueRepositoryDeletion')->with($repository_2)->ordered();

        $this->repository_factory->shouldReceive('getAllRepositories')->andReturns([$repository_1, $repository_2]);

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }
}
