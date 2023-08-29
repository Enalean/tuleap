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

use PFUser;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

final class PullRequestNewInlineCommentNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Factory
     */
    private $pull_request_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&InlineCommentRetriever
     */
    private $inline_comment_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OwnerRetriever
     */
    private $owner_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&InlineCommentCodeContextExtractor
     */
    private $code_context_extractor;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserHelper
     */
    private $user_helper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&HTMLURLBuilder
     */
    private $html_url_builder;
    private PullRequestNewInlineCommentNotificationToProcessBuilder $builder;

    protected function setUp(): void
    {
        $this->user_manager             = $this->createMock(UserManager::class);
        $this->pull_request_factory     = $this->createMock(Factory::class);
        $this->inline_comment_retriever = $this->createMock(InlineCommentRetriever::class);
        $this->code_context_extractor   = $this->createMock(InlineCommentCodeContextExtractor::class);
        $this->owner_retriever          = $this->createMock(OwnerRetriever::class);
        $this->user_helper              = $this->createMock(UserHelper::class);
        $this->html_url_builder         = $this->createMock(HTMLURLBuilder::class);

        $this->builder = new PullRequestNewInlineCommentNotificationToProcessBuilder(
            $this->user_manager,
            $this->pull_request_factory,
            $this->inline_comment_retriever,
            $this->owner_retriever,
            $this->code_context_extractor,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder
        );
    }

    public function testBuildNewInlineCommentNotificationFromPullRequestNewInlineCommentEvent(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR Title');
        $change_user = $this->buildUser(102);
        $owners      = [$change_user, $this->buildUser(104), $this->buildUser(105)];
        $comment     = $this->buildInlineComment(41, (int) $change_user->getId(), $pull_request->getId());

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_retriever->method('getInlineCommentByID')
            ->with($comment->getId())->willReturn($comment);
        $this->pull_request_factory->method('getPullRequestById')
            ->with($pull_request->getId())->willReturn($pull_request);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn($change_user);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->code_context_extractor->method('getCodeContext')->willReturn('+Some code');
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(PullRequestNewInlineCommentNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenTheInlineCommentCannotBeFound(): void
    {
        $comment = $this->buildInlineComment(404, 102, 12);

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn(null);

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $comment = $this->buildInlineComment(14, 102, 404);

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn($comment);
        $this->pull_request_factory->method('getPullRequestById')->willThrowException(new PullRequestNotFoundException());

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenTheUserCommentingCannotBeFound(): void
    {
        $comment = $this->buildInlineComment(14, 404, 16);

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn($comment);
        $this->pull_request_factory->method('getPullRequestById')->willReturn($this->createMock(PullRequest::class));
        $this->user_manager->method('getUserById')->willReturn(null);

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenTheCodeContextExtractionFails(): void
    {
        $change_user = $this->buildUser(102);
        $comment     = $this->buildInlineComment(14, (int) $change_user->getId(), 16);

        $event = PullRequestNewInlineCommentEvent::fromInlineCommentID($comment->getId());

        $this->inline_comment_retriever->method('getInlineCommentByID')->willReturn($comment);
        $this->pull_request_factory->method('getPullRequestById')->willReturn($this->createMock(PullRequest::class));
        $this->user_manager->method('getUserById')->with($change_user->getId())->willReturn($change_user);
        $this->owner_retriever->method('getOwners')->willReturn([$change_user]);

        $this->code_context_extractor->method('getCodeContext')
            ->willThrowException(
                new class extends InlineCommentCodeContextException
                {
                }
            );

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }

    private function buildInlineComment(int $comment_id, int $user_id, int $pr_id): InlineComment
    {
        return new InlineComment(
            $comment_id,
            $pr_id,
            $user_id,
            10,
            'file_/path',
            2,
            'My comment',
            false,
            0,
            "right",
            "",
            Comment::FORMAT_TEXT
        );
    }
}
