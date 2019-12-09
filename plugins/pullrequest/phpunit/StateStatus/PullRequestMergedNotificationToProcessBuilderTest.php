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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\OwnerRetriever;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

final class PullRequestMergedNotificationToProcessBuilderTest extends TestCase
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
        $this->owner_retriever           = \Mockery::mock(OwnerRetriever::class);
        $this->user_helper               = \Mockery::mock(UserHelper::class);
        $this->html_url_builder          = \Mockery::mock(HTMLURLBuilder::class);

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
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(12);
        $pull_request->shouldReceive('getTitle')->andReturn('PR Title');
        $pull_request->shouldReceive('getBranchDest')->andReturn('master');
        $change_user   = $this->buildUser(102);
        $owners        = [$change_user, $this->buildUser(104), $this->buildUser(105)];

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

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
        $this->assertInstanceOf(PullRequestMergedNotification::class, $notifications[0]);
    }

    public function testNoNotificationIsBuiltWhenThePullRequestCanNoBeFound(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(404);
        $change_user = $this->buildUser(102);

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_factory->shouldReceive('getPullRequestById')->andThrow(PullRequestNotFoundException::class);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertEmpty($notifications);
    }

    public function testNoNotificationIsBuiltWhenTheUserAbandoningThePullRequestCannotBeFound(): void
    {
        $pull_request  = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(13);
        $change_user = $this->buildUser(102);

        $event = PullRequestMergedEvent::fromPullRequestAndUserMergingThePullRequest($pull_request, $change_user);

        $this->pull_request_factory->shouldReceive('getPullRequestById')->andReturn($pull_request);
        $this->user_manager->shouldReceive('getUserById')
            ->with($change_user->getId())->andReturn(null);

        $notifications = $this->builder->getNotificationsToProcess($event);
        $this->assertEmpty($notifications);
    }

    private function buildUser(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
