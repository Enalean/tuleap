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

require_once 'bootstrap.php';

class GitRepositoryManager_DeleteAllRepositoriesTest extends TuleapTestCase {
    private $project;
    private $git_repository_manager;
    private $dao;

    public function setUp() {
        parent::setUp();
        $this->project_id           = 42;
        $this->project              = stub('Project')->getID()->returns($this->project_id);
        $this->repository_factory   = mock('GitRepositoryFactory');
        $this->system_event_manager = mock('SystemEventManager');
        $this->dao                  = mock('GitDao');

        $this->git_repository_manager = new GitRepositoryManager($this->repository_factory, $this->system_event_manager, $this->dao);
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
    private $dao;

    public function setUp() {
        parent::setUp();
        $this->project_id   = 12;
        $this->project_name = 'garden';
        $this->project      = mock('Project');
        stub($this->project)->getID()->returns($this->project_id);
        stub($this->project)->getUnixName()->returns($this->project_name);
        $this->dao = mock('GitDao');

        $this->factory    = mock('GitRepositoryFactory');
        $this->manager    = new GitRepositoryManager($this->factory, mock('SystemEventManager'), $this->dao);
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

    private $creator;
    private $dao;
    private $system_event_manager;

    public function setUp() {
        parent::setUp();
        $this->creator    = mock('GitRepositoryCreator');
        $this->repository = mock('GitRepository');

        $this->system_event_manager = mock('SystemEventManager');
        $this->dao                  = mock('GitDao');

        $this->manager = partial_mock(
            'GitRepositoryManager',
            array('isRepositoryNameAlreadyUsed'),
            array(
                mock('GitRepositoryFactory'),
                $this->system_event_manager,
                $this->dao
            )
        );
    }

    public function itThrowAnExceptionIfRepositoryNameCannotBeUsed() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(true);
        stub($this->creator)->isNameValid()->returns(true);

        $this->expectException();
        $this->manager->create($this->repository, $this->creator);
    }

    public function itThrowsAnExceptionIfNameIsNotCompliantToBackendStandards() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(false);

        $this->expectException();
        $this->manager->create($this->repository, $this->creator);
    }

    public function itCreatesOnRepositoryBackendIfEverythingIsClean() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(true);

        expect($this->dao)->save($this->repository)->once();
        $this->manager->create($this->repository, $this->creator);
    }

    public function itScheduleAnEventToCreateTheRepositoryInGitolite() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(true);

        stub($this->dao)->save()->returns(54);

        expect($this->system_event_manager)->createEvent(
            'GIT_REPO_UPDATE',
            54,
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        )->once();

        $this->manager->create($this->repository, $this->creator);
    }

    public function itSetRepositoryIdOnceSavedInDatabase() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->creator)->isNameValid()->returns(true);

        stub($this->dao)->save()->returns(54);

        expect($this->repository)->setId(54)->once();

        $this->manager->create($this->repository, $this->creator);
    }
}

class GitRepositoryManager_ForkTest extends TuleapTestCase {
    private $backend;
    private $repository;
    private $user;
    private $project;
    private $manager;
    private $forkPermissions;

    public function setUp() {
        parent::setUp();
        $this->backend    = mock('Git_Backend_Gitolite');
        $this->repository = mock('GitRepository');
        stub($this->repository)->getBackend()->returns($this->backend);

        $this->user    = stub('PFUser')->getId()->returns(123);
        $this->project = stub('Project')->getId()->returns(101);
        $this->manager = partial_mock('GitRepositoryManager', array('isRepositoryNameAlreadyUsed'));
        $this->forkPermissions = array();
    }

    public function itThrowAnExceptionIfRepositoryNameCannotBeUsed() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(true);

        $this->expectException();
        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itForkInRepositoryBackendIfEverythingIsClean() {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);

        $this->backend->expectOnce('fork');
        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    function testForkIndividualRepositories() {
        $path  = 'toto';
        $this->repository->setReturnValue('userCanRead', true, array($this->user));
        $this->backend->setReturnValue('isNameValid', true, array($path));

        $this->backend->expectOnce('fork');
        $this->manager->forkRepositories(array($this->repository), $this->project, $this->user, $path, null, $this->forkPermissions);
    }

    function testClonesManyInternalRepositories() {
        $namespace  = 'toto';
        $repo_ids = array('1', '2', '3');

        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($this->user));
            $this->backend->setReturnValue('isNameValid', true, array($namespace));
            stub($repo)->getBackend()->returns($this->backend);
            $repos[] = $repo;
        }

        $this->backend->expectCallCount('fork', 3);
        $this->manager->forkRepositories($repos, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }

    function testCloneManyCrossProjectRepositories() {
        $this->user->setReturnValue('isMember', true);
        $to_project = stub('Project')->getId()->returns(2);

        $repo_ids = array('1', '2', '3');
        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($this->user));
            stub($repo)->getBackend()->returns($this->backend);
            $repos[] = $repo;
        }

        $this->backend->expectCallCount('fork', 3);
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    function testWhenNoRepositorySelectedItAddsWarning() {
        $this->expectException();
        $this->manager->forkRepositories(array(), $this->project, $this->user, '', null, $this->forkPermissions);
    }

    function testClonesOneRepository() {
        $this->repository->setReturnValue('getId', 1);
        $this->repository->setReturnValue('userCanRead', true, array($this->user));

        $this->backend->expectOnce('fork');
        $this->manager->forkRepositories(array($this->repository), $this->project, $this->user, '', null, $this->forkPermissions);
    }

    function testDoesntCloneUnreadableRepos() {
        $repos = $this->getRepoCollectionUnreadableFor(array('1', '2', '3'), $this->user);
        $to_project = stub('Project')->getId()->returns(2);

        $this->backend->expectNever('fork');
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    protected function getRepoCollectionUnreadableFor($repo_ids, $user) {
        $return = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', false, array($user));
            $return[] = $repo;
        }
        return $return;
    }

    public function testForkCrossProjectsRedirectToCrossProjectGitRepositories() {
        $repo_id = '1';
        $project_id = 2;

        stub($this->user)->isMember($project_id, 'A')->returns(true);
        $to_project = stub('Project')->getId()->returns($project_id);

        $this->backend->expectOnce('fork');

        $this->repository->setReturnValue('getId', $repo_id);
        $this->repository->setReturnValue('userCanRead', true, array($this->user));

        $repos = array($this->repository);

        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    function testForkShouldNotCloneAnyNonExistentRepositories() {
        $this->backend->expectOnce('fork');

        $repo = $this->GivenARepository(123);

        $this->manager->forkRepositories(array($repo, null), $this->project, $this->user, null, null, $this->forkPermissions);
    }

    function testForkShouldIgnoreAlreadyExistingRepository() {
        $this->backend->throwAt(0, 'fork', new GitRepositoryAlreadyExistsException(''));

        $errorMessage = 'Repository Xxx already exists';
        $GLOBALS['Language']->setReturnValue('getText', $errorMessage);
        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', $errorMessage));
        $repo1 = $this->GivenARepository(123);

        $repo2 = $this->GivenARepository(456);

        $this->backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $this->forkRepositories(array($repo1, $repo2));
    }

    function testForkShouldTellTheUserIfTheRepositoryAlreadyExists() {
        $errorMessage = 'Repository Xxx already exists';
        $GLOBALS['Language']->setReturnValue('getText', $errorMessage);
        $repo2 = $this->GivenARepository(456);

        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', $errorMessage));
        $this->backend->throwAt(1, 'fork', new GitRepositoryAlreadyExistsException($repo2->getName()));

        $repo1 = $this->GivenARepository(123);

        $this->backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $this->forkRepositories(array($repo1, $repo2));
    }

    function testForkGiveInformationAboutUnexpectedErrors() {
        $errorMessage = 'user gitolite doesnt exist';
        $repo2 = $this->GivenARepository(456);
        $repo2->setName('megaRepoGit');

        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', "Got an unexpected error while forking ".$repo2->getName().": ".$errorMessage));
        $this->backend->throwAt(1, 'fork', new Exception($errorMessage));

        $repo1 = $this->GivenARepository(123);

        $this->backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $this->forkRepositories(array($repo1, $repo2));
    }

    function testForkAssertNamespaceIsValid() {
        $this->backend->setReturnValue('isNameValid', false);
        $this->backend->expectNever('fork');

        $this->expectException();

        $this->forkRepositories(array($this->repository), '^toto/pouet');
    }

    private function GivenARepository($id) {
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $id);
        $repo->setReturnValue('userCanRead', true);
        $this->backend->setReturnValue('isNameValid', true);
        stub($repo)->getBackend()->returns($this->backend);
        return $repo;
    }

    private function forkRepositories($repositories, $namespace=null) {
        $this->manager->forkRepositories($repositories, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }
}

?>
