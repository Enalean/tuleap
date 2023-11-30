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

namespace Tuleap\PullRequest\InlineComment\Notification;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\InlineCommentTestBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\FormatNotificationContentStub;
use Tuleap\PullRequest\Tests\Stub\InlineCommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use UserHelper;

final class PullRequestNewInlineCommentNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private RetrieveUserByIdStub $user_retriever;
    private InlineCommentSearcherStub $inline_comment_searcher;
    private OwnerRetriever & MockObject $owner_retriever;
    private InlineCommentCodeContextExtractor & MockObject $code_context_extractor;
    private UserHelper & MockObject $user_helper;
    private HTMLURLBuilder & MockObject $html_url_builder;
    private PullRequest $pull_request;
    private \PFUser $change_user;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->change_user  = UserTestBuilder::buildWithId(102);
        $this->pull_request = PullRequestTestBuilder::aPullRequestInReview()->build();

        $this->user_retriever          = RetrieveUserByIdStub::withUser($this->change_user);
        $this->pull_request_dao        = SearchPullRequestStub::withAtLeastOnePullRequest($this->pull_request);
        $this->code_context_extractor  = $this->createMock(InlineCommentCodeContextExtractor::class);
        $this->owner_retriever         = $this->createMock(OwnerRetriever::class);
        $this->user_helper             = $this->createMock(UserHelper::class);
        $this->html_url_builder        = $this->createMock(HTMLURLBuilder::class);
        $this->inline_comment_searcher = InlineCommentSearcherStub::withNoComment();
    }

    private function getNotifications(PullRequestNewInlineCommentEvent $event): array
    {
        $builder = new PullRequestNewInlineCommentNotificationToProcessBuilder(
            $this->user_retriever,
            new PullRequestRetriever($this->pull_request_dao),
            new InlineCommentRetriever($this->inline_comment_searcher),
            $this->owner_retriever,
            $this->code_context_extractor,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder,
            FormatNotificationContentStub::withDefault(),
        );
        return $builder->getNotificationsToProcess($event);
    }

    public function testBuildNewInlineCommentNotificationFromPullRequestNewInlineCommentEvent(): void
    {
        $owners  = [$this->change_user, UserTestBuilder::buildWithId(104), UserTestBuilder::buildWithId(105)];
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->change_user)
            ->build();

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->code_context_extractor->method('getCodeContext')->willReturn('+Some code');
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn(
            'https://example.com/link-to-pr'
        );

        $notifications = $this->getNotifications($event);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(PullRequestNewInlineCommentNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenTheInlineCommentCannotBeFound(): void
    {
        $this->inline_comment_searcher = InlineCommentSearcherStub::withNoComment();

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID(404);
        $this->assertEmpty($this->getNotifications($event));
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')->build();

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->pull_request_dao        = SearchPullRequestStub::withNoRow();

        $this->assertEmpty($this->getNotifications($event));
    }

    public function testNoNotificationIsBuiltWhenTheUserCommentingCannotBeFound(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')
            ->onPullRequest($this->pull_request)
            ->build();

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->user_retriever          = RetrieveUserByIdStub::withNoUser();

        $this->assertEmpty($this->getNotifications($event));
    }

    public function testNoNotificationIsBuiltWhenTheCodeContextExtractionFails(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->change_user)
            ->build();

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->owner_retriever->method('getOwners')->willReturn([$this->change_user]);

        $this->code_context_extractor->method('getCodeContext')
            ->willThrowException(
                new class extends InlineCommentCodeContextException {
                }
            );

        $this->assertEmpty($this->getNotifications($event));
    }
}
