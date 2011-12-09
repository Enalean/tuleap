<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/GitRepository.class.php');
Mock::generatePartial('GitRepository', 'GitRepositoryTestVersion', array('_getUserManager', 'getRepositoryIDByName', 'getDao'));
Mock::generatePartial('GitRepository', 'GitRepositorySecondTestVersion', array('_getProjectManager', 'getDao'));
require_once(dirname(__FILE__).'/../include/Git_Backend_Gitolite.class.php');
Mock::generate('Git_Backend_Gitolite');
require_once(dirname(__FILE__).'/../include/GitBackend.class.php');
Mock::generate('GitBackend');
require_once(dirname(__FILE__).'/../include/GitDao.class.php');
Mock::generate('GitDao');
Mock::generate('UserManager');
Mock::generate('User');
Mock::generate('ProjectManager');
Mock::generate('Project');
Mock::generate('DataAccessResult');

class GitRepositoryTest extends UnitTestCase {

    

    
    public function setUp() {
        $link =dirname(__FILE__).'/_fixtures/tmp/perms';
        if (file_exists($link))
            unlink($link);
        symlink(dirname(__FILE__).'/_fixtures/perms', $link);
    }
    
    public function tearDown() {
        unlink(dirname(__FILE__).'/_fixtures/tmp/perms');
    }

    public function test_isNameValid() {
        $gitolite = new MockGit_Backend_Gitolite();
        $gitolite->setReturnValue('getAllowedCharsInNamePattern', 'a-zA-Z0-9/_.-');
        
        $gitshell = new MockGitBackend();
        $gitshell->setReturnValue('getAllowedCharsInNamePattern', 'a-zA-Z0-9_.-');
        
        $repo = new GitRepository();
        
        $repo->setBackend($gitolite);
        $this->checkNameValidation($repo);
        $this->assertTrue($repo->isNameValid('jambon/beurre'));
       
        $repo->setBackend($gitshell);
        $this->checkNameValidation($repo);
        $this->assertFalse($repo->isNameValid('jambon/beurre'));
    }
    
    private function checkNameValidation($repo) {
        $this->assertFalse($repo->isNameValid(''));
        $this->assertTrue($repo->isNameValid('jambon'));
        $this->assertTrue($repo->isNameValid('jambon.beurre'));
        $this->assertTrue($repo->isNameValid('jambon-beurre'));
        $this->assertTrue($repo->isNameValid('jambon_beurre'));
        $this->assertFalse($repo->isNameValid('jambon/.beurre'));
        $this->assertFalse($repo->isNameValid('jambon..beurre'));
        $this->assertFalse($repo->isNameValid('jambon...beurre'));
        $this->assertFalse($repo->isNameValid(str_pad('name_with_more_than_255_chars_', 256, '_')));
    }
    
        
    public function testDeletionPathShouldBeInProjectPath() {
        $repo = new GitRepository();
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/default.conf'));
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/tmp/perms/default.conf'));
        
        $this->assertFalse($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/../../default.conf'));
        $this->assertFalse($repo->isSubPath('_fixtures/perms/', 'coincoin'));
    }
    
    public function testDeletionShoultAffectDotGit() {
        $repo = new GitRepository();
        $this->assertTrue($repo->isDotGit('default.git'));
        $this->assertFalse($repo->isDotGit('default.conf'));
        $this->assertFalse($repo->isDotGit('d'));
        $this->assertFalse($repo->isDotGit('defaultgit'));
        $this->assertFalse($repo->isDotGit('default.git.old'));
    }

    public function testLogGitPushNoUser() {
        $um = new MockUserManager();
        $um->setReturnValue('getUserByIdentifier', null);
        $repo = new GitRepositoryTestVersion();
        $repo->setReturnValue('_getUserManager', $um);
        $repo->setReturnValue('getRepositoryIDByName', 1);
        $dao = new MockGitDao();
        $dao->setReturnValue('logGitPush', true);
        $repo->setReturnValue('getDao', $dao);

        $this->assertTrue($repo->logGitPush('repo', 'user', 'prj', 3));

        $repo->expectOnce('_getUserManager');
        $um->expectOnce('getUserByIdentifier');
        $repo->expectOnce('getRepositoryIDByName');
        $dao->expectOnce('logGitPush');
    }

    public function testLogGitPushDaoFail() {
        $user = new MockUser();
        $user->setReturnValue('getId', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserByIdentifier', $user);
        $repo = new GitRepositoryTestVersion();
        $repo->setReturnValue('_getUserManager', $um);
        $repo->setReturnValue('getRepositoryIDByName', 1);
        $dao = new MockGitDao();
        $dao->setReturnValue('logGitPush', false);
        $repo->setReturnValue('getDao', $dao);

        $this->assertFalse($repo->logGitPush('repo', 'user', 'prj', 3));

        $repo->expectOnce('_getUserManager');
        $um->expectOnce('getUserByIdentifier');
        $user->expectOnce('getId');
        $repo->expectOnce('getRepositoryIDByName');
        $dao->expectOnce('logGitPush');
    }

    public function testLogGitPushSuccess() {
        $user = new MockUser();
        $user->setReturnValue('getId', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserByIdentifier', $user);
        $repo = new GitRepositoryTestVersion();
        $repo->setReturnValue('_getUserManager', $um);
        $repo->setReturnValue('getRepositoryIDByName', 1);
        $dao = new MockGitDao();
        $dao->setReturnValue('logGitPush', true);
        $repo->setReturnValue('getDao', $dao);

        $this->assertTrue($repo->logGitPush('repo', 'user', 'prj', 3));

        $repo->expectOnce('_getUserManager');
        $um->expectOnce('getUserByIdentifier');
        $user->expectOnce('getId');
        $repo->expectOnce('getRepositoryIDByName');
        $dao->expectOnce('logGitPush');
    }

    public function testGetRepositoryIDByNameSuccess() {
        $repo = new GitRepositorySecondTestVersion();
        $pm = new MockProjectManager();
        $project = new Mockproject();
        $repo->setReturnValue('_getProjectManager', $pm);
        $pm->setReturnValue('getProjectByUnixName', $project);
        $dao = new MockGitDao();
        $repo->setReturnValue('getDao', $dao);
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', false);
        $dar->setReturnValue('getRow', array ("repository_id" => 48));
        $dao->setReturnValue('getProjectRepositoryByName', $dar);

        $this->assertEqual($repo->getRepositoryIDByName('repo', 'prj'), 48);

        $repo->expectOnce('_getProjectManager');
        $dao->expectOnce('getProjectRepositoryByName');
        $project->expectOnce('getID');
        $dar->expectOnce('isError');
        $dar->expectOnce('getRow');
    }

    public function testGetRepositoryIDByNameNoRepository() {
        $repo = new GitRepositorySecondTestVersion();
        $pm = new MockProjectManager();
        $project = new Mockproject();
        $repo->setReturnValue('_getProjectManager', $pm);
        $pm->setReturnValue('getProjectByUnixName', $project);
        $dao = new MockGitDao();
        $repo->setReturnValue('getDao', $dao);
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('isError', true);
        $dao->setReturnValue('getProjectRepositoryByName', $dar);

        $this->assertEqual($repo->getRepositoryIDByName('repo', 'prj'), 0);

        $repo->expectOnce('_getProjectManager');
        $dao->expectOnce('getProjectRepositoryByName');
        $project->expectOnce('getID');
        $dar->expectOnce('isError');
        $dar->expectNever('getRow');
    }

    public function testGetRepositoryIDByNameNoProjectID() {
        $repo = new GitRepositorySecondTestVersion();
        $pm = new MockProjectManager();
        $project = new Mockproject();
        $repo->setReturnValue('_getProjectManager', $pm);
        $pm->setReturnValue('getProjectByUnixName', false);

        $this->assertIdentical($repo->getRepositoryIDByName('repo', 'prj'), 0);

        $repo->expectOnce('_getProjectManager');
        $project->expectNever('getID');
    }
    
    public function testForkCreatesAnewRepoAndPassesItToTheBackend() {
        $user = $this->_newUser("sandra");
        $manager = new MockUserManager();
        $manager->setReturnValue('getCurrentUser', $user);
        $backend = new MockGit_Backend_Gitolite();
        $repo    = new GitRepository($manager);        
        $repo->setBackend($backend);
        $clone = $this->_aGitRepoWith($user, $repo);
        
        $namespace = "toto/tata";

        $backend->expectOnce('fork', array(new EqualExpectation($clone), $namespace));

        $repo->fork($namespace);
    }
    
    private function _aGitRepoWith($user, $repo) {
        $clone = new GitRepository();
        $clone->setCreator($user);
        $clone->setName($repo->getName());
        $clone->setParent($repo);
        return $clone;
    }
    
    public function _newUser($name) {
        $user = new User(array('language_id' => 1));
        $user->setUserName($name);
        return $user;
    }
    
}

?>
