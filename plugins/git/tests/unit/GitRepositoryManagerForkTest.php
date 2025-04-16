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

declare(strict_types=1);

namespace Tuleap\Git;

use EventManager;
use Exception;
use Git_Backend_Gitolite;
use Git_SystemEventManager;
use GitDao;
use GitRepository;
use GitRepositoryAlreadyExistsException;
use GitRepositoryFactory;
use GitRepositoryManager;
use org\bovigo\vfs\vfsStream;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectHistoryDao;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;
use Tuleap\Git\SystemEvent\OngoingDeletionDAO;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryManagerForkTest extends TestCase
{
    use GlobalResponseMock;

    private Git_Backend_Gitolite&MockObject $backend;
    private GitRepository $repository;
    private PFUser $user;
    private Project $project;
    private GitRepositoryManager&MockObject $manager;
    private array $forkPermissions;
    private Git_SystemEventManager&MockObject $git_system_event_manager;
    private FineGrainedPermissionReplicator&MockObject $fine_grained_permission_replicator;
    private HistoryValueFormatter&MockObject $history_value_formatter;
    private ProjectHistoryDao&MockObject $project_history_dao;
    private EventManager&MockObject $event_manager;

    protected function setUp(): void
    {
        $this->backend    = $this->createMock(Git_Backend_Gitolite::class);
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(554)->withBackend($this->backend)->build();

        $this->user    = UserTestBuilder::buildWithId(123);
        $this->project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->git_system_event_manager = $this->createMock(Git_SystemEventManager::class);
        $this->event_manager            = $this->createMock(EventManager::class);

        $this->fine_grained_permission_replicator = $this->createMock(FineGrainedPermissionReplicator::class);
        $this->history_value_formatter            = $this->createMock(HistoryValueFormatter::class);
        $this->project_history_dao                = $this->createMock(ProjectHistoryDao::class);

        $this->manager = $this->getMockBuilder(GitRepositoryManager::class)
            ->setConstructorArgs([
                $this->createMock(GitRepositoryFactory::class),
                $this->git_system_event_manager,
                $this->createMock(GitDao::class),
                vfsStream::setup()->url(),
                $this->fine_grained_permission_replicator,
                $this->project_history_dao,
                $this->history_value_formatter,
                $this->event_manager,
                $this->createMock(OngoingDeletionDAO::class),
            ])
            ->onlyMethods(['isRepositoryNameAlreadyUsed'])
            ->getMock();

        $this->forkPermissions = [];
    }

    public function testItThrowAnExceptionIfRepositoryNameCannotBeUsed(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(true);

        $this->expectException(GitRepositoryAlreadyExistsException::class);
        $this->manager->fork(
            $this->repository,
            $this->project,
            $this->user,
            'namespace',
            GitRepository::REPO_SCOPE_INDIVIDUAL,
            $this->forkPermissions
        );
    }

    public function testItForkInRepositoryBackendIfEverythingIsClean(): void
    {
        $this->backend->expects($this->once())->method('fork')->willReturn(667);
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');
        $this->event_manager->method('processEvent');
        $this->git_system_event_manager->method('queueRepositoryFork');

        $this->manager->fork($this->repository, $this->project, $this->user, 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItScheduleAndEventToApplyForkOnFilesystem(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);
        $this->backend->method('fork')->willReturn(667);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');
        $this->event_manager->method('processEvent');

        $this->git_system_event_manager->expects($this->once())->method('queueRepositoryFork')
            ->with($this->repository, self::callback(static fn(GitRepository $repository) => $repository->getId() === 667));

        $this->manager->fork($this->repository, $this->project, $this->user, 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItAsksForExternalPluginsAfterForkingTheRepository(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);
        $this->backend->method('fork')->willReturn(667);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $this->event_manager->expects($this->once())->method('processEvent');
        $this->git_system_event_manager->method('queueRepositoryFork');

        $this->manager->fork($this->repository, $this->project, $this->user, 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItDoesntScheduleAnEventIfAnExceptionIsThrownByBackend(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed');
        $this->backend->method('fork')->willThrowException(new Exception('whatever'));

        $this->expectException(Exception::class);
        $this->git_system_event_manager->expects($this->never())->method('queueRepositoryFork');

        $this->manager->fork($this->repository, $this->project, $this->user, 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItDoesntScheduleAnEventWhenBackendReturnsNoId(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed');
        $this->backend->method('fork')->willReturn(false);

        $this->expectException(Exception::class);
        $this->git_system_event_manager->expects($this->never())->method('queueRepositoryFork');

        $this->manager->fork($this->repository, $this->project, $this->user, 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testItThrowsAnExceptionWhenBackendReturnsNoId(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed');
        $this->backend->method('fork')->willReturn(false);

        $this->expectException(Exception::class);

        $this->manager->fork($this->repository, $this->project, $this->user, 'namespace', GitRepository::REPO_SCOPE_INDIVIDUAL, $this->forkPermissions);
    }

    public function testForkIndividualRepositories(): void
    {
        $path = 'toto';
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);
        $this->backend->method('userCanRead')->with($this->user)->willReturn(true);
        $this->backend->method('isNameValid')->with($path)->willReturn(true);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $this->backend->expects($this->once())->method('fork');
        $this->manager->forkRepositories([$this->repository], $this->project, $this->user, $path, null, $this->forkPermissions);
    }

    public function testClonesManyInternalRepositories(): void
    {
        $namespace = 'toto';
        $repo_ids  = ['1', '2', '3'];

        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $repos = [];
        foreach ($repo_ids as $id) {
            $repo    = GitRepositoryTestBuilder::aProjectRepository()->withId((int) $id)
                ->inProject($this->project)->withBackend($this->backend)->build();
            $repos[] = $repo;
        }

        $this->event_manager->method('processEvent');
        $this->git_system_event_manager->method('queueRepositoryFork');
        $this->backend->expects($this->exactly(count($repo_ids)))->method('userCanRead')->with($this->user)->willReturn(true);
        $this->backend->method('isNameValid')->with($namespace)->willReturn(true);
        $this->backend->method('fork')->willReturnOnConsecutiveCalls(667, 668, 669);
        self::assertTrue($this->manager->forkRepositories($repos, $this->project, $this->user, $namespace, null, $this->forkPermissions));
    }

    public function testCloneManyCrossProjectRepositories(): void
    {
        $this->user->setUserGroupData([['group_id' => 2, 'admin_flags' => '']]);
        $to_project = ProjectTestBuilder::aProject()->withId(2)->build();

        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $repo_ids = ['1', '2', '3'];
        $repos    = [];
        foreach ($repo_ids as $id) {
            $repo    = GitRepositoryTestBuilder::aProjectRepository()->withId((int) $id)
                ->inProject($this->project)->withBackend($this->backend)->build();
            $repos[] = $repo;
        }

        $this->backend->expects($this->exactly(count($repo_ids)))->method('userCanRead')->with($this->user)->willReturn(true);
        $this->backend->expects($this->exactly(3))->method('fork');
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    public function testWhenNoRepositorySelectedItAddsWarning(): void
    {
        $this->expectException(Exception::class);
        $this->manager->forkRepositories([], $this->project, $this->user, '', null, $this->forkPermissions);
    }

    public function testClonesOneRepository(): void
    {
        $this->repository->setId(1);
        $this->backend->method('userCanRead')->with($this->user)->willReturn(true);

        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $this->backend->expects($this->once())->method('fork');
        $this->manager->forkRepositories([$this->repository], $this->project, $this->user, '', null, $this->forkPermissions);
    }

    public function testDoesntCloneUnreadableRepos(): void
    {
        $repos = $this->getRepoCollectionUnreadableFor([1, 2, 3]);
        $this->backend->method('userCanRead')->with($this->user)->willReturn(false);
        $to_project = ProjectTestBuilder::aProject()->withId(2)->build();

        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $this->backend->expects($this->never())->method('fork');
        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    /**
     * @param list<int> $repo_ids
     * @return list<GitRepository>
     */
    protected function getRepoCollectionUnreadableFor(array $repo_ids): array
    {
        $return = [];
        foreach ($repo_ids as $id) {
            $repo     = GitRepositoryTestBuilder::aProjectRepository()->withId($id)->withBackend($this->backend)->build();
            $return[] = $repo;
        }
        return $return;
    }

    public function testForkCrossProjectsRedirectToCrossProjectGitRepositories(): void
    {
        $repo_id    = '1';
        $project_id = 2;

        $this->user->setUserGroupData([['group_id' => $project_id, 'admin_flags' => 'A']]);
        $to_project = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $this->backend->expects($this->once())->method('fork');

        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $this->repository->setId($repo_id);
        $this->backend->method('userCanRead')->with($this->user)->willReturn(true);

        $repos = [$this->repository];

        $this->manager->forkRepositories($repos, $to_project, $this->user, '', null, $this->forkPermissions);
    }

    public function testForkShouldNotCloneAnyNonExistentRepositories(): void
    {
        $this->backend->expects($this->once())->method('fork');

        $repo = $this->givenARepository(123);
        $this->backend->method('userCanRead')->willReturn(true);
        $this->backend->method('isNameValid')->willReturn(true);

        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);

        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->history_value_formatter->method('formatValueForRepository');
        $this->project_history_dao->method('groupAddHistory');

        $this->manager->forkRepositories([$repo, null], $this->project, $this->user, null, null, $this->forkPermissions);
    }

    public function testForkShouldIgnoreAlreadyExistingRepository(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);
        $this->fine_grained_permission_replicator->method('replicateDefaultPermissionsFromProject');
        $this->event_manager->method('processEvent');
        $this->history_value_formatter->method('formatValueForRepository')->willReturn('');
        $this->project_history_dao->method('groupAddHistory');
        $this->git_system_event_manager->method('queueRepositoryFork');

        $GLOBALS['Response']->expects($this->atLeastOnce())->method('addFeedback')->willReturnCallback(
            function (string $level, string $message): void {
                match (true) {
                    $level === 'warning' &&
                    ($message === 'Repository my-repo-123 already exists on target, skipped.' || str_contains($message, 'my-repo-456')) => true,
                };
            }
        );

        $repo1 = $this->givenARepository(123);
        $repo2 = $this->givenARepository(456);
        $this->backend->method('userCanRead')->willReturn(true);
        $this->backend->method('isNameValid')->willReturn(true);

        $counter = 0;
        $this->backend->expects($this->exactly(2))->method('fork')->willReturnCallback(function () use (&$counter) {
            if ($counter++ === 0) {
                throw new GitRepositoryAlreadyExistsException('');
            }
            return 667;
        });

        $this->forkRepositories([$repo1, $repo2]);
    }

    public function testForkGiveInformationAboutUnexpectedErrors(): void
    {
        $this->manager->method('isRepositoryNameAlreadyUsed')->willReturn(false);


        $errorMessage = 'user gitolite doesnt exist';
        $repo2        = $this->givenARepository(456);
        $this->backend->method('userCanRead')->willReturn(true);
        $this->backend->method('isNameValid')->willReturn(true);
        $repo2->setName('megaRepoGit');

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('warning', 'Got an unexpected error while forking ' . $repo2->getName() . ': ' . $errorMessage);

        $this->backend->expects($this->once())->method('fork')->willThrowException(new Exception($errorMessage));

        $this->forkRepositories([$repo2]);
    }

    public function testForkAssertNamespaceIsValid(): void
    {
        $this->backend->method('isNameValid')->willReturn(false);
        $this->backend->expects($this->never())->method('fork');

        $this->expectException(Exception::class);

        $this->forkRepositories([$this->repository], '^toto/pouet');
    }

    private function givenARepository(int $id): GitRepository
    {
        return GitRepositoryTestBuilder::aProjectRepository()->withId($id)
            ->withName("my-repo-$id")
            ->inProject(ProjectTestBuilder::aProject()->build())
            ->withBackend($this->backend)
            ->build();
    }

    /**
     * @param list<GitRepository> $repositories
     * @throws Exception
     */
    private function forkRepositories(array $repositories, ?string $namespace = null): void
    {
        $this->manager->forkRepositories($repositories, $this->project, $this->user, $namespace, null, $this->forkPermissions);
    }
}
