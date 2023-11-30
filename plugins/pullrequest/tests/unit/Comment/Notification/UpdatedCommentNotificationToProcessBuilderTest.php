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

namespace Tuleap\PullRequest\Comment\Notification;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\CommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\FormatNotificationContentStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use UserHelper;

final class UpdatedCommentNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RetrieveUserByIdStub $user_retriever;
    private OwnerRetriever & MockObject $owner_retriever;
    private UserHelper & MockObject $user_helper;
    private HTMLURLBuilder & MockObject $html_url_builder;
    private CommentSearcherStub $comment_dao;
    private PullRequest $pull_request;
    private \PFUser $change_user;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();
        $this->change_user  = UserTestBuilder::buildWithId(102);

        $this->user_retriever   = RetrieveUserByIdStub::withUser($this->change_user);
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($this->pull_request);
        $this->comment_dao      = CommentSearcherStub::withNoComment();
        $this->owner_retriever  = $this->createMock(OwnerRetriever::class);
        $this->user_helper      = $this->createMock(UserHelper::class);
        $this->html_url_builder = $this->createMock(HTMLURLBuilder::class);
    }

    private function getNotificationsToProcess(UpdatedCommentEvent $event): array
    {
        $builder = new UpdatedCommentNotificationToProcessBuilder(
            $this->user_retriever,
            new PullRequestRetriever($this->pull_request_dao),
            new CommentRetriever($this->comment_dao),
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder,
            FormatNotificationContentStub::withDefault(),
        );
        return $builder->getNotificationsToProcess($event);
    }

    public function testBuildUpdatedCommentNotificationFromPullRequestUpdatedCommentEvent(): void
    {
        $owners  = [$this->change_user, UserTestBuilder::buildWithId(104), UserTestBuilder::buildWithId(105)];
        $comment = CommentTestBuilder::aMarkdownComment('alodiary commandant')
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->change_user)
            ->build();

        $event = UpdatedCommentEvent::fromUpdatedComment($comment);

        $this->comment_dao = CommentSearcherStub::withComment($comment);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn(
            'https://example.com/link-to-pr'
        );

        $notifications = $this->getNotificationsToProcess($event);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(UpdatedCommentNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenTheCommentCannotBeFound(): void
    {
        $this->comment_dao = CommentSearcherStub::withNoComment();

        $event = UpdatedCommentEvent::fromUpdatedComment(CommentTestBuilder::aMarkdownComment('alodiary commandant')->withId(404)->build());
        $this->assertEmpty($this->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $comment = CommentTestBuilder::aMarkdownComment('alodiary commandant')->build();

        $event = UpdatedCommentEvent::fromUpdatedComment($comment);

        $this->comment_dao      = CommentSearcherStub::withComment($comment);
        $this->pull_request_dao = SearchPullRequestStub::withNoRow();

        $this->assertEmpty($this->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenTheUserCommentingCannotBeFound(): void
    {
        $comment = CommentTestBuilder::aMarkdownComment('alodiary commandant')
            ->onPullRequest($this->pull_request)
            ->build();

        $event = UpdatedCommentEvent::fromUpdatedComment($comment);

        $this->comment_dao      = CommentSearcherStub::withComment($comment);
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($this->pull_request);
        $this->user_retriever   = RetrieveUserByIdStub::withNoUser();

        $this->assertEmpty($this->getNotificationsToProcess($event));
    }
}
