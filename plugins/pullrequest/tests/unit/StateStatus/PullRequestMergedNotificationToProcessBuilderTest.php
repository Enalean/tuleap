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

namespace Tuleap\PullRequest\StateStatus;

use PFUser;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

final class PullRequestMergedNotificationToProcessBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
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
    private PullRequestMergedNotificationToProcessBuilder $builder;

    protected function setUp(): void
    {
        $this->user_manager         = $this->createMock(UserManager::class);
        $this->pull_request_factory = $this->createMock(Factory::class);
        $this->owner_retriever      = $this->createMock(OwnerRetriever::class);
        $this->user_helper          = $this->createMock(UserHelper::class);
        $this->html_url_builder     = $this->createMock(HTMLURLBuilder::class);

        $this->builder = new PullRequestMergedNotificationToProcessBuilder(
            $this->user_manager,
            $this->pull_request_factory,
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder
        );
    }

    public function testBuildMergeNotificationFromPullRequestMergedEvent(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(12);
        $pull_request->method('getTitle')->willReturn('PR Title');
        $pull_request->method('getBranchDest')->willReturn('master');
        $change_user = $this->buildUser(102);
        $owners      = [$change_user, $this->buildUser(104), $this->buildUser(105)];

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_factory->method('getPullRequestById')
            ->with($pull_request->getId())->willReturn($pull_request);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn($change_user);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertCount(1, $notifications);
        self::assertInstanceOf(PullRequestMergedNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCanNoBeFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(404);
        $change_user = $this->buildUser(102);

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_factory->method('getPullRequestById')->willThrowException(new PullRequestNotFoundException());

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheUserAbandoningThePullRequestCannotBeFound(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(13);
        $change_user = $this->buildUser(102);

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_factory->method('getPullRequestById')->willReturn($pull_request);
        $this->user_manager->method('getUserById')
            ->with($change_user->getId())->willReturn(null);

        $notifications = $this->builder->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
