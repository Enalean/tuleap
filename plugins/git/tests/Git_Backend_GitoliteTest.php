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

require_once 'bootstrap.php';
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

abstract class Git_Backend_GitoliteCommonTest extends TuleapTestCase {

    protected function _GivenAGitRepoWithNameAndNamespace($name, $namespace) {
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
        $driver             = mock('Git_GitoliteDriver');
        $dao                = mock('GitDao');
        $permissionsManager = mock('PermissionsManager');
        $gitPlugin          = mock('GitPlugin');
        $backend = new Git_Backend_Gitolite($driver, mock('Logger'));
        $backend->setDao($dao);
        $backend->setPermissionsManager($permissionsManager);
        $backend->setGitPlugin($gitPlugin);
        return $backend;
    }
}

class Git_Backend_GitoliteTest extends Git_Backend_GitoliteCommonTest {
    
    protected $fixturesPath;

    private   $forkPermissions;

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
        $this->forkPermissions = array();
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

    public function itSavesForkInfoIntoDB() {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";
        
        $driver = mock('Git_GitoliteDriver');

        $project = mock('Project');

        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);

        $backend = partial_mock('Git_Backend_Gitolite', array('clonePermissions'), array($driver, mock('Logger')));
        $dao = mock('GitDao');
        $backend->setDao($dao);

        $backend->expectOnce('clonePermissions', array($old_repo, $new_repo));
        $dao->expectOnce('save', array($new_repo));
        stub($dao)->save()->returns(667);
        $dao->setReturnValue('isRepositoryExisting', false, array('*', $new_repo_path));

        $this->assertEqual(667, $backend->fork($old_repo, $new_repo, $this->forkPermissions));
    }

    public function testFork_clonesRepository() {
        $name  = 'tuleap';
        $old_namespace = '';
        $new_namespace = 'u/johanm/ericsson';
        $new_repo_path = "gpig/$new_namespace/$name.git";

        $driver     = new MockGit_GitoliteDriver();
        $driver->setReturnValue('fork', true);
        $project    = new MockProject();
        
        $project->setReturnValue('getUnixName', 'gpig');
        
        $new_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $new_namespace);
        $new_repo->setProject($project);
        $new_repo->setPath($new_repo_path);
        $old_repo = $this->_GivenAGitRepoWithNameAndNamespace($name, $old_namespace);
        $old_repo->setProject($project);
        
        $backend = TestHelper::getPartialMock('Git_Backend_Gitolite', array('clonePermissions'));
        $backend->__construct($driver, mock('Logger'));
        
        $driver->expectOnce('fork', array($name, 'gpig/'. $old_namespace, 'gpig/'. $new_namespace));
        $driver->expectOnce('dumpProjectRepoConf', array($project));
        $driver->expectNever('push');

        $backend->forkOnFilesystem($old_repo, $new_repo);
    }

    public function testFork_clonesRepositoryFromOneProjectToAnotherSucceed() {
        $repo_name        = 'tuleap';
        $old_project_name = 'garden';
        $new_project_name = 'gpig';
        $namespace        = '';
        $new_repo_path    = "$new_project_name/$namespace/$repo_name.git";
        
        $driver     = new MockGit_GitoliteDriver();
        $driver->setReturnValue('fork', true);
        
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
        $backend->__construct($driver, mock('Logger'));
        
        $driver->expectOnce('fork', array($repo_name, $old_project_name.'/'. $namespace, $new_project_name.'/'. $namespace));
        $driver->expectOnce('dumpProjectRepoConf', array($new_project));
        $driver->expectNever('push');

        $backend->forkOnFilesystem($old_repo, $new_repo, $this->forkPermissions);
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
        $backend->__construct($driver, mock('Logger'));
        $backend->setDao($dao);
        
        $this->expectException('GitRepositoryAlreadyExistsException');
        
        $backend->expectNever('clonePermissions');
        $dao->expectNever('save');
        $dao->setReturnValue('isRepositoryExisting', true, array($project_id, $new_repo_path));
        $driver->expectNever('fork');
        $driver->expectNever('dumpProjectRepoConf');
        $driver->expectNever('push');

        $backend->fork($old_repo, $new_repo, $this->forkPermissions);
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

class Git_Backend_Gitolite_disconnectFromGerrit extends TuleapTestCase {

    private $repo_id = 123;

    public function setUp() {
        parent::setUp();
        $this->repository = aGitRepository()->withId($this->repo_id)->build();
        $this->dao        = mock('GitDao');
        $this->backend    = partial_mock('Git_Backend_Gitolite', array('updateRepoConf'));
        $this->backend->setDao($this->dao);
    }

    public function itAsksToDAOToDisconnectFromGerrit() {
        expect($this->dao)->disconnectFromGerrit($this->repo_id)->once();

        $this->backend->disconnectFromGerrit($this->repository);
    }
}

class Git_Backend_Gitolite_UrlTests extends Git_Backend_GitoliteCommonTest {

    private $backend;
    private $git_plugin;
    private $project;
    private $repository;

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('sys_default_domain', '_dummy_');

        $this->project = new MockProject();
        $this->project->setReturnValue('getUnixName', 'gpig');
        $this->project->setReturnValue('getId', 123);

        $this->repository   = aGitRepository()->withProject($this->project)->withName('bionic')->build();

        $driver             = mock('Git_GitoliteDriver');
        $dao                = mock('GitDao');
        $permissionsManager = mock('PermissionsManager');
        $this->git_plugin   = mock('GitPlugin');
        $this->backend = new Git_Backend_Gitolite($driver, mock('Logger'));
        $this->backend->setDao($dao);
        $this->backend->setPermissionsManager($permissionsManager);
        $this->backend->setGitPlugin($this->git_plugin);
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testGetAccessTypeShouldUseGitoliteSshUser() {
        $repository = aGitRepository()
            ->withName('bionic')
            ->withNamespace('u/johndoe/uber')
            ->withProject($this->project)
            ->build();

        $urls = $this->backend->getAccessUrl($repository);
        $url  = array_shift($urls);

        // url starts by gitolite
        $this->assertPattern('%^ssh://gitolite@%', $url);
    }

    public function testGetAccessTypeShouldIncludesNameSpace() {
        $repository = aGitRepository()
            ->withName('bionic')
            ->withNamespace('u/johndoe/uber')
            ->withProject($this->project)
            ->build();

        $urls = $this->backend->getAccessUrl($repository);
        $url  = array_shift($urls);

        // url ends by the namespace + name
        $this->assertPattern('%/gpig/u/johndoe/uber/bionic\.git$%', $url);
    }

    public function testGetAccessTypeWithoutNameSpace() {
        $urls = $this->backend->getAccessUrl($this->repository);
        $url  = array_shift($urls);

        // url ends by the namespace + name
        $this->assertPattern('%/gpig/bionic\.git$%', $url);
    }

    public function itReturnsNoSSHUrlWhenParameterIsSetToEmpty() {
        stub($this->git_plugin)->getConfigurationParameter('git_ssh_url')->returns('');

        $this->assertArrayEmpty($this->backend->getAccessUrl($this->repository));
    }

    public function itReturnsOnlyHttpUrl() {
        stub($this->git_plugin)->getConfigurationParameter('git_ssh_url')->returns('');
        stub($this->git_plugin)->getConfigurationParameter('git_http_url')->returns('https://stuf');

        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertCount($urls, 1);
        $this->assertTrue(isset($urls['http']));
    }

    public function itReturnsAnHttpUrl() {
        stub($this->git_plugin)->getConfigurationParameter('git_ssh_url')->returns('');
        stub($this->git_plugin)->getConfigurationParameter('git_http_url')->returns('https://stuf');

        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertEqual('https://stuf/gpig/bionic.git', $urls['http']);
    }

    public function itReturnsBothSSHAndHttpUrl() {
        stub($this->git_plugin)->getConfigurationParameter('git_http_url')->returns('https://stuf');

        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertCount($urls, 2);
        $this->assertTrue(isset($urls['ssh']));
        $this->assertTrue(isset($urls['http']));
    }

    public function itReturnsACustomSSHURl() {
        stub($this->git_plugin)->getConfigurationParameter('git_ssh_url')->returns('ssh://git@stuf:2222');

        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertEqual('ssh://git@stuf:2222/gpig/bionic.git', $urls['ssh']);
    }

    public function itReturnsDefaultSSHURl() {
        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertEqual('ssh://gitolite@_dummy_/gpig/bionic.git', $urls['ssh']);
    }

    public function itReplaceServerNameInSSHUrl() {
        stub($this->git_plugin)->getConfigurationParameter('git_ssh_url')->returns('ssh://git@%server_name%:2222');

        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertEqual('ssh://git@_dummy_:2222/gpig/bionic.git', $urls['ssh']);
    }

    public function itReplacesServerNameInHTTPUrl() {
        stub($this->git_plugin)->getConfigurationParameter('git_http_url')->returns('http://%server_name%');

        $urls = $this->backend->getAccessUrl($this->repository);
        $this->assertEqual('http://_dummy_/gpig/bionic.git', $urls['http']);
    }
}
