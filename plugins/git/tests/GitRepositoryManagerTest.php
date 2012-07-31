<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../include/GitRepositoryManager.class.php';
require_once dirname(__FILE__).'/builders/aGitRepository.php';

class GitRepositoryManager_DeleteAllRepositoriesTest extends TuleapTestCase {
    private $project;
    private $git_repository_manager;

    public function setUp() {
        parent::setUp();
        $this->project_id           = 42;
        $this->project              = stub('Project')->getID()->returns($this->project_id);
        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->system_event_manager = mock('SystemEventManager');

        $this->git_repository_manager = new GitRepositoryManager($this->repository_factory, $this->system_event_manager);
    }

    public function itDeletesNothingWhenThereAreNoRepositories() {
        stub($this->repository_factory)->getAllRepositories()->returns(array());
        $this->repository_factory->expectOnce('getAllRepositories', array($this->project));

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }

    public function itDeletesEachRepository() {
        $repository_1_id = 1;
        $repository_1    = mock('GitRepository');
        $repository_1->expectOnce('forceMarkAsDeleted');
        stub($repository_1)->getId()->returns($repository_1_id);
        stub($repository_1)->getProjectId()->returns($this->project);

        $repository_2_id = 2;
        $repository_2    = mock('GitRepository');
        $repository_2->expectOnce('forceMarkAsDeleted');
        stub($repository_2)->getId()->returns($repository_2_id);
        stub($repository_2)->getProjectId()->returns($this->project);

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

        stub($this->repository_factory)->getAllRepositories()->returns(array($repository_1, $repository_2));

        $this->git_repository_manager->deleteProjectRepositories($this->project);
    }
}

class GitRepositoryManager_IsRepositoryNameAlreadyUsedTest extends TuleapTestCase {
    private $factory;
    private $project;
    private $manager;
    private $project_id;
    private $project_name;

    public function setUp() {
        parent::setUp();
        $this->project_id   = 12;
        $this->project_name = 'garden';
        $this->project      = mock('Project');
        stub($this->project)->getID()->returns($this->project_id);
        stub($this->project)->getUnixName()->returns($this->project_name);

        $this->factory    = mock('GitRepositoryFactory');
        $this->manager    = new GitRepositoryManager($this->factory, mock('SystemEventManager'));
    }

    private function aRepoWithPath($path) {
        return aGitRepository()->withPath($this->project_name.'/'.$path.'.git')->withProject($this->project)->build();
    }

    public function itCannotCreateARepositoryWithSamePath() {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('bla'))
        );
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function itCannotCreateARepositoryWithSamePathThatIsNotAtRoot() {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('foo/bla'))
        );
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla')));
    }

    public function itForbidCreationOfRepositoriesWhenPathAlreadyExists() {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('bla'))
        );

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/zoum/zaz')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('zoum/bla/top')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('blafoo')));
    }

    public function itForbidCreationOfRepositoriesWhenPathAlreadyExistsAndHasParents() {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('foo/bla'))
        );

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bla/stuff/zaz')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla/foo')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('bla')));
    }

    public function itForbidCreationWhenNewRepoIsInsideExistingPath() {
        stub($this->factory)->getAllRepositories($this->project)->returns(
            array($this->aRepoWithPath('foo/bar/bla'))
        );

        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo')));
        $this->assertTrue($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar')));

        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/bar/zorg')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foo/zorg')));
        $this->assertFalse($this->manager->isRepositoryNameAlreadyUsed($this->aRepoWithPath('foobar/zorg')));
    }
}

class GitRepositoryManager_CreateTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->backend    = mock('Git_Backend_Interface');
        $this->repository = mock('GitRepository');
        stub($this->repository)->getBackend()->returns($this->backend);

        $this->manager = partial_mock('GitRepositoryManager', array('isRepositoryNameAlreadyUsed'));
    }

    public function itThrowAnExceptionIfRepositoryNameCannotBeUsed() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(true);
        stub($this->repository)->isNameValid()->returns(true);

        $this->expectException();
        $this->manager->create($this->repository);
    }

    public function itThrowsAnExceptionIfNameIsNotCompliantToBackendStandards() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->repository)->isNameValid()->returns(false);

        $this->expectException();
        $this->manager->create($this->repository);
    }

    public function itCreatesOnRepositoryBackendIfEverythingIsClean() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->repository)->isNameValid()->returns(true);

        $this->backend->expectOnce('createReference');
        $this->manager->create($this->repository);
    }
}

class GitRepositoryManager_ForkTest extends TuleapTestCase {
    private $backend;
    private $repository;
    private $user;
    private $project;
    private $manager;
    
    public function setUp() {
        parent::setUp();
        $this->backend    = mock('Git_Backend_Gitolite');
        $this->repository = mock('GitRepository');
        stub($this->repository)->getBackend()->returns($this->backend);

        $this->user = stub('User')->getId()->returns(123);
        
        $this->project = stub('Project')->getId()->returns(101);
        
        $this->manager = partial_mock('GitRepositoryManager', array('isRepositoryNameAlreadyUsed'));
    }

    public function itThrowAnExceptionIfRepositoryNameCannotBeUsed() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(true);

        $this->expectException();
        $this->manager->fork($this->repository, mock('User'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, mock('Project'));
    }

    public function itForkInRepositoryBackendIfEverythingIsClean() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);

        $this->backend->expectOnce('fork');
        $this->manager->fork($this->repository, mock('User'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, mock('Project'));
    }
    
    function testForkIndividualRepositories() {
        $path  = 'toto';
        
        $backend = mock('Git_Backend_Gitolite');
        $backend->expectOnce('fork');
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('userCanRead', true, array($this->user));
        $repo->setReturnValue('isNameValid', true, array($path));
        stub($repo)->getBackend()->returns($backend);
                
        $this->manager->forkRepositories(array($repo), $this->project, $path, null, $this->user);
    }

    function testClonesManyInternalRepositories() {
        $path  = 'toto';
        $group_id = 101;

        $backend = mock('Git_Backend_Gitolite');
        
        $repo_ids = array('1', '2', '3');
        
        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($this->user));
            $repo->setReturnValue('isNameValid', true, array($path));
            stub($repo)->getBackend()->returns($backend);
            $repos[] = $repo;
        }
        
        $backend->expectCallCount('fork', 3);
        
        $this->manager->forkRepositories($repos, $this->project, $path, null, $this->user);
    }
    function testCloneManyCrossProjectRepositories() {
        
        $path  = '';
         
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isMember', true);

        $project_id = 2;
        $to_project = new MockProject();
        $to_project->setReturnValue('getId', $project_id);

        $backend = mock('Git_Backend_Gitolite');
        
        $repo_ids = array('1', '2', '3');
        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($user));
            stub($repo)->getBackend()->returns($backend);
            $repos[] = $repo;
        }
        
        $backend->expectCallCount('fork', 3);
        
        $this->manager->forkRepositories($repos, $to_project, '', null, $user);
    }
    
    function testWhenNoRepositorySelectedItAddsWarning() {
        $group_id = 101;

        $repos = array();
        $user = new MockUser();
        
        $project = new MockProject();
        $project->setReturnValue('getId', $group_id);
                
        
        
        //$action->getController()->expectOnce('addError', array('actions_no_repository_forked'));
        
        $this->manager->forkRepositories($repos, $project, '', null, $user);
    }
    
    function testClonesOneRepository() {
        $id = '1';
        $group_id = 101;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        
        $project = new MockProject();
        $project->setReturnValue('getId', $group_id);
        $project->setReturnValue('getUnixName', '');
        
        $backend = mock('Git_Backend_Gitolite');
        $backend->expectOnce('fork');
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $id);
        $repo->setReturnValue('userCanRead', true, array($user));
        stub($repo)->getBackend()->returns($backend);
        $repos = array($repo);
        
        
        $this->manager->forkRepositories($repos, $project, '', null, $user);
    }
    

    function testDoesntCloneUnreadableRepos() {
        $repositories = array('1', '2', '3');
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $repos = $this->getRepoCollectionUnreadableFor($repositories, $user);
        
        $to_project = new MockProject();
        $to_project->setReturnValue('getId', 2);
        
        
        $this->manager->forkRepositories($repos, $to_project, '', null, $user);
    }
    
    protected function getRepoCollectionUnreadableFor($repo_ids, $user) {
        $return = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', false, array($user));
            $repo->expectNever('fork');
            $return[] = $repo;
        }
        return $return;
    }

    
    public function testForkCrossProjectsRedirectToCrossProjectGitRepositories() {
        $repo_id = '1';
        $project_id = 2;
        
        $user = new MockUser();
        $user->setReturnValue('getId', 123);
        $user->setReturnValue('isMember', true, array($project_id, 'A'));
        $to_project = new MockProject();
        $to_project->setReturnValue('getId', $project_id);
        
        $backend = mock('Git_Backend_Gitolite');
        $backend->expectOnce('fork');
        
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $repo_id);
        $repo->setReturnValue('userCanRead', true, array($user));
        stub($repo)->getBackend()->returns($backend);
        $repos = array($repo);
        
        
        
        
        //$action->getController()->expectOnce('addInfo', array('successfully_forked'));
                
        $this->manager->forkRepositories($repos, $to_project, '', null, $user);
    }

    function testForkShouldNotCloneAnyNonExistentRepositories() {
        $backend = mock('Git_Backend_Gitolite');
        $backend->expectOnce('fork');

        $project = new MockProject();
        $repo    = $this->GivenARepository(123);
        stub($repo)->getBackend()->returns($backend);
        
        $user   = new MockUser();
        
        $this->manager->forkRepositories(array($repo, null), $project, null, null, $user);
    }
    
    function testForkShouldIgnoreAlreadyExistingRepository() {
        $backend = mock('Git_Backend_Gitolite');
        $backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $backend->throwAt(0, 'fork', new GitRepositoryAlreadyExistsException(''));
        
        $errorMessage = 'Repository Xxx already exists';
        $GLOBALS['Language']->setReturnValue('getText', $errorMessage);
        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', $errorMessage));
        $repo1 = $this->GivenARepository(123);
        stub($repo1)->getBackend()->returns($backend);
        
        $repo2 = $this->GivenARepository(456);
        stub($repo2)->getBackend()->returns($backend);

        $this->forkRepositories(array($repo1, $repo2));
    }
    
    function testForkShouldTellTheUserIfTheRepositoryAlreadyExists() {
        $backend = mock('Git_Backend_Gitolite');
        
        $errorMessage = 'Repository Xxx already exists';
        $GLOBALS['Language']->setReturnValue('getText', $errorMessage);
        $repo2 = $this->GivenARepository(456);
        stub($repo2)->getBackend()->returns($backend);
        
        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', $errorMessage));
        $backend->throwAt(1, 'fork', new GitRepositoryAlreadyExistsException($repo2->getName()));

        $repo1 = $this->GivenARepository(123);
        stub($repo1)->getBackend()->returns($backend);
        
        $this->forkRepositories(array($repo1, $repo2));
    }
    
    function testForkGiveInformationAboutUnexpectedErrors() {
        $backend = mock('Git_Backend_Gitolite');
        
        $errorMessage = 'user gitolite doesnt exist';
        $repo2 = $this->GivenARepository(456);
        $repo2->setName('megaRepoGit');
        stub($repo2)->getBackend()->returns($backend);
        
        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', "Got an unexpected error while forking ".$repo2->getName().": ".$errorMessage));
        $backend->throwAt(1, 'fork', new Exception($errorMessage));
        
        $repo1 = $this->GivenARepository(123);
        stub($repo1)->getBackend()->returns($backend);
        
        $this->forkRepositories(array($repo1, $repo2));
    }
    
    function testForkAssertNamespaceIsValid() {
        $repo = new MockGitRepository();
        $repo->setReturnValue('isNameValid', false);
        $repo->expectNever('fork');
        
        $repo->setReturnValue('isNameValid', false);
        
        $GLOBALS['Response']->expectOnce('addFeedback', array('error', '*'));
        
        $this->forkRepositories(array($repo), '^toto/pouet');
    }
    
    private function GivenARepository($id) {
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $id);
        $repo->setReturnValue('userCanRead', true);
        $repo->setReturnValue('isNameValid', true);
        return $repo;
    }

    public function forkRepositories($repositories, $namespace=null) {
        $user    = new MockUser();
        $project = new MockProject();
        
        $this->manager->forkRepositories($repositories, $project, $namespace, null, $user);
        
    }
}

?>
