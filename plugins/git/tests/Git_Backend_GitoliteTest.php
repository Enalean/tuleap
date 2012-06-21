<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/Git_Backend_Gitolite.class.php';
require_once dirname(__FILE__).'/../include/Git_GitoliteDriver.class.php';
require_once 'common/project/Project.class.php';
require_once 'common/backend/Backend.class.php';

Mock::generate('Backend');
Mock::generate('Git_GitoliteDriver');
Mock::generatePartial('Git_Backend_Gitolite', 'Git_Backend_GitoliteTestVersion', array('getDao', 'loadRepositoryFromId'));
Mock::generate('GitRepository');
Mock::generate('GitDao');
Mock::generate('DataAccessResult');
Mock::generate('Project');
Mock::generate('PermissionsManager');

class Git_Backend_GitoliteTest extends UnitTestCase {
    
    protected $fixturesPath;

    protected $unset_servername = false;
    
    public function setUp() {
        if (!isset($_SERVER['SERVER_NAME'])) {
            $this->unset_servername = true;
            $_SERVER['SERVER_NAME'] = '_dummy_';
        }
        $this->fixtureRenamePath = dirname(__FILE__).'/_fixtures/rename';
        
        if (file_exists($this->fixtureRenamePath)){
            @rmdir($this->fixtureRenamePath .'/legacy');
            @rmdir($this->fixtureRenamePath .'/newone');
            @rmdir($this->fixtureRenamePath);
        }
        
        mkdir($this->fixtureRenamePath .'/legacy', 0777, true);
        
        $link =dirname(__FILE__).'/_fixtures/tmp/perms';
        if (file_exists($link)) {
            unlink($link);
        }
        symlink(dirname(__FILE__).'/_fixtures/perms', $link);
    }
    
    public function tearDown() {
        if ($this->unset_servername) {
            unset($_SERVER['SERVER_NAME']);
        }
        @rmdir($this->fixtureRenamePath .'/legacy');
        @rmdir($this->fixtureRenamePath .'/newone');
        @rmdir($this->fixtureRenamePath);
        unlink(dirname(__FILE__).'/_fixtures/tmp/perms');
    }
    
    function getPartialMock($className, $methods) {
        $partialName = $className.'Partial'.uniqid();
        Mock::generatePartial($className, $partialName, $methods);
        return new $partialName($this);
    }
    
    public function testRenameProjectOk() {
        $project = $this->getPartialMock('Project', array('getUnixName'));
        $project->setReturnValue('getUnixName', 'legacy');
        
        $backend = $this->getPartialMock('Git_Backend_Gitolite', array('glRenameProject', 'getBackend'));
        
        $driver = new MockGit_GitoliteDriver();
        $driver->setReturnValue('getRepositoriesPath', $this->fixtureRenamePath);
        $backend->setDriver($driver);
        
        $bck = new MockBackend();
        $bck->expectNever('log');
        $backend->setReturnValue('getBackend', $bck);
        
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/newone'));
        
        $backend->expectOnce('glRenameProject', array('legacy', 'newone'));
        $this->assertTrue($backend->renameProject($project, 'newone'));
        
        clearstatcache(true, $this->fixtureRenamePath .'/legacy');
        $this->assertFalse(is_dir($this->fixtureRenamePath .'/legacy'));
        $this->assertTrue(is_dir($this->fixtureRenamePath .'/newone'));
    }

    public function testFork_clonesRepositoryAndPushesConf() {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";
        
        $driver     = new MockGit_GitoliteDriver();
        $driver->setReturnValue('fork', true);
        $dao        = new MockGitDao();
        $project    = new MockProject();
        
        $project->setReturnValue('getUnixName', 'gpig');
        
        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);
        
        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver);
        $backend->setDao($dao);
        
        $backend->expectOnce('clonePermissions', array($old_repo, $new_repo));
        $dao->expectOnce('save', array($new_repo));
        $dao->setReturnValue('isRepositoryExisting', false, array('*', $new_repo_path));
        $driver->expectOnce('fork', array($name, 'gpig/'. $old_namespace, 'gpig/'. $new_namespace));
        $driver->expectOnce('dumpProjectRepoConf', array($project));
        $driver->expectOnce('push');

        $backend->fork($old_repo, $new_repo);
    }

    public function testFork_clonesRepositoryFromOneProjectToAnotherSucceedAndPushesConf() {
        $repo_name        = 'tuleap';
        $old_project_name = 'garden';
        $new_project_name = 'gpig';
        $namespace        = '';
        $new_repo_path    = "$new_project_name/$namespace/$repo_name.git";
        
        $driver     = new MockGit_GitoliteDriver();
        $driver->setReturnValue('fork', true);
        $dao        = new MockGitDao();
        
        $new_project    = new MockProject();
        $new_project->setReturnValue('getUnixName', $new_project_name);
        
        $old_project    = new MockProject();
        $old_project->setReturnValue('getUnixName', 'garden');
        
        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $new_repo->setProject($new_project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($repo_name, $namespace);
        $old_repo->setProject($old_project);
        
        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver);
        $backend->setDao($dao);
        
        $backend->expectOnce('clonePermissions', array($old_repo, $new_repo));
        $dao->expectOnce('save', array($new_repo));
        $dao->setReturnValue('isRepositoryExisting', false, array('*', $new_repo_path));
        $driver->expectOnce('fork', array($repo_name, $old_project_name.'/'. $namespace, $new_project_name.'/'. $namespace));
        $driver->expectOnce('dumpProjectRepoConf', array($new_project));
        $driver->expectOnce('push');

        $backend->fork($old_repo, $new_repo);
    }
    
    public function testForkWithTargetPathAlreadyExistingShouldNotFork() {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";
        
        $driver     = new MockGit_GitoliteDriver();
        $dao        = new MockGitDao();
        
        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setPath($new_repo_path);
        $project_id = $new_repo->getProject()->getId();
        
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        
        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver);
        $backend->setDao($dao);
        
        $this->expectException('GitRepositoryAlreadyExistsException');
        
        $backend->expectNever('clonePermissions');
        $dao->expectNever('save');
        $dao->setReturnValue('isRepositoryExisting', true, array($project_id, $new_repo_path));
        $driver->expectNever('fork');
        $driver->expectNever('dumpProjectRepoConf');
        $driver->expectNever('push');

        $backend->fork($old_repo, $new_repo);
    }

    
    public function _GivenAGitRepoWithNameAndNamespace($name, $namespace) {
        $repository = new GitRepository();
        $repository->setName($name);
        $repository->setNamespace($namespace);
        
        $project = new MockProject();
        $project->setReturnValue('getUnixName', 'gpig');
        $project->setReturnValue('getId', 123);
        $repository->setProject($project);
        
        return $repository;
    }

    protected function _GivenABackendGitolite() {
        $driver             = new MockGit_GitoliteDriver();
        $dao                = new MockGitDao();
        $permissionsManager = new MockPermissionsManager();
        $backend = new Git_Backend_Gitolite($driver);
        $backend->setDao($dao);
        $backend->setPermissionsManager($permissionsManager);
        return $backend;
    }

    public function testGetAccessTypeShouldUseGitoliteSshUser() {
        $repository = $this->_GivenAGitRepoWithNameAndNamespace('bionic', 'u/johndoe/uber');
        $backend    = $this->_GivenABackendGitolite();
        
        $url = $backend->getAccessUrl($repository);
        
        // url starts by gitolite
        $this->assertPattern('%^gitolite@%', $url);
    }

    public function testGetAccessTypeShouldIncludesNameSpace() {
        $repository = $this->_GivenAGitRepoWithNameAndNamespace('bionic', 'u/johndoe/uber');
        $backend    = $this->_GivenABackendGitolite();
        
        $url = $backend->getAccessUrl($repository);
        
        // url ends by the namespace + name
        $this->assertPattern('%:gpig/u/johndoe/uber/bionic\.git$%', $url);
    }
    
    public function testGetAccessTypeWithoutNameSpace() {
        $repository = $this->_GivenAGitRepoWithNameAndNamespace('bionic', '');
        $backend    = $this->_GivenABackendGitolite();
        
        $url = $backend->getAccessUrl($repository);
        
        // url ends by the namespace + name
        $this->assertPattern('%:gpig/bionic\.git$%', $url);
    }
    
    public function testClonePermsWithPersonalFork() {
        $old_repo_id = 110;
        $new_repo_id = 220;
        
        $project = new MockProject();
        
        $old = new MockGitRepository();
        $old->setReturnValue('getId', $old_repo_id);
        $old->setReturnValue('getProject', $project);
        
        $new = new MockGitRepository();
        $new->setReturnValue('getId', $new_repo_id);
        $new->setReturnValue('getProject', $project);
        
        $backend  = $this->_GivenABackendGitolite();
        
        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->expectOnce('duplicateWithStatic', array($old_repo_id, $new_repo_id, Git::allPermissionTypes()));
        
        $backend->clonePermissions($old, $new);
    }
    
    public function testClonePermsCrossProjectFork() {
        $old_repo_id = 110;
        $old_project = new MockProject();
        $old_project->setReturnValue('getId', 1);
        
        $new_repo_id = 220;
        $new_project = new MockProject();
        $new_project->setReturnValue('getId', 2);
        
        $old = new MockGitRepository();
        $old->setReturnValue('getId', $old_repo_id);
        $old->setReturnValue('getProject', $old_project);
        
        $new = new MockGitRepository();
        $new->setReturnValue('getId', $new_repo_id);
        $new->setReturnValue('getProject', $new_project);
        
        $backend  = $this->_GivenABackendGitolite();
        
        $permissionsManager = $backend->getPermissionsManager();
        $permissionsManager->expectOnce('duplicateWithoutStatic', array($old_repo_id, $new_repo_id, Git::allPermissionTypes()));
        
        $backend->clonePermissions($old, $new);
    }
}

class Git_Backend_Gitolite_DeleteAllRepositoriesTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->project_id           = 1;
        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->system_event_manager = mock('SystemEventManager');
        
        $this->backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('getRepositoryFactory', 'getSystemEventManager'));
        stub($this->backend)->getRepositoryFactory()->returns($this->repository_factory);
        stub($this->backend)->getSystemEventManager()->returns($this->system_event_manager);
    }
    
    public function itDeletesNothingWhenThereAreNoRepositories() {
        stub($this->repository_factory)->getAllGitoliteRepositories()->returns(array());
        $this->repository_factory->expectOnce('getAllGitoliteRepositories', array($this->project_id));

        $this->backend->deleteProjectRepositories($this->project_id);
    }

     public function itDeletesEachRepository() {
        $repository_1_id = 1;
        $repository_1    = mock('GitRepository');
        $repository_1->expectOnce('forceMarkAsDeleted');
        stub($repository_1)->getId()->returns($repository_1_id);
        stub($repository_1)->getProjectId()->returns($this->project_id);
        
        $repository_2_id = 2;
        $repository_2    = mock('GitRepository');
        $repository_2->expectOnce('forceMarkAsDeleted');
        stub($repository_2)->getId()->returns($repository_2_id);
        stub($repository_2)->getProjectId()->returns($this->project_id);
        
        $this->system_event_manager->expectCallCount('createEvent', 2);
        
        $this->system_event_manager->expectAt(0, 'createEvent', array(
            'GIT_REPO_DELETE',
            $this->project_id.SystemEvent::PARAMETER_SEPARATOR.$repository_1_id,
            '*'
        ));
        
        $this->system_event_manager->expectAt(1, 'createEvent', array(
            'GIT_REPO_DELETE',
            $this->project_id.SystemEvent::PARAMETER_SEPARATOR.$repository_2_id,
            '*'
        ));
        
        stub($this->repository_factory)->getAllGitoliteRepositories($this->project_id)->returns(array($repository_1, $repository_2));
        
        $this->backend->deleteProjectRepositories($this->project_id);
    }
}

?>
