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

namespace Tuleap\PullRequest\Notification;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\Timeline\Dao as TimelineDAO;
use UserManager;

final class OwnerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReviewerRetriever
     */
    private $reviewer_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TimelineDAO
     */
    private $timeline_dao;

    /**
     * @var OwnerRetriever
     */
    private $owner_retriever;

    protected function setUp(): void
    {
        $this->user_manager       = \Mockery::mock(UserManager::class);
        $this->reviewer_retriever = \Mockery::mock(ReviewerRetriever::class);
        $this->timeline_dao       = \Mockery::mock(TimelineDAO::class);

        $this->owner_retriever = new OwnerRetriever(
            $this->user_manager,
            $this->reviewer_retriever,
            $this->timeline_dao
        );
    }

    public function testOwnersArePullRequestCreatorUpdatersAndReviewers(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(74);

        $user_102 = \Mockery::mock(\PFUser::class);
        $user_103 = \Mockery::mock(\PFUser::class);
        $user_104 = \Mockery::mock(\PFUser::class);

        $this->reviewer_retriever->shouldReceive('getReviewers')->andReturn([$user_102]);

        $pull_request->shouldReceive('getUserId')->andReturn(103);
        $user_103->shouldReceive('getId')->andReturn(103);
        $this->user_manager->shouldReceive('getUserById')->with(103)->andReturn($user_103);

        $this->timeline_dao->shouldReceive('searchUserIDsByPullRequestIDAndEventType')->andReturn([
            ['user_id' => 104]
        ]);
        $this->user_manager->shouldReceive('getUserById')->with(104)->andReturn($user_104);

        $owners = $this->owner_retriever->getOwners($pull_request);

        $this->assertEqualsCanonicalizing([$user_102, $user_103, $user_104], $owners);
    }

    public function testAUserCanOnlyBeAOwnerOnce(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(75);

        $user_105 = \Mockery::mock(\PFUser::class);
        $user_105->shouldReceive('getId')->andReturn(105);

        $pull_request->shouldReceive('getUserId')->andReturn(105);
        $this->reviewer_retriever->shouldReceive('getReviewers')->andReturn([$user_105]);
        $this->timeline_dao->shouldReceive('searchUserIDsByPullRequestIDAndEventType')->andReturn([
            ['user_id' => 105]
        ]);
        $this->user_manager->shouldReceive('getUserById')->with(105)->andReturn($user_105);

        $this->assertCount(1, $this->owner_retriever->getOwners($pull_request));
    }
}
