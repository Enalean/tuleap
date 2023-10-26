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

use PFUser;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentRetriever;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Stub\CommentSearcherStub;
use Tuleap\PullRequest\Tests\Stub\FormatNotificationContentStub;
use UserHelper;
use UserManager;

final class PullRequestNewCommentNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
    private CommentSearcherStub $comment_dao;

    protected function setUp(): void
    {
        $this->user_manager         = $this->createMock(UserManager::class);
        $this->pull_request_factory = $this->createMock(Factory::class);
        $this->comment_dao          = CommentSearcherStub::withDefaultRow();
        $this->owner_retriever      = $this->createMock(OwnerRetriever::class);
        $this->user_helper          = $this->createMock(UserHelper::class);
        $this->html_url_builder     = $this->createMock(HTMLURLBuilder::class);
    }

    private function getNotificationsToProcess(PullRequestNewCommentEvent $event): array
    {
        $builder = new PullRequestNewCommentNotificationToProcessBuilder(
            $this->user_manager,
            $this->pull_request_factory,
            new CommentRetriever($this->comment_dao),
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder,
            FormatNotificationContentStub::withDefault(),
        );
        return $builder->getNotificationsToProcess($event);
    }

    public function testBuildNewCommentNotificationFromPullRequestNewCommentEvent(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR Title');
        $change_user = $this->buildUser(102);
        $owners      = [$change_user, $this->buildUser(104), $this->buildUser(105)];
        $comment     = $this->buildComment(41, (int) $change_user->getId(), $pull_request->getId());

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_dao = CommentSearcherStub::fromComment($comment);
        $this->pull_request_factory->method('getPullRequestById')
                ->with($pull_request->getId())->willReturn($pull_request);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn($change_user);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');

        $notifications = $this->getNotificationsToProcess($event);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(PullRequestNewCommentNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenTheCommentCannotBeFound(): void
    {
        $comment = $this->buildComment(404, 102, 12);

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_dao = CommentSearcherStub::withNoRow();

        $this->assertEmpty($this->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $comment = $this->buildComment(14, 102, 404);

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_dao = CommentSearcherStub::fromComment($comment);

        $this->pull_request_factory->method('getPullRequestById')->willThrowException(new PullRequestNotFoundException());

        $this->assertEmpty($this->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenTheUserCommentingCannotBeFound(): void
    {
        $comment = $this->buildComment(14, 404, 16);

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_dao = CommentSearcherStub::fromComment($comment);
        $this->pull_request_factory->method('getPullRequestById')->willReturn($this->createMock(PullRequest::class));
        $this->user_manager->method('getUserById')->willReturn(null);

        $this->assertEmpty($this->getNotificationsToProcess($event));
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }

    private function buildComment(int $comment_id, int $user_id, int $pr_id): Comment
    {
        return new Comment(
            $comment_id,
            $pr_id,
            $user_id,
            10,
            'My comment',
            0,
            "inca-silver",
            TimelineComment::FORMAT_TEXT
        );
    }
}
