<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

require_once 'bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $project_manager;
    private $project;
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao             = Mockery::mock(GitDao::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class);
        $this->project         = \Mockery::spy(\Project::class);

        $this->project->shouldReceive('getID')->andReturns(101);
        $this->project->shouldReceive('getUnixName')->andReturns('garden');

        $this->project_manager->shouldReceive('getProjectByUnixName')->with('garden')->andReturns($this->project);

        $this->factory = new GitRepositoryFactory($this->dao, $this->project_manager);
    }

    public function testGetRepositoryFromFullPath(): void
    {
        $this->dao->shouldReceive('searchProjectRepositoryByPath')
            ->with(101, 'garden/u/manuel/grou/ping/diskinstaller.git')
            ->once()
            ->andReturns([]);

        $this->factory->getFromFullPath('/data/tuleap/gitolite/repositories/garden/u/manuel/grou/ping/diskinstaller.git');
    }

    public function testGetRepositoryFromFullPathAndGitRoot(): void
    {
        $this->dao->shouldReceive('searchProjectRepositoryByPath')->with(101, 'garden/diskinstaller.git')->once();
        $this->dao->shouldReceive('searchProjectRepositoryByPath')->andReturns([]);

        $this->factory->getFromFullPath('/data/tuleap/gitroot/garden/diskinstaller.git');
    }

    public function testItReturnsSpecialRepositoryWhenIdMatches(): void
    {
        $this->assertInstanceOf(
            GitRepositoryGitoliteAdmin::class,
            $this->factory->getRepositoryById(GitRepositoryGitoliteAdmin::ID)
        );
    }

    public function testItCanonicalizesRepositoryName(): void
    {
        $user    = \Mockery::spy(\PFUser::class);
        $project = \Mockery::spy(\Project::class);
        $backend = \Mockery::spy(\Git_Backend_Interface::class);

        $repository = $this->factory->buildRepository($project, 'a', $user, $backend);
        $this->assertEquals('a', $repository->getName());

        $repository = $this->factory->buildRepository($project, 'a/b', $user, $backend);
        $this->assertEquals('a/b', $repository->getName());

        $repository = $this->factory->buildRepository($project, 'a//b', $user, $backend);
        $this->assertEquals('a/b', $repository->getName());

        $repository = $this->factory->buildRepository($project, 'a///b', $user, $backend);
        $this->assertEquals('a/b', $repository->getName());
    }
}
