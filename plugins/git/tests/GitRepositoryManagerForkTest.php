<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

require_once __DIR__.'/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryManagerForkTest extends TuleapTestCase
{
    private $backend;
    private $repository;
    private $user;
    private $project;
    private $manager;
    private $forkPermissions;
    private $git_system_event_manager;
    private $backup_directory;

    public function setUp()
    {
        parent::setUp();
        $this->backend    = mock('Git_Backend_Gitolite');
        $this->repository = partial_mock('GitRepository', array('userCanRead', 'isNameValid'));
        $this->repository->setId(554);
        $this->repository->setBackend($this->backend);

        $this->user    = stub('PFUser')->getId()->returns(123);
        $this->project = stub('Project')->getId()->returns(101);

        $this->git_system_event_manager = mock('Git_SystemEventManager');
        $this->backup_directory         = "/tmp/";
        $this->mirror_updater           = mock('GitRepositoryMirrorUpdater');
        $this->mirror_data_mapper       = stub('Git_Mirror_MirrorDataMapper')
            ->fetchAllRepositoryMirrors()
            ->returns(array());

        $this->event_manager = mock(EventManager::class);

        $this->manager = partial_mock(
            'GitRepositoryManager',
            array('isRepositoryNameAlreadyUsed'),
            array(
                mock('GitRepositoryFactory'),
                $this->git_system_event_manager,
                safe_mock(GitDao::class),
                $this->backup_directory,
                $this->mirror_updater,
                $this->mirror_data_mapper,
                mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator'),
                mock('ProjectHistoryDao'),
                mock('Tuleap\Git\Permissions\HistoryValueFormatter'),
                $this->event_manager
            )
        );

        $this->forkPermissions = array();
    }

    public function itThrowAnExceptionIfRepositoryNameCannotBeUsed()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(true);

        $this->expectException();
        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itForkInRepositoryBackendIfEverythingIsClean()
    {
        stub($this->backend)->fork()->returns(667);
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);

        $this->backend->expectOnce('fork');
        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itScheduleAndEventToApplyForkOnFilesystem()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);

        stub($this->backend)->fork()->returns(667);

        expect($this->git_system_event_manager)->queueRepositoryFork(
            $this->repository,
            new class(667) extends SimpleExpectation {
                public function __construct($repository_id)
                {
                    parent::__construct();
                    $this->repository_id = $repository_id;
                }

                public function test($compare)
                {
                    if (! $compare instanceof GitRepository) {
                        throw new InvalidArgumentException('Expected ' . GitRepository::class . 'got ' . get_class($compare));
                    }
                    return $compare->getId() === $this->repository_id;
                }

                public function testMessage($compare)
                {
                    return "Expected repository id is $this->repository_id, ".$compare->getId()." given";
                }
            }
        )
            ->once();

        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itAsksForExternalPluginsAfterForkingTheRepository()
    {
        stub($this->manager)->isRepositoryNameAlreadyUsed($this->repository)->returns(false);
        stub($this->backend)->fork()->returns(667);

        expect($this->event_manager)->processEvent()->once();

        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itDoesntScheduleAnEventIfAnExceptionIsThrownByBackend()
    {
        stub($this->backend)->fork()->throws(new Exception('whatever'));

        $this->expectException();
        expect($this->git_system_event_manager)->queueRepositoryFork()->never();

        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itDoesntScheduleAnEventWhenBackendReturnsNoId()
    {
        stub($this->backend)->fork()->returns(false);

        $this->expectException();
        expect($this->git_system_event_manager)->queueRepositoryFork()->never();

        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function itThrowsAnExceptionWhenBackendReturnsNoId()
    {
        stub($this->backend)->fork()->returns(false);

        $this->expectException();

        $this->manager->fork($this->repository, mock('Project'), mock('PFUser'), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testForkIndividualRepositories()
    {
        $path  = 'toto';
        $this->repository->setReturnValue('userCanRead', true, array($this->user));
        $this->backend->setReturnValue('isNameValid', true, array($path));

        $this->backend->expectOnce('fork');
        $this->manager->forkRepositories(array($this->repository), $this->project, $this->user, $path, null, $this->forkPermissions);
    }

    public function testClonesManyInternalRepositories()
    {
        $namespace  = 'toto';
        $repo_ids = array('1', '2', '3');

        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($this->user));
            $repo->setReturnValue('getProject', $this->project);
            $this->backend->setReturnValue('isNameValid', true, array($namespace));
            stub($repo)->getBackend()->returns($this->backend);
            $repos[] = $repo;
        }

        $this->backend->expectCallCount('fork', 3);
        $this->manager->forkRepositories($repos, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }

    public function testCloneManyCrossProjectRepositories()
    {
        $this->user->setReturnValue('isMember', true);
        $to_project = stub('Project')->getId()->returns(2);

        $repo_ids = array('1', '2', '3');
        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', true, array($this->user));
            $repo->setReturnValue('getProject', \Mockery::spy(Project::class));
            stub($repo)->getBackend()->returns($this->backend);
            $repos[] = $repo;
        }

        $this->backend->expectCallCount('fork', 3);
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    public function testWhenNoRepositorySelectedItAddsWarning()
    {
        $this->expectException();
        $this->manager->forkRepositories(array(), $this->project, $this->user, '', null, $this->forkPermissions);
    }

    public function testClonesOneRepository()
    {
        $this->repository->setId(1);
        $this->repository->setReturnValue('userCanRead', true, array($this->user));

        $this->backend->expectOnce('fork');
        $this->manager->forkRepositories(array($this->repository), $this->project, $this->user, '', null, $this->forkPermissions);
    }

    public function testDoesntCloneUnreadableRepos()
    {
        $repos = $this->getRepoCollectionUnreadableFor(array('1', '2', '3'), $this->user);
        $to_project = stub('Project')->getId()->returns(2);

        $this->backend->expectNever('fork');
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    protected function getRepoCollectionUnreadableFor($repo_ids, $user)
    {
        $return = array();
        foreach ($repo_ids as $id) {
            $repo = new MockGitRepository();
            $repo->setReturnValue('getId', $id);
            $repo->setReturnValue('userCanRead', false, array($user));
            $return[] = $repo;
        }
        return $return;
    }

    public function testForkCrossProjectsRedirectToCrossProjectGitRepositories()
    {
        $repo_id = '1';
        $project_id = 2;

        stub($this->user)->isMember($project_id, 'A')->returns(true);
        $to_project = stub('Project')->getId()->returns($project_id);

        $this->backend->expectOnce('fork');

        $this->repository->setId($repo_id);
        $this->repository->setReturnValue('userCanRead', true, array($this->user));

        $repos = array($this->repository);

        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    public function testForkShouldNotCloneAnyNonExistentRepositories()
    {
        $this->backend->expectOnce('fork');

        $repo = $this->givenARepository(123);

        $this->manager->forkRepositories(array($repo, null), $this->project, $this->user, null, null, $this->forkPermissions);
    }

    public function testForkShouldIgnoreAlreadyExistingRepository()
    {
        $this->backend->throwAt(0, 'fork', new GitRepositoryAlreadyExistsException(''));
        $this->backend->setReturnValueAt(1, 'fork', 667);

        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', 'Repository my-repo-123 already exists on target, skipped.'));
        $repo1 = $this->givenARepository(123);

        $repo2 = $this->givenARepository(456);

        $this->backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $this->forkRepositories(array($repo1, $repo2));
    }

    public function testForkShouldTellTheUserIfTheRepositoryAlreadyExists()
    {
        $repo2 = $this->givenARepository(456);

        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', 'Repository my-repo-456 already exists on target, skipped.'));
        $this->backend->setReturnValueAt(0, 'fork', 667);
        $this->backend->throwAt(1, 'fork', new GitRepositoryAlreadyExistsException($repo2->getName()));

        $repo1 = $this->givenARepository(123);

        $this->backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $this->forkRepositories(array($repo1, $repo2));
    }

    public function testForkGiveInformationAboutUnexpectedErrors()
    {
        $errorMessage = 'user gitolite doesnt exist';
        $repo2 = $this->givenARepository(456);
        $repo2->setName('megaRepoGit');

        $GLOBALS['Response']->expectOnce('addFeedback', array('warning', "Got an unexpected error while forking ".$repo2->getName().": ".$errorMessage));
        $this->backend->setReturnValueAt(0, 'fork', 667);
        $this->backend->throwAt(1, 'fork', new Exception($errorMessage));

        $repo1 = $this->givenARepository(123);

        $this->backend->expectCallCount('fork', 2); //should still call fork on the second repo
        $this->forkRepositories(array($repo1, $repo2));
    }

    public function testForkAssertNamespaceIsValid()
    {
        $this->backend->setReturnValue('isNameValid', false);
        $this->backend->expectNever('fork');

        $this->expectException();

        $this->forkRepositories(array($this->repository), '^toto/pouet');
    }

    private function givenARepository($id)
    {
        $repo = new MockGitRepository();
        $repo->setReturnValue('getId', $id);
        $repo->setReturnValue('getName', "my-repo-$id");
        $repo->setReturnValue('userCanRead', true);
        $repo->setReturnValue('getProject', \Mockery::spy(Project::class));
        $this->backend->setReturnValue('isNameValid', true);
        stub($repo)->getBackend()->returns($this->backend);
        return $repo;
    }

    private function forkRepositories($repositories, $namespace = null)
    {
        $this->manager->forkRepositories($repositories, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }
}
