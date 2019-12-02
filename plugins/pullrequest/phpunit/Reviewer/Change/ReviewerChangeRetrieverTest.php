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

namespace Tuleap\PullRequest\Reviewer\Change;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\PullRequest;

final class ReviewerChangeRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ReviewerChangeDAO
     */
    private $reviewer_change_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var ReviewerChangeRetriever
     */
    private $reviewer_change_retriever;

    protected function setUp(): void
    {
        $this->reviewer_change_dao = \Mockery::mock(ReviewerChangeDAO::class);
        $this->user_manager        = \Mockery::mock(\UserManager::class);

        $this->reviewer_change_retriever = new ReviewerChangeRetriever($this->reviewer_change_dao, $this->user_manager);
    }

    public function testRetrieveListOfReviewerChangesOfAPullRequest(): void
    {
        $pull_request = \Mockery::mock(PullRequest::class);
        $pull_request->shouldReceive('getId')->andReturn(63);

        $user_1_id       = 102;
        $user_2_id       = 103;
        $user_3_id       = 104;
        $unknown_user_id = 404;

        $this->user_manager->shouldReceive('getUserById')->with($user_1_id)->andReturn($this->buildUserWithID($user_1_id));
        $this->user_manager->shouldReceive('getUserById')->with($user_2_id)->andReturn($this->buildUserWithID($user_2_id));
        $this->user_manager->shouldReceive('getUserById')->with($user_3_id)->andReturn($this->buildUserWithID($user_3_id));
        $this->user_manager->shouldReceive('getUserById')->with($unknown_user_id)->andReturn(null);

        $valid_change_timestamp = 1575044496;

        $this->reviewer_change_dao->shouldReceive('searchByPullRequestID')->with($pull_request->getId())->andReturn([
            12 => [
                [
                    'change_date'      => 1575044496,
                    'change_user_id'   => $user_1_id,
                    'reviewer_user_id' => $user_2_id,
                    'is_removal'       => 0
                ],
                [
                    'change_date'      => 1575044496,
                    'change_user_id'   => $user_1_id,
                    'reviewer_user_id' => $user_3_id,
                    'is_removal'       => 1
                ],
                [
                    'change_date'      => 1575044496,
                    'change_user_id'   => $user_1_id,
                    'reviewer_user_id' => $unknown_user_id,
                    'is_removal'       => 1
                ],
            ],
            168 => [
                [
                    'change_date'      => 1575051696,
                    'change_user_id'   => $unknown_user_id,
                    'reviewer_user_id' => $user_2_id,
                    'is_removal'       => 0
                ]
            ]
        ]);

        $changes = $this->reviewer_change_retriever->getChangesForPullRequest($pull_request);
        $this->assertCount(1, $changes);
        $valid_change = $changes[0];
        $this->assertEquals($valid_change_timestamp, $valid_change->changedAt()->getTimestamp());
        $this->assertEquals($user_1_id, $valid_change->changedBy()->getId());
        $this->assertCount(1, $valid_change->getAddedReviewers());
        $this->assertCount(1, $valid_change->getRemovedReviewers());
    }

    private function buildUserWithID(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
