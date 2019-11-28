<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'bootstrap.php';

class GitRepositoryTest extends TuleapTestCase
{

    public function testDeletionPathShouldBeInProjectPath()
    {
        $tmp_folder = $this->getTmpDir() . '/perms';
        symlink(dirname(__FILE__).'/_fixtures/perms', $tmp_folder);

        $repo = new GitRepository();
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/default.conf'));
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', $tmp_folder . '/default.conf'));
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', $tmp_folder . '/coincoin.git.git'));

        $this->assertFalse($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/../../default.conf'));
        $this->assertFalse($repo->isSubPath('_fixtures/perms/', 'coincoin'));

        unlink($tmp_folder);
    }


    public function testDeletionShouldAffectDotGit()
    {
        $repo = new GitRepository();
        $this->assertTrue($repo->isDotGit('default.git'));
        $this->assertTrue($repo->isDotGit('default.git.git'));

        $this->assertFalse($repo->isDotGit('default.conf'));
        $this->assertFalse($repo->isDotGit('d'));
        $this->assertFalse($repo->isDotGit('defaultgit'));
        $this->assertFalse($repo->isDotGit('default.git.old'));
    }

    public function testGetRepositoryIDByNameSuccess()
    {
        $repo = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm = \Mockery::spy(\ProjectManager::class);
        $project = \Mockery::spy(\Project::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns($project);
        $dao = \Mockery::mock(GitDao::class);
        $repo->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('getProjectRepositoryByName')->andReturn(['repository_id' => 48])->once();

        $repo->shouldReceive('_getProjectManager')->once()->andReturns($pm);
        $project->shouldReceive('getID')->once();

        $this->assertEqual($repo->getRepositoryIDByName('repo', 'prj'), 48);
    }

    public function testGetRepositoryIDByNameNoRepository()
    {
        $repo = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm = \Mockery::spy(\ProjectManager::class);
        $project = \Mockery::spy(\Project::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns($project);
        $dao = \Mockery::mock(GitDao::class);
        $repo->shouldReceive('getDao')->andReturns($dao);
        $dao->shouldReceive('getProjectRepositoryByName')->andReturnFalse()->once();

        $repo->shouldReceive('_getProjectManager')->once()->andReturns($pm);
        $project->shouldReceive('getID')->once();

        $this->assertEqual($repo->getRepositoryIDByName('repo', 'prj'), 0);
    }

    public function testGetRepositoryIDByNameNoProjectID()
    {
        $repo = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $pm = \Mockery::spy(\ProjectManager::class);
        $project = \Mockery::spy(\Project::class);
        $pm->shouldReceive('getProjectByUnixName')->andReturns(false);

        $repo->shouldReceive('_getProjectManager')->once()->andReturns($pm);
        $project->shouldReceive('getID')->never();

        $this->assertIdentical($repo->getRepositoryIDByName('repo', 'prj'), 0);
    }

    public function _newUser($name)
    {
        $user = new PFUser(array('language_id' => 1));
        $user->setUserName($name);
        return $user;
    }

    public function testGetFullName_appendsNameSpaceToName()
    {
        $repo = $this->_GivenARepositoryWithNameAndNamespace('tulip', null);
        $this->assertEqual('tulip', $repo->getFullName());

        $repo = $this->_GivenARepositoryWithNameAndNamespace('tulip', 'u/johan');
        $this->assertEqual('u/johan/tulip', $repo->getFullName());
    }

    protected function _GivenARepositoryWithNameAndNamespace($name, $namespace)
    {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

    public function testProjectRepositoryDosNotBelongToUser()
    {
        $user = new PFUser(array('language_id' => 1));
        $user->setUserName('sandra');

        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_PROJECT);

        $this->assertFalse($repo->belongsTo($user));
    }

    public function testUserRepositoryBelongsToUser()
    {
        $user = new PFUser(array('language_id' => 1));
        $user->setUserName('sandra');

        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);

        $this->assertTrue($repo->belongsTo($user));
    }
    public function testUserRepositoryDoesNotBelongToAnotherUser()
    {
        $creator = new PFUser(array('language_id' => 1));
        $creator->setId(123);

        $user = new PFUser(array('language_id' => 1));
        $user->setId(456);

        $repo = new GitRepository();
        $repo->setCreator($creator);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);

        $this->assertFalse($repo->belongsTo($user));
    }

    public function itIsMigratableIfItIsAGitoliteRepo()
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $this->assertTrue($repo->canMigrateToGerrit());
    }

    public function itIsNotMigratableIfItIsAGitshellRepo()
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITSHELL);
        $this->assertFalse($repo->canMigrateToGerrit());
    }

    public function itIsNotMigratableIfAlreadyAGerritRepo()
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerId(34);
        $this->assertFalse($repo->canMigrateToGerrit());
    }

    public function itIsMigratableIfItHasAlreadyBeenAGerritRepoInThePastAndRemoteProjectIsNotDeleted()
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerDisconnectDate(12345677890);
        $repo->setRemoteProjectDeletionDate(null);
        $repo->setRemoteServerId(4154);
        $this->assertTrue($repo->canMigrateToGerrit());
    }

    public function itIsMigratableIfItHasAlreadyBeenAGerritRepoInThePastAndRemoteProjectIsDeleted()
    {
        $repo = new GitRepository();
        $repo->setBackendType(GitDao::BACKEND_GITOLITE);
        $repo->setRemoteServerDisconnectDate(12345677890);
        $repo->setRemoteProjectDeletionDate(12345677890);
        $repo->setRemoteServerId(4154);
        $this->assertTrue($repo->canMigrateToGerrit());
    }

    public function itIsNotMigratedIfItWasDisconnected()
    {
        $repository = new GitRepository();
        $repository->setDeletionDate(null);
        $repository->setRemoteServerDisconnectDate(12345677890);
        $repository->setRemoteServerId(1);

        $this->assertFalse($repository->isMigratedToGerrit());
    }
}

class GitRepository_CanDeletedTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->backend = \Mockery::spy(\GitBackend::class)->shouldReceive('getGitRootPath')->andReturns(dirname(__FILE__).'/_fixtures')->getMock();
        $project       = \Mockery::spy(\Project::class)->shouldReceive('getUnixName')->andReturns('perms')->getMock();

        $this->repo = new GitRepository();
        $this->repo->setBackend($this->backend);
        $this->repo->setProject($project);
    }

    public function itCanBeDeletedWithDotGitDotGitRepositoryShouldSucceed()
    {
        $this->backend->shouldReceive('canBeDeleted')->andReturns(true);
        $this->repo->setPath('perms/coincoin.git.git');

        $this->assertTrue($this->repo->canBeDeleted());
    }

    public function itCanBeDeletedWithWrongRepositoryPathShouldFail()
    {
        $this->backend->shouldReceive('canBeDeleted')->andReturns(true);
        $this->repo->setPath('perms/coincoin');

        $this->assertFalse($this->repo->canBeDeleted());
    }

    public function itCannotBeDeletedIfBackendForbidIt()
    {
        $this->backend->shouldReceive('canBeDeleted')->andReturns(false);

        $this->repo->setPath('perms/coincoin.git.git');
        $this->assertFalse($this->repo->canBeDeleted());
    }
}

class GitRepository_GetAccessUrlTest extends TuleapTestCase
{
    /**
     * @var Git_Backend_Interface
     */
    private $backend;

    /**
     * @var GitRepository
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->backend = \Mockery::spy(\GitBackend::class);

        $this->repository = new GitRepository();
        $this->repository->setBackend($this->backend);
    }

    public function itReturnsTheBackendContent()
    {
        $access_url = array('ssh' => 'plop');
        $this->backend->shouldReceive('getAccessURL')->andReturns(array('ssh' => 'plop'));
        $this->assertEqual($this->repository->getAccessURL(), $access_url);
    }
}
