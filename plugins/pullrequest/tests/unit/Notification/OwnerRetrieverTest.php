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

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\Timeline\Dao as TimelineDAO;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class OwnerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReviewerRetriever
     */
    private $reviewer_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TimelineDAO
     */
    private $timeline_dao;
    private OwnerRetriever $owner_retriever;

    protected function setUp(): void
    {
        $this->user_manager       = $this->createMock(UserManager::class);
        $this->reviewer_retriever = $this->createMock(ReviewerRetriever::class);
        $this->timeline_dao       = $this->createMock(TimelineDAO::class);

        $this->owner_retriever = new OwnerRetriever(
            $this->user_manager,
            $this->reviewer_retriever,
            $this->timeline_dao
        );
    }

    public function testOwnersArePullRequestCreatorUpdatersAndReviewers(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(74);

        $user_102 = UserTestBuilder::aUser()->withId(102)->build();
        $user_103 = UserTestBuilder::aUser()->withId(103)->build();
        $user_104 = UserTestBuilder::aUser()->withId(104)->build();

        $this->reviewer_retriever->method('getReviewers')->willReturn([$user_102]);

        $pull_request->method('getUserId')->willReturn(103);

        $this->timeline_dao->method('searchUserIDsByPullRequestIDAndEventType')->willReturn([
            ['user_id' => 104],
        ]);

        $this->user_manager->method('getUserById')->willReturnMap([
            [103, $user_103],
            [104, $user_104],
        ]);

        $owners = $this->owner_retriever->getOwners($pull_request);

        self::assertEqualsCanonicalizing([$user_102, $user_103, $user_104], $owners);
    }

    public function testAUserCanOnlyBeAOwnerOnce(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(75);

        $user_105 = UserTestBuilder::aUser()->withId(105)->build();

        $pull_request->method('getUserId')->willReturn(105);
        $this->reviewer_retriever->method('getReviewers')->willReturn([$user_105]);
        $this->timeline_dao->method('searchUserIDsByPullRequestIDAndEventType')->willReturn([
            ['user_id' => 105],
        ]);
        $this->user_manager->method('getUserById')->with(105)->willReturn($user_105);

        self::assertCount(1, $this->owner_retriever->getOwners($pull_request));
    }
}
