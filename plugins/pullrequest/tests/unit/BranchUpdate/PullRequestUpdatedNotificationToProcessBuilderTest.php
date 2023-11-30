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
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\GitExec;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use UserHelper;

final class PullRequestUpdatedNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private RetrieveUserByIdStub $user_manager;
    private GitRepositoryFactory&MockObject $git_repository_factory;
    private MockObject&OwnerRetriever $owner_retriever;
    private MockObject&UserHelper $user_helper;
    private MockObject&HTMLURLBuilder $html_url_builder;
    private Git_GitRepositoryUrlManager&MockObject $url_manager;
    private MockObject&PullRequestUpdateCommitDiff $commits_differ;

    private SearchPullRequestStub $pull_request_dao;
    private PFUser $change_user;

    protected function setUp(): void
    {
        $this->pull_request_dao       = SearchPullRequestStub::withAtLeastOnePullRequest(PullRequestTestBuilder::aPullRequestInReview()->build());
        $this->git_repository_factory = $this->createMock(GitRepositoryFactory::class);
        $this->owner_retriever        = $this->createMock(OwnerRetriever::class);
        $this->user_helper            = $this->createMock(UserHelper::class);
        $this->html_url_builder       = $this->createMock(HTMLURLBuilder::class);
        $this->url_manager            = $this->createMock(Git_GitRepositoryUrlManager::class);
        $this->commits_differ         = $this->createMock(PullRequestUpdateCommitDiff::class);

        $this->change_user  = UserTestBuilder::buildWithId(102);
        $this->user_manager = RetrieveUserByIdStub::withUser($this->change_user);
    }

    private function getNotificationsToProcess(PullRequestUpdatedEvent $event): array
    {
        $builder = new PullRequestUpdatedNotificationToProcessBuilder(
            $this->user_manager,
            new PullRequestRetriever($this->pull_request_dao),
            $this->git_repository_factory,
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder,
            $this->url_manager,
            $this->commits_differ
        );

        return $builder->getNotificationsToProcess($event);
    }

    public function testBuildUpdateNotificationFromPullRequestUpdatedEvent(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->method('findNewCommitReferences')->willReturn(['fbe4dade4f744aa203ec35bf09f71475ecc3f9d6']);

        $notifications = $this->getNotificationsToProcess($event);
        self::assertCount(1, $notifications);
        self::assertInstanceOf(PullRequestUpdatedNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenNoNewCommitIsFound(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->method('findNewCommitReferences')->willReturn([]);

        self::assertEmpty($this->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenContentOfTheRepositoryHasAlreadyChanged(): void
    {
        $event = $this->setUpValidRepositoryWithoutDeterminingTheExpectedDiff();

        $this->commits_differ->method('findNewCommitReferences')
            ->willThrowException($this->createMock(\Git_Command_Exception::class));

        self::assertEmpty($this->getNotificationsToProcess($event));
    }

    private function setUpValidRepositoryWithoutDeterminingTheExpectedDiff(): PullRequestUpdatedEvent
    {
        $owners = [$this->change_user, UserTestBuilder::buildWithId(104), UserTestBuilder::buildWithId(105)];

        $git_exec = new GitExec($this->getTmpDir());
        $git_exec->init();
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getId')->willReturn(1);
        $git_repository->method('getFullPath')->willReturn($this->getTmpDir() . '/myrepo.git');
        $git_repository->method('getGitRootPath')->willReturn($this->getTmpDir());
        $project = ProjectTestBuilder::aProject()->withUnixName('')->build();
        $git_repository->method('getProject')->willReturn($project);
        $git_repository->method('getFullName')->willReturn('myrepo');
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(12)->withTitle('PR Title')->withRepositoryDestinationId($git_repository->getId())->build();

        $git_exec = \Git_Exec::buildFromRepository($git_repository);
        $git_exec->init();

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $this->change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);
        $this->git_repository_factory->method('getRepositoryById')
            ->with($git_repository->getId())->willReturn($git_repository);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');
        $this->url_manager->method('getAbsoluteCommitURL')->willReturn('https://example.com/link-to-commit');

        return $event;
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $this->change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_dao = SearchPullRequestStub::withNoRow();

        $notifications = $this->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheUserUpdatingThePullRequestCannotBeFound(): void
    {
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(14)->build();

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $this->change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->user_manager = RetrieveUserByIdStub::withNoUser();

        $notifications = $this->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheDestinationRepositoryCannotBeFound(): void
    {
        $git_repository = new GitRepository();
        $git_repository->setId(404);
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(14)->withRepositoryDestinationId($git_repository->getId())->build();

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $this->change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $this->git_repository_factory->method('getRepositoryById')
            ->with($git_repository->getId())->willReturn(null);

        $notifications = $this->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheDestinationRepositoryDataAreNotAvailable(): void
    {
        $git_repository = $this->createMock(GitRepository::class);
        $git_repository->method('getId')->willReturn(2);
        $git_repository->method('getFullPath')->willReturn($this->getTmpDir() . '/myrepo.git');
        $git_repository->method('getGitRootPath')->willReturn($this->getTmpDir());
        $project = $this->createMock(\Project::class);
        $project->method('getUnixName')->willReturn('');
        $git_repository->method('getProject')->willReturn($project);
        $git_repository->method('getFullName')->willReturn('myrepo');
        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(12)->withTitle('PR Title')->withRepositoryDestinationId($git_repository->getId())->build();

        $event = PullRequestUpdatedEvent::fromPullRequestUserAndReferences(
            $pull_request,
            $this->change_user,
            'a7d1692502252a5ec18bfcae4184498b1459810c',
            'fbe4dade4f744aa203ec35bf09f71475ecc3f9d6',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319',
            '4682f1f1fb9ee3cf6ca518547ae5525c9768a319'
        );

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);
        $this->git_repository_factory->method('getRepositoryById')
            ->with($git_repository->getId())->willReturn($git_repository);

        $notifications = $this->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }
}
