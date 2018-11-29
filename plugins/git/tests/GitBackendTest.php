<?php
/*
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generatePartial('GitBackend', 'GitBackendTestVersion', array('getDao', 'getDriver', 'getSystemEventManager'));
Mock::generatePartial('GitBackend', 'GitBackend4SetUp', array('getDao', 'getDriver', 'deployPostReceive', 'setRepositoryPermissions', 'changeRepositoryAccess'));

Mock::generate('GitDriver');
Mock::generate('GitRepository');
Mock::generate('GitDao');
Mock::generate('Project');
Mock::generate('SystemEventManager');

class GitBackendTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->http_request = mock('HTTPRequest');
        HTTPRequest::setInstance($this->http_request);

        $this->fixturesPath = dirname(__FILE__).'/_fixtures';

        $git_plugin        = stub('GitPlugin')->areFriendlyUrlsActivated()->returns(false);
        $this->url_manager = new Git_GitRepositoryUrlManager($git_plugin);
    }

    public function tearDown() {
        @unlink($this->fixturesPath.'/tmp/hooks/post-receive');
        HTTPRequest::clearInstance();

        parent::tearDown();
    }

    public function testAddMailingShowRev() {
        stub($this->http_request)->getServerUrl()->returns('https://localhost');

        $prj = new MockProject($this);
        $prj->setReturnValue('getId', 1750);
        $prj->setReturnValue('getUnixName', 'prj');

        $repo = new GitRepository();
        $repo->setPath('prj/repo.git');
        $repo->setName('repo');
        $repo->setProject($prj);
        $repo->setId(290);

        $driver = new MockGitDriver($this);
        $driver->expectOnce('setConfig', array('/var/lib/codendi/gitroot/prj/repo.git', 'hooks.showrev', "t=%s; git show --name-status --pretty='format:URL:    https://localhost/plugins/git/prj/repo?a=commitdiff&h=%%H%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b' \$t"));

        $backend = new GitBackendTestVersion($this);
        $backend->setUp($this->url_manager);
        $backend->setGitRootPath(Git_Backend_Interface::GIT_ROOT_PATH);
        $backend->setReturnValue('getDriver', $driver);

        $backend->setUpMailingHook($repo);
    }

    public function testArchiveCreatesATarGz() {
        $this->GivenThereIsARepositorySetUp();
        
        $project = new MockProject();
        $project->setReturnValue('getUnixName', 'zorblub');
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('getPath', 'gitolite-admin-ref');
        $repo->setReturnValue('getName', 'gitolite-admin-ref');
        $repo->setReturnValue('getDeletionDate', '2012-01-26');
        $repo->setReturnValue('getProject', $project);
        
        $backend = new GitBackendTestVersion();
        $backend->setGitRootPath($this->_tmpDir);
        $backend->setGitBackupDir($this->backupDir);
        $backend->archive($repo);
        
        $this->ThenCleanTheWorkspace();
    }
    
    private function GivenThereIsARepositorySetUp() {
        // Copy the reference to save time & create symlink because
        // git is very sensitive to path you are using. Just symlinking
        // spots bugs
        $this->cwd           = getcwd();
        $this->_tmpDir       = '/tmp';
        $this->_fixDir       = dirname(__FILE__).'/_fixtures';
        $this->_glAdmDirRef  = $this->_tmpDir.'/gitolite-admin-ref';
        $this->backupDir     = $this->_tmpDir.'/backup';
        system('tar -xf '. $this->_fixDir.'/gitolite-admin-ref' .'.tar --directory '.$this->_tmpDir);
        mkdir($this->backupDir);
    }
    
    private function ThenCleanTheWorkspace() {
        system('rm -rf '. $this->_glAdmDirRef);
        system('rm -rf '. $this->backupDir);
        chdir($this->cwd);
    }
}

?>