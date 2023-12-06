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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use UserHelper;

final class PullRequestMergedNotificationToProcessBuilderTest extends TestCase
{
    private RetrieveUserByIdStub $user_manager;
    private MockObject&OwnerRetriever $owner_retriever;
    private MockObject&UserHelper $user_helper;
    private MockObject&HTMLURLBuilder $html_url_builder;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->user_manager     = RetrieveUserByIdStub::withNoUser();
        $this->pull_request_dao = SearchPullRequestStub::withNoRow();
        $this->owner_retriever  = $this->createMock(OwnerRetriever::class);
        $this->user_helper      = $this->createMock(UserHelper::class);
        $this->html_url_builder = $this->createMock(HTMLURLBuilder::class);
    }

    private function getNotificationsToProcess(PullRequestMergedEvent $event): array
    {
        $builder = new PullRequestMergedNotificationToProcessBuilder(
            $this->user_manager,
            new PullRequestRetriever($this->pull_request_dao),
            $this->owner_retriever,
            new FilterUserFromCollection(),
            $this->user_helper,
            $this->html_url_builder
        );

        return $builder->getNotificationsToProcess($event);
    }

    public function testBuildMergeNotificationFromPullRequestMergedEvent(): void
    {
        $pull_request = PullRequestTestBuilder::aMergedPullRequest()->withId(12)->withTitle('PR Title')->build();
        $change_user  = UserTestBuilder::buildWithId(102);
        $owners       = [$change_user, UserTestBuilder::buildWithId(104), UserTestBuilder::buildWithId(105)];

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);
        $this->user_manager     = RetrieveUserByIdStub::withUser($change_user);
        $this->owner_retriever->method('getOwners')->willReturn($owners);
        $this->user_helper->method('getDisplayNameFromUser')->willReturn('Display name');
        $this->user_helper->method('getAbsoluteUserURL')->willReturn('https://example.com/users/foo');
        $this->html_url_builder->method('getAbsolutePullRequestOverviewUrl')->willReturn('https://example.com/link-to-pr');

        $notifications = $this->getNotificationsToProcess($event);
        self::assertCount(1, $notifications);
        self::assertInstanceOf(PullRequestMergedNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCanNoBeFound(): void
    {
        $pull_request = PullRequestTestBuilder::aMergedPullRequest()->withId(404)->build();
        $change_user  = UserTestBuilder::buildWithId(102);

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $notifications = $this->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheUserAbandoningThePullRequestCannotBeFound(): void
    {
        $pull_request = PullRequestTestBuilder::aMergedPullRequest()->withId(13)->build();
        $change_user  = UserTestBuilder::buildWithId(102);

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $notifications = $this->getNotificationsToProcess($event);
        self::assertEmpty($notifications);
    }
}
