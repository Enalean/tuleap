<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\TemporaryTestDirectory;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Git_Backend_GitoliteTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    private $fixtureRenamePath;
    private $forkPermissions;

    public function setUp(): void
    {
        parent::setUp();
        $this->fixtureRenamePath = $this->getTmpDir() . '/rename';

        mkdir($this->fixtureRenamePath . '/legacy', 0770, true);

        $this->forkPermissions = [];
    }

    public function testRenameProjectOk()
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getUnixName')->andReturns('legacy');

        $backend = Mockery::mock(Git_Backend_Gitolite::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $driver = \Mockery::spy(\Git_GitoliteDriver::class);
        $driver->shouldReceive('getRepositoriesPath')->andReturns($this->fixtureRenamePath);
        $backend->setDriver($driver);

        $bck = \Mockery::spy(\Backend::class);
        $bck->shouldReceive('log')->never();
        $backend->shouldReceive('getBackend')->andReturns($bck);

        $this->assertTrue(is_dir($this->fixtureRenamePath . '/legacy'));
        $this->assertFalse(is_dir($this->fixtureRenamePath . '/newone'));

        $backend->shouldReceive('glRenameProject')->with('legacy', 'newone')->once();
        $this->assertTrue($backend->renameProject($project, 'newone'));

        clearstatcache(true, $this->fixtureRenamePath . '/legacy');
        $this->assertFalse(is_dir($this->fixtureRenamePath . '/legacy'));
        $this->assertTrue(is_dir($this->fixtureRenamePath . '/newone'));
    }

    public function testItSavesForkInfoIntoDB(): void
    {
        $name          = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $project = \Mockery::spy(\Project::class);

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->givenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = \Mockery::mock(\Git_Backend_Gitolite::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao     = \Mockery::spy(GitDao::class);
        $backend->setDao($dao);

        $backend->shouldReceive('clonePermissions')->with($old_repo, $new_repo)->once();
        $dao->shouldReceive('save')->with($new_repo)->once()->andReturns(667);
        $dao->shouldReceive('isRepositoryExisting')->with(\Mockery::any(), $new_repo_path)->andReturns(false);

        $this->assertEquals(667, $backend->fork($old_repo, $new_repo, $this->forkPermissions));
    }

    public function testForkClonesRepository(): void
    {
        $name          = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver  = \Mockery::spy(\Git_GitoliteDriver::class);
        $project = \Mockery::spy(\Project::class);

        $project->shouldReceive('getUnixName')->andReturns('gpig');

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->givenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = \Mockery::mock(
            \Git_Backend_Gitolite::class,
            [
                $driver,
                \Mockery::spy(GitoliteAccessURLGenerator::class),
                new DefaultBranchUpdateExecutorStub(),
                \Mockery::spy(\Psr\Log\LoggerInterface::class),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $driver->shouldReceive('fork')->with($name, 'gpig/' . $old_namespace, 'gpig/' . $new_namespace)->once()->andReturns(true);
        $driver->shouldReceive('dumpProjectRepoConf')->with($project)->once();
        $driver->shouldReceive('push')->never();

        $backend->forkOnFilesystem($old_repo, $new_repo);
    }

    public function testForkClonesRepositoryFromOneProjectToAnotherSucceed(): void
    {
        $repo_name        = 'tuleap';
        $old_project_name = 'garden';
        $new_project_name = 'gpig';
        $namespace        = '';
        $new_repo_path    = "$new_project_name/$namespace/$repo_name.git";

        $driver = \Mockery::spy(\Git_GitoliteDriver::class);

        $new_project = \Mockery::spy(\Project::class);
        $new_project->shouldReceive('getUnixName')->andReturns($new_project_name);

        $old_project = \Mockery::spy(\Project::class);
        $old_project->shouldReceive('getUnixName')->andReturns('garden');

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $new_repo->setProject($new_project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->givenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $old_repo->setProject($old_project);

        $backend = \Mockery::mock(
            \Git_Backend_Gitolite::class,
            [
                $driver,
                \Mockery::spy(GitoliteAccessURLGenerator::class),
                new DefaultBranchUpdateExecutorStub(),
                \Mockery::spy(\Psr\Log\LoggerInterface::class),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $driver->shouldReceive('fork')->with($repo_name, $old_project_name . '/' . $namespace, $new_project_name . '/' . $namespace)->once()->andReturns(true);
        $driver->shouldReceive('dumpProjectRepoConf')->with($new_project)->once();
        $driver->shouldReceive('push')->never();

        $backend->forkOnFilesystem($old_repo, $new_repo, $this->forkPermissions);
    }

    public function testForkWithTargetPathAlreadyExistingShouldNotFork(): void
    {
        $name          = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver = \Mockery::spy(\Git_GitoliteDriver::class);
        $dao    = \Mockery::spy(GitDao::class);

        $new_repo = $this->givenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setPath($new_repo_path);
        $project_id = $new_repo->getProject()->getId();

        $old_repo = $this->givenAGitRepoWithNameAndNamespace($name, $old_namespace);

        $backend = \Mockery::mock(
            \Git_Backend_Gitolite::class,
            [
                $driver,
                \Mockery::spy(GitoliteAccessURLGenerator::class),
                new DefaultBranchUpdateExecutorStub(),
                \Mockery::spy(\Psr\Log\LoggerInterface::class),
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $backend->setDao($dao);

        $this->expectException(\GitRepositoryAlreadyExistsException::class);

        $backend->shouldReceive('clonePermissions')->never();
        $dao->shouldNotReceive('save');
        $dao->shouldReceive('isRepositoryExisting')->with($project_id, $new_repo_path)->andReturns(true);
        $driver->shouldReceive('fork')->never();
        $driver->shouldReceive('dumpProjectRepoConf')->never();
        $driver->shouldReceive('push')->never();

        $backend->fork($old_repo, $new_repo, $this->forkPermissions);
    }

    public function testClonePermsWithPersonalFork(): void
    {
        $old_repo_id = 110;
        $new_repo_id = 220;

        $project = \Mockery::spy(\Project::class);

        $old = \Mockery::spy(\GitRepository::class);
        $old->shouldReceive('getId')->andReturns($old_repo_id);
        $old->shouldReceive('getProject')->andReturns($project);

        $new = \Mockery::spy(\GitRepository::class);
        $new->shouldReceive('getId')->andReturns($new_repo_id);
        $new->shouldReceive('getProject')->andReturns($project);

        $backend = $this->givenABackendGitolite();

        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->shouldReceive('duplicateWithStatic')->with($old_repo_id, $new_repo_id, Git::allPermissionTypes())->once();

        $backend->clonePermissions($old, $new);
    }

    public function testIsNameValid(): void
    {
        $this->assertTrue($this->givenABackendGitolite()->isNameValid("lerepo"));
        $this->assertTrue($this->givenABackendGitolite()->isNameValid("le_repo/repo"));
        $this->assertTrue($this->givenABackendGitolite()->isNameValid("le_repo65"));
        $this->assertTrue($this->givenABackendGitolite()->isNameValid("le_repo_git"));
        $this->assertTrue($this->givenABackendGitolite()->isNameValid("lerepo.gitea"));
    }

    public function testIsNameValidReturnFalseIfARepositoryNameEndWithPointGit(): void
    {
        $this->assertFalse($this->givenABackendGitolite()->isNameValid("lerepo.git"));
        $this->assertFalse($this->givenABackendGitolite()->isNameValid("le_repo.git/repo"));
        $this->assertFalse($this->givenABackendGitolite()->isNameValid("le_repo65/repo.git"));
    }

    public function testClonePermsCrossProjectFork(): void
    {
        $old_repo_id = 110;
        $old_project = \Mockery::spy(\Project::class);
        $old_project->shouldReceive('getId')->andReturns(1);

        $new_repo_id = 220;
        $new_project = \Mockery::spy(\Project::class);
        $new_project->shouldReceive('getId')->andReturns(2);

        $old = \Mockery::spy(\GitRepository::class);
        $old->shouldReceive('getId')->andReturns($old_repo_id);
        $old->shouldReceive('getProject')->andReturns($old_project);

        $new = \Mockery::spy(\GitRepository::class);
        $new->shouldReceive('getId')->andReturns($new_repo_id);
        $new->shouldReceive('getProject')->andReturns($new_project);

        $backend = $this->givenABackendGitolite();

        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->shouldReceive('duplicateWithoutStatic')->with($old_repo_id, $new_repo_id, Git::allPermissionTypes())->once();

        $backend->clonePermissions($old, $new);
    }

    private function givenAGitRepoWithNameAndNamespace($name, $namespace): GitRepository
    {
        $repository = new GitRepository();
        $repository->setName($name);
        $repository->setNamespace($namespace);
        $backend_interface = $this->createStub(Git_Backend_Interface::class);
        $backend_interface->method('getGitRootPath')->willReturn('/some_path');
        $repository->setBackend($backend_interface);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('gpig');
        $project->shouldReceive('getId')->andReturns(123);
        $repository->setProject($project);

        return $repository;
    }

    private function givenABackendGitolite(): Git_Backend_Gitolite
    {
        $driver             = \Mockery::spy(\Git_GitoliteDriver::class);
        $dao                = \Mockery::spy(GitDao::class);
        $permissionsManager = \Mockery::spy(\PermissionsManager::class);
        $gitPlugin          = \Mockery::mock(GitPlugin::class);
        $backend            = new Git_Backend_Gitolite($driver, \Mockery::spy(GitoliteAccessURLGenerator::class), new DefaultBranchUpdateExecutorStub(), \Mockery::spy(\Psr\Log\LoggerInterface::class));
        $backend->setDao($dao);
        $backend->setPermissionsManager($permissionsManager);
        $backend->setGitPlugin($gitPlugin);
        return $backend;
    }
}
