<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
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
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UpdatedInlineCommentNotificationToProcessBuilderTest extends TestCase
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

    private function getNotifications(UpdatedInlineCommentEvent $event): array
    {
        $builder = new UpdatedInlineCommentNotificationToProcessBuilder(
            $this->user_retriever,
            new PullRequestRetriever($this->pull_request_dao),
            new InlineCommentRetriever($this->inline_comment_searcher),
            $this->owner_retriever,
            $this->code_context_extractor,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder,
            FormatNotificationContentStub::withDefault(),
            new MentionedUserInTextRetriever(ProvideAndRetrieveUserStub::build(UserTestBuilder::buildWithId(1510)))
        );
        return $builder->getNotificationsToProcess($event);
    }

    public function testBuildUpdatedInlineCommentNotificationFromPullRequestUpdatedInlineCommentEvent(): void
    {
        $owners  = [$this->change_user, UserTestBuilder::buildWithId(104), UserTestBuilder::buildWithId(105)];
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->change_user)
            ->build();

        $event = UpdatedInlineCommentEvent::fromInlineComment($comment);

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->code_context_extractor->method('getCodeContext')->willReturn('+Some code');
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn(
            'https://example.com/link-to-pr'
        );

        $notifications = $this->getNotifications($event);
        self::assertCount(1, $notifications);
        self::assertInstanceOf(UpdatedInlineCommentNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenTheInlineCommentCannotBeFound(): void
    {
        $this->inline_comment_searcher = InlineCommentSearcherStub::withNoComment();

        $event = UpdatedInlineCommentEvent::fromInlineComment(InlineCommentTestBuilder::aMarkdownComment('No comment')->withId(404)->build());
        self::assertEmpty($this->getNotifications($event));
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')->build();

        $event = UpdatedInlineCommentEvent::fromInlineComment($comment);

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->pull_request_dao        = SearchPullRequestStub::withNoRow();

        self::assertEmpty($this->getNotifications($event));
    }

    public function testNoNotificationIsBuiltWhenTheUserCommentingCannotBeFound(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')
            ->onPullRequest($this->pull_request)
            ->build();

        $event = UpdatedInlineCommentEvent::fromInlineComment($comment);

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->user_retriever          = RetrieveUserByIdStub::withNoUser();

        self::assertEmpty($this->getNotifications($event));
    }

    public function testNoNotificationIsBuiltWhenTheCodeContextExtractionFails(): void
    {
        $comment = InlineCommentTestBuilder::aMarkdownComment('nonintellectual radialization')
            ->onPullRequest($this->pull_request)
            ->byAuthor($this->change_user)
            ->build();

        $event = UpdatedInlineCommentEvent::fromInlineComment($comment);

        $this->inline_comment_searcher = InlineCommentSearcherStub::withComment($comment);
        $this->owner_retriever->method('getOwners')->willReturn([$this->change_user]);

        $this->code_context_extractor->method('getCodeContext')
            ->willThrowException(
                new class extends InlineCommentCodeContextException {
                }
            );

        self::assertEmpty($this->getNotifications($event));
    }
}
