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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\StateStatus\PullRequestAbandonedNotificationToProcessBuilder;
use UserHelper;
use UserManager;

final class PullRequestNewCommentNotificationToProcessBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Factory
     */
    private $pull_request_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\PullRequest\Comment\Factory
     */
    private $comment_factory;
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
     * @var PullRequestAbandonedNotificationToProcessBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->user_manager              = \Mockery::mock(UserManager::class);
        $this->pull_request_factory      = \Mockery::mock(Factory::class);
        $this->comment_factory           = \Mockery::mock(\Tuleap\PullRequest\Comment\Factory::class);
        $this->owner_retriever           = \Mockery::mock(OwnerRetriever::class);
        $this->user_helper               = \Mockery::mock(UserHelper::class);
        $this->html_url_builder          = \Mockery::mock(HTMLURLBuilder::class);

        $this->builder = new PullRequestNewCommentNotificationToProcessBuilder(
            $this->user_manager,
            $this->pull_request_factory,
            $this->comment_factory,
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder
        );
    }

    public function testBuildNewCommentNotificationFromPullRequestNewCommentEvent(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $pull_request->shouldReceive('getTitle')->andReturn('PR Title');
        $change_user   = $this->buildUser(102);
        $owners        = [$change_user, $this->buildUser(104), $this->buildUser(105)];
        $comment       = $this->buildComment(41, $change_user->getId(), $pull_request->getId());

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_factory->shouldReceive('getCommentByID')
            ->with($comment->getId())->andReturn($comment);
        $this->pull_request_factory->shouldReceive('getPullRequestById')
            ->with($pull_request->getId())->andReturn($pull_request);
        $this->user_manager->shouldReceive('getUserById')
            ->with($change_user->getId())->andReturn($change_user);
        $this->owner_retriever->shouldReceive('getOwners')->andReturn($owners);
        $this->user_helper->shouldReceive('getDisplayNameFromUser')->andReturn('Display name');
        $this->user_helper->shouldReceive('getAbsoluteUserURL')->andReturn('https://example.com/users/foo');
        $this->html_url_builder->shouldReceive('getAbsolutePullRequestOverviewUrl')->andReturn('https://example.com/link-to-pr');

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertCount(1, $notifications);
        $this->assertInstanceOf(PullRequestNewCommentNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenTheCommentCannotBeFound(): void
    {
        $comment = $this->buildComment(404, 102, 12);

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_factory->shouldReceive('getCommentByID')->andReturn(null);

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCannotBeFound(): void
    {
        $comment     = $this->buildComment(14, 102, 404);

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_factory->shouldReceive('getCommentByID')->andReturn($comment);
        $this->pull_request_factory->shouldReceive('getPullRequestById')->andThrow(PullRequestNotFoundException::class);

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
    }

    public function testNoNotificationIsBuiltWhenTheUserCommentingCannotBeFound(): void
    {
        $comment     = $this->buildComment(14, 404, 16);

        $event = PullRequestNewCommentEvent::fromCommentID($comment->getId());

        $this->comment_factory->shouldReceive('getCommentByID')->andReturn($comment);
        $this->pull_request_factory->shouldReceive('getPullRequestById')->andReturn(\Mockery::mock(PullRequest::class));
        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $this->assertEmpty($this->builder->getNotificationsToProcess($event));
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
            'My comment'
        );
    }
}
