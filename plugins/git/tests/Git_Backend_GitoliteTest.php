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

Mock::Generate('Backend');
Mock::Generate('Git_GitoliteDriver');
Mock::generatePartial('Git_Backend_Gitolite', 'Git_Backend_GitoliteTestVersion', array('getDao', 'loadRepositoryFromId'));
Mock::generate('GitRepository');
Mock::generate('GitDao');
Mock::generate('DataAccessResult');
Mock::generate('Project');

class Git_Backend_GitoliteTest extends UnitTestCase {
    
    protected $fixturesPath;


    public function setUp() {
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

    public function testDeleteProjectRepositoriesDaoError() {
        $backend = new Git_Backend_GitoliteTestVersion();
        $dao = new MockGitDao();
        $dao->expectOnce('getAllGitoliteRespositories');
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', true);
        $dao->setReturnValue('getAllGitoliteRespositories', $dar);
        $backend->setReturnValue('getDao', $dao);
        $backend->expectNever('loadRepositoryFromId');
        $this->assertFalse($backend->deleteProjectRepositories(1));
    }

    public function testDeleteProjectRepositoriesNothingToDelete() {
        $backend = new Git_Backend_GitoliteTestVersion();
        $dao = new MockGitDao();
        $dao->expectOnce('getAllGitoliteRespositories');
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', false);
        $dao->setReturnValue('getAllGitoliteRespositories', $dar);
        $backend->setReturnValue('getDao', $dao);
        $backend->expectNever('loadRepositoryFromId');
        $this->assertTrue($backend->deleteProjectRepositories(1));
    }

    public function testDeleteProjectRepositoriesDeleteFail() {
        $backend = new Git_Backend_GitoliteTestVersion();
        $dao = new MockGitDao();
        $dao->expectOnce('getAllGitoliteRespositories');
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dao->setReturnValue('getAllGitoliteRespositories', $dar);
        $dar->setReturnValueAt(0, 'getRow', array('repository_id' => 1));
        $dar->setReturnValueAt(1, 'getRow', array('repository_id' => 2));
        $dar->setReturnValueAt(2, 'getRow', array('repository_id' => 3));
        $dao->setReturnValue('getAllGitoliteRespositories', $dar);
        $backend->setReturnValue('getDao', $dao);
        $repository = new MockGitRepository();
        $repository->setReturnValueAt(0, 'delete', true);
        $repository->setReturnValueAt(1, 'delete', false);
        $repository->setReturnValueAt(2, 'delete', true);
        $backend->setReturnValue('loadRepositoryFromId', $repository);
        $backend->expectCallCount('loadRepositoryFromId', 3);
        $repository->expectCallCount('delete', 3);
        $this->assertFalse($backend->deleteProjectRepositories(1));
    }

    public function testDeleteProjectRepositoriesSuccess() {
        $backend = new Git_Backend_GitoliteTestVersion();
        $dao = new MockGitDao();
        $dao->expectOnce('getAllGitoliteRespositories');
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValueAt(0, 'getRow', array('repository_id' => 1));
        $dar->setReturnValueAt(1, 'getRow', array('repository_id' => 2));
        $dar->setReturnValueAt(2, 'getRow', array('repository_id' => 3));
        $dao->setReturnValue('getAllGitoliteRespositories', $dar);
        $backend->setReturnValue('getDao', $dao);
        $repository = new MockGitRepository();
        $repository->setReturnValue('delete', true);
        $backend->setReturnValue('loadRepositoryFromId', $repository);
        $backend->expectCallCount('loadRepositoryFromId', 3);
        $repository->expectCallCount('delete', 3);
        $this->assertTrue($backend->deleteProjectRepositories(1));
    }
    
    public function testFork_clonesRepositoryAndPushesConf() {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        
        $driver     = new MockGit_GitoliteDriver();
        $dao        = new MockGitDao();
        $project    = new MockProject();
        
        $project->setReturnValue('getUnixName', 'gpig');
        
        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);
        
        $backend    = new Git_Backend_Gitolite($driver);
        $backend->setDao($dao);
        
        $dao->expectOnce('save', array($new_repo));
        $driver->expectOnce('fork', array($name, 'gpig/'. $old_namespace, 'gpig/'. $new_namespace));        
        $driver->expectOnce('dumpProjectRepoConf', array($project));
        $driver->expectOnce('push');        

        $backend->fork($old_repo, $new_repo);
    }
    
    public function _GivenAGitRepoWithNameAndNamespace($name, $namespace) {
        $repository = new GitRepository();
        $repository->setName($name);
        $repository->setNamespace($namespace);
        
        $project = new MockProject();
        $project->setReturnValue('getUnixName', 'gpig');
        $repository->setProject($project);
        
        return $repository;
    }

    protected function _GivenABackendGitolite() {
        $driver  = new MockGit_GitoliteDriver();
        $dao     = new MockGitDao();
        $backend = new Git_Backend_Gitolite($driver);
        $backend->setDao($dao);
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

}

?>
