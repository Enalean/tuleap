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
use PFUser;
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

final class PullRequestUpdatedNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Factory
     */
    private $pull_request_factory;
    /**
     * @var GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $git_repository_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OwnerRetriever
     */
    private $owner_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserHelper
     */
    private $user_helper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HTMLURLBuilder
     */
    private $html_url_builder;
    /**
     * @var Git_GitRepositoryUrlManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $url_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PullRequestUpdateCommitDiff
     */
    private $commits_differ;

    private PullRequestUpdatedNotificationToProcessBuilder $builder;

    protected function setUp(): void
    {
        $this->user_manager           = $this->createMock(UserManager::class);
        $this->pull_request_factory   = $this->createMock(Factory::class);
        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $this->owner_retriever        = $this->createMock(OwnerRetriever::class);
        $this->user_helper            = $this->createMock(UserHelper::class);
        $this->html_url_builder       = $this->createMock(HTMLURLBuilder::class);
        $this->url_manager            = $this->createMock(Git_GitRepositoryUrlManager::class);
        $this->commits_differ         = $this->createMock(PullRequestUpdateCommitDiff::class);

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
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->method('findNewCommitReferences')->willReturn(['fbe4dade4f744aa203ec35bf09f71475ecc3f9d6']);

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertCount(1, $notifications);
        self::assertInstanceOf(PullRequestUpdatedNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenNoNewCommitIsFound(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->method('findNewCommitReferences')->willReturn([]);

        self::assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenContentOfTheRepositoryHasAlreadyChanged(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->method('findNewCommitReferences')
            ->willThrowException($this->createMock(\Git_Command_Exception::class));

        self::assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    private function setUpValidRepositoryWithoutDeterminingTheExpectedDiff(): PullRequestUpdatedEvent
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR Title');
        $change_user = $this->buildUser(102);
        $owners      = [$change_user, $this->buildUser(104), $this->buildUser(105)];

        $git_exec = new GitExec($this->getTmpDir());
        $git_exec->init();
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getId')->willReturn(1);
        $git_repository->method('getFullPath')->willReturn($this->getTmpDir() . '/myrepo.git');
        $git_repository->method('getGitRootPath')->willReturn($this->getTmpDir());
        $project = $this->createMock(\Project::class);
        $project->method('getUnixName')->willReturn('');
        $git_repository->method('getProject')->willReturn($project);
        $git_repository->method('getFullName')->willReturn('myrepo');
        $pull_request->method('getRepoDestId')->willReturn($git_repository->getId());
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

        $this->pull_request_factory->method('getPullRequestById')
            ->with($pull_request->getId())->willReturn($pull_request);
        $this->git_repository_factory->method('getRepositoryById')
            ->with($git_repository->getId())->willReturn($git_repository);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn($change_user);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');
        $this->url_manager->method('getAbsoluteCommitURL')->willReturn('https://example.com/link-to-commit');

        return $event;
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(404);
        $change_user = $this->buildUser(102);

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->method('getPullRequestById')->willThrowException(new PullRequestNotFoundException());

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheUserUpdatingThePullRequestCannotBeFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(14);
        $change_user = $this->buildUser(404);

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->method('getPullRequestById')->willReturn($pull_request);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn(null);

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheDestinationRepositoryCannotBeFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(14);
        $change_user    = $this->buildUser(102);
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getId')->willReturn(404);
        $pull_request->method('getRepoDestId')->willReturn($git_repository->getId());

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->method('getPullRequestById')->willReturn($pull_request);
        $this->git_repository_factory->method('getRepositoryById')
            ->with($git_repository->getId())->willReturn(null);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn($change_user);

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheDestinationRepositoryDataAreNotAvailable(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR Title');
        $change_user = $this->buildUser(102);

        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getId')->willReturn(2);
        $git_repository->method('getFullPath')->willReturn($this->getTmpDir() . '/myrepo.git');
        $git_repository->method('getGitRootPath')->willReturn($this->getTmpDir());
        $project = $this->createMock(\Project::class);
        $project->method('getUnixName')->willReturn('');
        $git_repository->method('getProject')->willReturn($project);
        $git_repository->method('getFullName')->willReturn('myrepo');
        $pull_request->method('getRepoDestId')->willReturn($git_repository->getId());

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_factory->method('getPullRequestById')->willReturn($pull_request);
        $this->git_repository_factory->method('getRepositoryById')
            ->with($git_repository->getId())->willReturn($git_repository);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn($change_user);

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
