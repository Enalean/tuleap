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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalResponseMock;

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class GitRepositoryManagerForkTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalResponseMock;

    private $backend;
    private $repository;
    private $user;
    private $project;
    /**
     * @var \Mockery\Mock|GitRepositoryManager
     */
    private $manager;
    private $forkPermissions;
    private $git_system_event_manager;
    private $backup_directory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tuleap\Git\Permissions\FineGrainedPermissionReplicator
     */
    private $fine_grained_permission_replicator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tuleap\Git\Permissions\HistoryValueFormatter
     */
    private $history_value_formatter;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectHistoryDao
     */
    private $project_history_dao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backend    = \Mockery::spy(\Git_Backend_Gitolite::class);
        $this->repository = \Mockery::mock(\GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->repository->setId(554);
        $this->repository->setBackend($this->backend);

        $this->user    = \Mockery::spy(\PFUser::class)->shouldReceive('getId')->andReturns(123)->getMock();
        $this->project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(101)->getMock();

        $this->git_system_event_manager = \Mockery::spy(\Git_SystemEventManager::class);
        $this->backup_directory         = vfsStream::setup()->url();
        $this->mirror_updater           = \Mockery::spy(\GitRepositoryMirrorUpdater::class);
        $this->mirror_data_mapper       = \Mockery::spy(\Git_Mirror_MirrorDataMapper::class)->shouldReceive('fetchAllRepositoryMirrors')->andReturns(array())->getMock();

        $this->event_manager = \Mockery::spy(EventManager::class);

        $this->fine_grained_permission_replicator = Mockery::mock('Tuleap\Git\Permissions\FineGrainedPermissionReplicator');
        $this->history_value_formatter = Mockery::mock('Tuleap\Git\Permissions\HistoryValueFormatter');
        $this->project_history_dao = Mockery::mock('ProjectHistoryDao');

        $this->manager = \Mockery::mock(
            \GitRepositoryManager::class,
            array(
                Mockery::mock('GitRepositoryFactory'),
                $this->git_system_event_manager,
                Mockery::mock(GitDao::class),
                $this->backup_directory,
                $this->mirror_updater,
                $this->mirror_data_mapper,
                $this->fine_grained_permission_replicator,
                $this->project_history_dao,
                $this->history_value_formatter,
                $this->event_manager
            )
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->forkPermissions = array();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);
        parent::tearDown();
    }

    public function testItThrowAnExceptionIfRepositoryNameCannotBeUsed(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(true);

        $this->expectException(GitRepositoryAlreadyExistsException::class);
        $this->manager->fork(
            $this->repository,
            \Mockery::spy(\Project::class),
            \Mockery::spy(\PFUser::class),
            'namespace',
            GitRepository::REPO_SCOPE_INDIVIDUAL,
            $this->forkPermissions
        );
    }

    public function testItForkInRepositoryBackendIfEverythingIsClean(): void
    {
        $this->backend->shouldReceive('fork')->andReturns(667)->once();
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->manager->fork($this->repository, \Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItScheduleAndEventToApplyForkOnFilesystem(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->backend->shouldReceive('fork')->andReturns(667);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->git_system_event_manager->shouldReceive('queueRepositoryFork')->with($this->repository, Mockery::on(function (GitRepository $repository) {
            return $repository->getId() === 667;
        }))
            ->once();

        $this->manager->fork($this->repository, \Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItAsksForExternalPluginsAfterForkingTheRepository(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);
        $this->backend->shouldReceive('fork')->andReturns(667);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->manager->fork($this->repository, \Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItDoesntScheduleAnEventIfAnExceptionIsThrownByBackend(): void
    {
        $this->backend->shouldReceive('fork')->andThrows(new Exception('whatever'));

        $this->expectException(Exception::class);
        $this->git_system_event_manager->shouldReceive('queueRepositoryFork')->never();

        $this->manager->fork($this->repository, \Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItDoesntScheduleAnEventWhenBackendReturnsNoId(): void
    {
        $this->backend->shouldReceive('fork')->andReturns(false);

        $this->expectException(Exception::class);
        $this->git_system_event_manager->shouldReceive('queueRepositoryFork')->never();

        $this->manager->fork($this->repository, \Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItThrowsAnExceptionWhenBackendReturnsNoId(): void
    {
        $this->backend->shouldReceive('fork')->andReturns(false);

        $this->expectException(Exception::class);

        $this->manager->fork($this->repository, \Mockery::spy(\Project::class), \Mockery::spy(\PFUser::class), 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testForkIndividualRepositories(): void
    {
        $path  = 'toto';
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturns(true);
        $this->backend->shouldReceive('isNameValid')->with($path)->andReturns(true);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->backend->shouldReceive('fork')->once();
        $this->manager->forkRepositories(array($this->repository), $this->project, $this->user, $path, null, $this->forkPermissions);
    }

    public function testClonesManyInternalRepositories(): void
    {
        $namespace  = 'toto';
        $repo_ids = array('1', '2', '3');

        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = Mockery::mock(GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $repo->shouldReceive('getId')->andReturns($id);
            $repo->shouldReceive('userCanRead')->with($this->user)->andReturns(true);
            $repo->shouldReceive('getProject')->andReturns($this->project);
            $this->backend->shouldReceive('isNameValid')->with($namespace)->andReturns(true);
            $repo->shouldReceive('getBackend')->andReturns($this->backend);
            $repos[] = $repo;
        }

        $this->backend->shouldReceive('fork')->andReturn(667, 668, 669);
        $this->manager->forkRepositories($repos, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }

    public function testCloneManyCrossProjectRepositories(): void
    {
        $this->user->shouldReceive('isMember')->andReturns(true);
        $to_project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(2)->getMock();

        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $repo_ids = array('1', '2', '3');
        $repos = array();
        foreach ($repo_ids as $id) {
            $repo = Mockery::mock(GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $repo->shouldReceive('getId')->andReturns($id);
            $repo->shouldReceive('userCanRead')->with($this->user)->andReturns(true);
            $repo->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));
            $repo->shouldReceive('getBackend')->andReturns($this->backend);
            $repos[] = $repo;
        }

        $this->backend->shouldReceive('fork')->times(3);
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    public function testWhenNoRepositorySelectedItAddsWarning(): void
    {
        $this->expectException(Exception::class);
        $this->manager->forkRepositories(array(), $this->project, $this->user, '', null, $this->forkPermissions);
    }

    public function testClonesOneRepository(): void
    {
        $this->repository->setId(1);
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturns(true);

        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->backend->shouldReceive('fork')->once();
        $this->manager->forkRepositories(array($this->repository), $this->project, $this->user, '', null, $this->forkPermissions);
    }

    public function testDoesntCloneUnreadableRepos(): void
    {
        $repos = $this->getRepoCollectionUnreadableFor(array('1', '2', '3'), $this->user);
        $to_project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(2)->getMock();

        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->backend->shouldReceive('fork')->never();
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    protected function getRepoCollectionUnreadableFor($repo_ids, $user)
    {
        $return = array();
        foreach ($repo_ids as $id) {
            $repo = Mockery::mock(GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $repo->shouldReceive('getId')->andReturns($id);
            $repo->shouldReceive('userCanRead')->with($user)->andReturns(false);
            $return[] = $repo;
        }
        return $return;
    }

    public function testForkCrossProjectsRedirectToCrossProjectGitRepositories(): void
    {
        $repo_id = '1';
        $project_id = 2;

        $this->user->shouldReceive('isMember')->with($project_id, 'A')->andReturns(true);
        $to_project = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns($project_id)->getMock();

        $this->backend->shouldReceive('fork')->once();

        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->repository->setId($repo_id);
        $this->repository->shouldReceive('userCanRead')->with($this->user)->andReturns(true);

        $repos = array($this->repository);

        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    public function testForkShouldNotCloneAnyNonExistentRepositories(): void
    {
        $this->backend->shouldReceive('fork')->once();

        $repo = $this->givenARepository(123);

        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $this->fine_grained_permission_replicator->shouldReceive('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->shouldReceive('formatValueForRepository');
        $this->project_history_dao->shouldReceive('groupAddHistory');

        $this->manager->forkRepositories(array($repo, null), $this->project, $this->user, null, null, $this->forkPermissions);
    }

    public function testForkShouldIgnoreAlreadyExistingRepository(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', 'Repository my-repo-123 already exists on target, skipped.')->once();

        $repo1 = $this->givenARepository(123);
        $repo2 = $this->givenARepository(456);

        $this->backend->shouldReceive('fork')->andThrow(new GitRepositoryAlreadyExistsException(''))->once();
        $this->backend->shouldReceive('fork')->andReturn(667)->once();

        $this->forkRepositories(array($repo1, $repo2));
    }

    public function testForkShouldTellTheUserIfTheRepositoryAlreadyExists(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $repo1 = $this->givenARepository(123);
        $repo2 = $this->givenARepository(456);

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', 'Repository my-repo-456 already exists on target, skipped.')->once();

        $this->backend->shouldReceive('fork')->andReturn(667)->once();
        $this->backend->shouldReceive('fork')->andThrow(new GitRepositoryAlreadyExistsException($repo2->getName()))->once();

        $this->forkRepositories(array($repo1, $repo2));
    }

    public function testForkGiveInformationAboutUnexpectedErrors(): void
    {
        $this->manager->shouldReceive('isRepositoryNameAlreadyUsed')->andReturns(false);

        $errorMessage = 'user gitolite doesnt exist';
        $repo2 = $this->givenARepository(456);
        $repo2->setName('megaRepoGit');

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('warning', "Got an unexpected error while forking " . $repo2->getName() . ": " . $errorMessage)->once();

        $this->backend->shouldReceive('fork')->andReturn(667)->once();
        $this->backend->shouldReceive('fork')->andThrow(new Exception($errorMessage))->once();

        $repo1 = $this->givenARepository(123);

        $this->forkRepositories(array($repo1, $repo2));
    }

    public function testForkAssertNamespaceIsValid(): void
    {
        $this->backend->shouldReceive('isNameValid')->andReturns(false);
        $this->backend->shouldReceive('fork')->never();

        $this->expectException(Exception::class);

        $this->forkRepositories(array($this->repository), '^toto/pouet');
    }

    private function givenARepository($id)
    {
        $repo = Mockery::mock(GitRepository::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $repo->shouldReceive('getId')->andReturns($id);
        $repo->shouldReceive('getName')->andReturns("my-repo-$id");
        $repo->shouldReceive('userCanRead')->andReturns(true);
        $repo->shouldReceive('getProject')->andReturns(\Mockery::spy(Project::class));
        $this->backend->shouldReceive('isNameValid')->andReturns(true);
        $repo->shouldReceive('getBackend')->andReturns($this->backend);
        return $repo;
    }

    private function forkRepositories($repositories, $namespace = null)
    {
        $this->manager->forkRepositories($repositories, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }
}
