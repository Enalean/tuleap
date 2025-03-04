<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Notification;

use Tuleap\Git\RetrieveGitRepository;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\Tests\Stub\RetrieveGitRepositoryStub;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\SearchPullRequest;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ContentInterpretorStub;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewPullRequestNotificationToProcessBuilderTest extends TestCase
{
    private function getNotificationToProcess(
        NewPullRequestEvent $event,
        SearchPullRequest $pull_request_retriever,
        RetrieveGitRepository $repository_retriever,
    ): array {
        $user             = UserTestBuilder::buildWithId(101);
        $html_url_builder = $this->createMock(HTMLURLBuilder::class);
        $builder          = new NewPullRequestNotificationToProcessBuilder(
            new PullRequestRetriever($pull_request_retriever),
            RetrieveUserByIdStub::withUser($user),
            $repository_retriever,
            new MentionedUserInTextRetriever(ProvideAndRetrieveUserStub::build($user)),
            new FilterUserFromCollection(),
            $html_url_builder,
            ContentInterpretorStub::withInterpretedText(''),
        );
        $html_url_builder->method('getAbsolutePullRequestOverviewUrl');

        return $builder->getNotificationsToProcess($event);
    }

    public function testItBuildsNotificationToProcess(): void
    {
        $notifications = $this->getNotificationToProcess(
            NewPullRequestEvent::fromPullRequestId(2),
            SearchPullRequestStub::withAtLeastOnePullRequest(
                PullRequestTestBuilder::aPullRequestInReview()
                    ->withId(2)
                    ->withRepositoryId(34)
                    ->createdBy(101)
                    ->build()
            ),
            RetrieveGitRepositoryStub::withGitRepository(GitRepositoryTestBuilder::aProjectRepository()->withId(34)->build()),
        );
        self::assertCount(1, $notifications);
        self::assertInstanceOf(NewPullRequestNotification::class, $notifications[0]);
    }

    public function testItReturnsEmptyIfPullRequestNotFound(): void
    {
        self::assertSame([], $this->getNotificationToProcess(
            NewPullRequestEvent::fromPullRequestId(2),
            SearchPullRequestStub::withNoRow(),
            RetrieveGitRepositoryStub::withGitRepository(GitRepositoryTestBuilder::aProjectRepository()->withId(34)->build()),
        ));
    }

    public function testItReturnsEmptyIfPullRequestOwnerNotFound(): void
    {
        self::assertSame([], $this->getNotificationToProcess(
            NewPullRequestEvent::fromPullRequestId(2),
            SearchPullRequestStub::withAtLeastOnePullRequest(
                PullRequestTestBuilder::aPullRequestInReview()
                    ->withId(2)
                    ->withRepositoryId(34)
                    ->createdBy(324)
                    ->build()
            ),
            RetrieveGitRepositoryStub::withGitRepository(GitRepositoryTestBuilder::aProjectRepository()->withId(34)->build()),
        ));
    }

    public function testItReturnsEmptyIfPullRequestRepositoryNotFound(): void
    {
        self::assertSame([], $this->getNotificationToProcess(
            NewPullRequestEvent::fromPullRequestId(2),
            SearchPullRequestStub::withAtLeastOnePullRequest(
                PullRequestTestBuilder::aPullRequestInReview()
                    ->withId(2)
                    ->withRepositoryId(34)
                    ->createdBy(101)
                    ->build()
            ),
            RetrieveGitRepositoryStub::withoutGitRepository(),
        ));
    }
}
