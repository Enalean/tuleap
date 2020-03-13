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
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryManagerRepositoryNameTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $factory;
    private $project;
    private $manager;
    private $project_id;
    private $project_name;
    private $dao;
    private $backup_directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project_id   = 12;
        $this->project_name = 'garden';
        $this->project      = \Mockery::spy(\Project::class);
        $this->project->shouldReceive('getID')->andReturns($this->project_id);
        $this->project->shouldReceive('getUnixName')->andReturns($this->project_name);

        $this->dao                = Mockery::mock(GitDao::class);
        $this->backup_directory   = "/tmp/";
        $this->mirror_updater     = \Mockery::spy(\GitRepositoryMirrorUpdater::class);
        $this->mirror_data_mapper = \Mockery::spy(\Git_Mirror_MirrorDataMapper::class);

        $this->factory    = \Mockery::spy(\GitRepositoryFactory::class);
        $this->manager    = new GitRepositoryManager(
            $this->factory,
            \Mockery::spy(\Git_SystemEventManager::class),
            $this->dao,
            $this->backup_directory,
            $this->mirror_updater,
            $this->mirror_data_mapper,
            \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionReplicator::class),
            \Mockery::spy(\ProjectHistoryDao::class),
            \Mockery::spy(\Tuleap\Git\Permissions\HistoryValueFormatter::class),
            \Mockery::spy(EventManager::class)
        );
    }

    private function aRepoWithPath($path)
    {
        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getPath')->andReturn($this->project_name . '/' . $path . '.git');
        $repository->shouldReceive('getPathWithoutLazyLoading')->andReturn($this->project_name . '/' . $path . '.git');
        $repository->shouldReceive('getProject')->andReturn($this->project);
        return $repository;
    }

    public function testItCannotCreateARepositoryWithSamePath()
    {
        $this->factory->shouldReceive('getAllRepositories')->with($this->project)->andReturns(array($this->aRepoWithPath('bla')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function testItCannotCreateARepositoryWithSamePathThatIsNotAtRoot()
    {
        $this->factory->shouldReceive('getAllRepositories')->with($this->project)->andReturns(array($this->aRepoWithPath('foo/bla')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla')));
    }

    public function testItForbidCreationOfRepositoriesWhenPathAlreadyExists()
    {
        $this->factory->shouldReceive('getAllRepositories')->with($this->project)->andReturns(array($this->aRepoWithPath('bla')));

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum/zaz')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla/top')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('blafoo')));
    }

    public function testItForbidCreationOfRepositoriesWhenPathAlreadyExistsAndHasParents()
    {
        $this->factory->shouldReceive('getAllRepositories')->with($this->project)->andReturns(array($this->aRepoWithPath('foo/bla')));

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff/zaz')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/foo')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function testItForbidCreationWhenNewRepoIsInsideExistingPath()
    {
        $this->factory->shouldReceive('getAllRepositories')->with($this->project)->andReturns(array($this->aRepoWithPath('foo/bar/bla')));

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar/zorg')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/zorg')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foobar/zorg')));
    }
}
