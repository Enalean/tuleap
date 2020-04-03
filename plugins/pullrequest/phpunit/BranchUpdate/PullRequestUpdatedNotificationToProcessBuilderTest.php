<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\BranchUpdate;

use Git_GitRepositoryUrlManager;
use GitRepository;
use GitRepositoryFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\TemporaryTestDirectory;
use UserHelper;
use UserManager;

final class PullRequestUpdatedNotificationToProcessBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Factory
     */
    private $pull_request_factory;
    /**
     * @var GitRepositoryFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $git_repository_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|OwnerRetriever
     */
    private $owner_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserHelper
     */
    private $user_helper;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HTMLURLBuilder
     */
    private $html_url_builder;
    /**
     * @var Git_GitRepositoryUrlManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $url_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PullRequestUpdateCommitDiff
     */
    private $commits_differ;

    /**
     * @var PullRequestUpdatedNotificationToProcessBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->user_manager              = \Mockery::mock(UserManager::class);
        $this->pull_request_factory      = \Mockery::mock(Factory::class);
        $this->git_repository_factory    = \Mockery::mock(GitRepositoryFactory::class);
        $this->owner_retriever           = \Mockery::mock(OwnerRetriever::class);
        $this->user_helper               = \Mockery::mock(UserHelper::class);
        $this->html_url_builder          = \Mockery::mock(HTMLURLBuilder::class);
        $this->url_manager               = \Mockery::mock(Git_GitRepositoryUrlManager::class);
        $this->commits_differ            = \Mockery::mock(PullRequestUpdateCommitDiff::class);

        $this->builder = new PullRequestUpdatedNotificationToProcessBuilder(
            $this->user_manager,
            $this->pull_request_factory,
            $this->git_repository_factory,
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder,
            $this->url_manager,
            $this->commits_differ
        );
    }

    public function testBuildUpdateNotificationFromPullRequestUpdatedEvent(): void
    {
        $event = $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->shouldReceive('findNewCommitReferences')->andReturn(['fbe4dade4f744aa203ec35bf09f71475ecc3f9d6']);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(PullRequestUpdatedNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenNoNewCommitIsFound(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->shouldReceive('findNewCommitReferences')->andReturn([]);

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenContentOfTheRepositoryHasAlreadyChanged(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->shouldReceive('findNewCommitReferences')
            ->andThrow(\Mockery::mock(\Git_Command_Exception::class));

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    private function setUpValidRepositoryWithoutDeterminingTheExpectedDiff(): PullRequestUpdatedEvent
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $pull_request->shouldReceive('getTitle')->andReturn('PR Title');
        $change_user   = $this->buildUser(102);
        $owners        = [$change_user, $this->buildUser(104), $this->buildUser(105)];

        $git_exec = new GitExec($this->getTmpDir());
        $git_exec->init();
        $git_repository = \Mockery::mock(GitRepository::class);
        $git_repository->shouldReceive('getId')->andReturn(1);
        $git_repository->shouldReceive('getFullPath')->andReturn($this->getTmpDir() . '/myrepo.git');
        $git_repository->shouldReceive('getGitRootPath')->andReturn($this->getTmpDir());
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturn('');
        $git_repository->shouldReceive('getProject')->andReturn($project);
        $git_repository->shouldReceive('getFullName')->andReturn('myrepo');
        $pull_request->shouldReceive('getRepoDestId')->andReturn($git_repository->getId());
        $git_exec = \Git_Exec::buildFromRepository($git_repository);
        $git_exec->init();

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->shouldReceive('getPullRequestById')
            ->with($pull_request->getId())->andReturn($pull_request);
        $this->git_repository_factory->shouldReceive('getRepositoryById')
            ->with($git_repository->getId())->andReturn($git_repository);
        $this->user_manager->shouldReceive('getUserById')
            ->with($change_user->getId())->andReturn($change_user);
        $this->owner_retriever->shouldReceive('getOwners')->andReturn($owners);
        $this->user_helper->shouldReceive('getDisplayNameFromUser')->andReturn('Display name');
        $this->user_helper->shouldReceive('getAbsoluteUserURL')->andReturn('https://example.com/users/foo');
        $this->html_url_builder->shouldReceive('getAbsolutePullRequestOverviewUrl')->andReturn('https://example.com/link-to-pr');
        $this->url_manager->shouldReceive('getAbsoluteCommitURL')->andReturn('https://example.com/link-to-commit');

        return $event;
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(404);
        $change_user = $this->buildUser(102);

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->shouldReceive('getPullRequestById')->andThrow(PullRequestNotFoundException::class);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheUserUpdatingThePullRequestCannotBeFound(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(14);
        $change_user = $this->buildUser(404);

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->shouldReceive('getPullRequestById')->andReturn($pull_request);
        $this->user_manager->shouldReceive('getUserById')
            ->with($change_user->getId())->andReturn(null);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheDestinationRepositoryCannotBeFound(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(14);
        $change_user = $this->buildUser(102);
        $git_repository = \Mockery::mock(GitRepository::class);
        $git_repository->shouldReceive('getId')->andReturn(404);
        $pull_request->shouldReceive('getRepoDestId')->andReturn($git_repository->getId());

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->shouldReceive('getPullRequestById')->andReturn($pull_request);
        $this->git_repository_factory->shouldReceive('getRepositoryById')
            ->with($git_repository->getId())->andReturn(null);
        $this->user_manager->shouldReceive('getUserById')
            ->with($change_user->getId())->andReturn($change_user);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheDestinationRepositoryDataAreNotAvailable(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $pull_request->shouldReceive('getTitle')->andReturn('PR Title');
        $change_user   = $this->buildUser(102);

        $git_repository = \Mockery::mock(GitRepository::class);
        $git_repository->shouldReceive('getId')->andReturn(2);
        $git_repository->shouldReceive('getFullPath')->andReturn($this->getTmpDir() . '/myrepo.git');
        $git_repository->shouldReceive('getGitRootPath')->andReturn($this->getTmpDir());
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getUnixName')->andReturn('');
        $git_repository->shouldReceive('getProject')->andReturn($project);
        $git_repository->shouldReceive('getFullName')->andReturn('myrepo');
        $pull_request->shouldReceive('getRepoDestId')->andReturn($git_repository->getId());

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->shouldReceive('getPullRequestById')->andReturn($pull_request);
        $this->git_repository_factory->shouldReceive('getRepositoryById')
            ->with($git_repository->getId())->andReturn($git_repository);
        $this->user_manager->shouldReceive('getUserById')
            ->with($change_user->getId())->andReturn($change_user);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertEmpty($notifications);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
