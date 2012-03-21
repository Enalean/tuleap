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
        if (file_exists($link)) {
            unlink($link);
        }
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
    
    private function checkNameValidation(GitRepository $repo) {
        $this->assertFalse($repo->isNameValid(''));
        $this->assertFalse($repo->isNameValid('/'));
        $this->assertFalse($repo->isNameValid('/jambon'));
        $this->assertFalse($repo->isNameValid('jambon/'));
        $this->assertTrue($repo->isNameValid('jambon'));
        $this->assertTrue($repo->isNameValid('jambon.beurre'));
        $this->assertTrue($repo->isNameValid('jambon-beurre'));
        $this->assertTrue($repo->isNameValid('jambon_beurre'));
        $this->assertFalse($repo->isNameValid('jambon/.beurre'));
        $this->assertFalse($repo->isNameValid('jambon..beurre'));
        $this->assertFalse($repo->isNameValid('jambon...beurre'));
        $this->assertFalse($repo->isNameValid(str_pad('name_with_more_than_255_chars_', 256, '_')));
        $this->assertFalse($repo->isNameValid('repo.git'));
        $this->assertFalse($repo->isNameValid('u/toto'));
    }
    
        
    public function testDeletionPathShouldBeInProjectPath() {
        $repo = new GitRepository();
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/default.conf'));
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/tmp/perms/default.conf'));
        $this->assertTrue($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/tmp/perms/coincoin.git.git'));
        
        $this->assertFalse($repo->isSubPath(dirname(__FILE__).'/_fixtures/perms/', dirname(__FILE__).'/_fixtures/perms/../../default.conf'));
        $this->assertFalse($repo->isSubPath('_fixtures/perms/', 'coincoin'));
    }
    
    public function testDeletionShouldAffectDotGit() {
        $repo = new GitRepository();
        $this->assertTrue($repo->isDotGit('default.git'));
        $this->assertTrue($repo->isDotGit('default.git.git'));
        
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
        $dao = new MockGitDao();
        $dao->setReturnValue('logGitPush', true);
        $repo->setReturnValue('getDao', $dao);

        $this->assertTrue($repo->logGitPush('repo', 'user', 'prj', 1327577111, 3));

        $repo->expectOnce('_getUserManager');
        $um->expectOnce('getUserByIdentifier');
        $dao->expectOnce('logGitPush');
    }

    public function testLogGitPushDaoFail() {
        $user = new MockUser();
        $user->setReturnValue('getId', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserByIdentifier', $user);
        $repo = new GitRepositoryTestVersion();
        $repo->setReturnValue('_getUserManager', $um);
        $dao = new MockGitDao();
        $dao->setReturnValue('logGitPush', false);
        $repo->setReturnValue('getDao', $dao);

        $this->assertFalse($repo->logGitPush('repo', 'user', 'prj', 1327577111, 3));

        $repo->expectOnce('_getUserManager');
        $um->expectOnce('getUserByIdentifier');
        $user->expectOnce('getId');
        $dao->expectOnce('logGitPush');
    }

    public function testLogGitPushSuccess() {
        $user = new MockUser();
        $user->setReturnValue('getId', 2);
        $um = new MockUserManager();
        $um->setReturnValue('getUserByIdentifier', $user);
        $repo = new GitRepositoryTestVersion();
        $repo->setReturnValue('_getUserManager', $um);
        $dao = new MockGitDao();
        $dao->setReturnValue('logGitPush', true);
        $repo->setReturnValue('getDao', $dao);

        $this->assertTrue($repo->logGitPush('repo', 'user', 'prj', 1327577111, 3));

        $repo->expectOnce('_getUserManager');
        $um->expectOnce('getUserByIdentifier');
        $user->expectOnce('getId');
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
        $backend = new MockGit_Backend_Gitolite();
        $project = new Mockproject();
        $project->setReturnValue('getUnixName', 'tulip');
        
        $repo    = new GitRepository();
        $repo->setBackend($backend);
        $repo->setProject($project);
        
        $namespace = "toto/tata";
        $clone = $this->_aGitRepoWith($user, $repo, $namespace, $backend, GitRepository::REPO_SCOPE_INDIVIDUAL);
        $clone->setProject($repo->getProject());
        $clone->setPath(unixPathJoin(array($project->getUnixName(), $namespace, $repo->getName())).'.git');

        $backend->expectOnce('fork', array(new EqualExpectation($repo), new EqualExpectation($clone)));

        $repo->fork($user, $namespace, GitRepository::REPO_SCOPE_INDIVIDUAL, $project);
    }
    public function testForkCrossProjectClonesByChangingTheProjectAndPath() {
        $user = $this->_newUser("sandra");
        $backend = new MockGit_Backend_Gitolite();
        $project = new Mockproject();
        $project->setReturnValue('getUnixName', 'tulip');

        $to_project = new Mockproject();
        $to_project->setReturnValue('getUnixName', 'blabla');

        $repo    = new GitRepository();
        $repo->setBackend($backend);
        $repo->setProject($project);
        
        $expectedRepo = $this->_aGitRepoWith($user, $repo, '', $backend, GitRepository::REPO_SCOPE_PROJECT);
        $expectedRepo->setProject($to_project);
        $expectedRepo->setPath(unixPathJoin(array($to_project->getUnixName(), '', $repo->getName())).'.git');

        $backend->expectOnce('fork', array(new EqualExpectation($repo), new EqualExpectation($expectedRepo)));
        $repo->fork($user, '', GitRepository::REPO_SCOPE_PROJECT, $to_project);
    }
    private function _aGitRepoWith($user, $repo, $namespace, $backend, $scope) {
        $clone = new GitRepository();
        $clone->setCreator($user);
        $clone->setNamespace($namespace);
        $clone->setBackend($backend);
        $clone->setParent($repo);
        $clone->setScope($scope);
        $clone->setName($repo->getName());
        return $clone;
    }
    
    public function _newUser($name) {
        $user = new User(array('language_id' => 1));
        $user->setUserName($name);
        return $user;
    }
    
    public function testCanBeDeletedWithDotGitDotGitRepositoryShouldSucceed() {
        $repo = $this->getRepositoryForDeletionTesting();
        $repo->setPath('perms/coincoin.git.git');

        $this->assertTrue($repo->canBeDeleted());
    }

    public function testCanBeDeletedWithWrongRepositoryPathShouldFail() {
        $repo = $this->getRepositoryForDeletionTesting();
        $repo->setPath('perms/coincoin');

        $this->assertFalse($repo->canBeDeleted());
    }
    
    protected function getRepositoryForDeletionTesting() {
        $backend = new MockGitBackend();
        $backend->setReturnValue('getGitRootPath', dirname(__FILE__).'/_fixtures');

        $project = new MockProject();
        $project->setReturnValue('getUnixName', 'perms');

        $repo = new GitRepository();
        $repo->setBackend($backend);
        $repo->setProject($project);
        
        return $repo;
    }
    
    public function testGetFullName_appendsNameSpaceToName() {
        $repo = $this->_GivenARepositoryWithNameAndNamespace('tulip', null);
        $this->assertEqual('tulip', $repo->getFullName());
        
        $repo = $this->_GivenARepositoryWithNameAndNamespace('tulip', 'u/johan');
        $this->assertEqual('u/johan/tulip', $repo->getFullName());
    }

    protected function _GivenARepositoryWithNameAndNamespace($name, $namespace) {
        $repo = new GitRepository();
        $repo->setName($name);
        $repo->setNamespace($namespace);
        return $repo;
    }

    public function testProjectRepositoryDosNotBelongToUser() {
        $user = new User(array('language_id' => 1));
        $user->setUserName('sandra');
        
        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_PROJECT);
        
        $this->assertFalse($repo->belongsTo($user));
    }
    
    public function testUserRepositoryBelongsToUser() {
        $user = new User(array('language_id' => 1));
        $user->setUserName('sandra');
        
        $repo = new GitRepository();
        $repo->setCreator($user);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);
        
        $this->assertTrue($repo->belongsTo($user));
    }
    public function testUserRepositoryDoesNotBelongToAnotherUser() {
        $creator = new User(array('language_id' => 1));
        $creator->setId(123);
        
        $user = new User(array('language_id' => 1));
        $user->setId(456);
        
        $repo = new GitRepository();
        $repo->setCreator($creator);
        $repo->setScope(GitRepository::REPO_SCOPE_INDIVIDUAL);
        
        $this->assertFalse($repo->belongsTo($user));
    }
}

?>