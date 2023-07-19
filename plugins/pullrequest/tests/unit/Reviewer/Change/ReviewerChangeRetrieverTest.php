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

use PFUser;
use Tuleap\PullRequest\Exception\PullRequestNotFoundException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;

final class ReviewerChangeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReviewerChangeDAO
     */
    private $reviewer_change_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Factory
     */
    private $pull_request_factory;
    private ReviewerChangeRetriever $reviewer_change_retriever;

    protected function setUp(): void
    {
        $this->reviewer_change_dao  = $this->createMock(ReviewerChangeDAO::class);
        $this->pull_request_factory = $this->createMock(Factory::class);
        $this->user_manager         = $this->createMock(\UserManager::class);

        $this->reviewer_change_retriever = new ReviewerChangeRetriever(
            $this->reviewer_change_dao,
            $this->pull_request_factory,
            $this->user_manager
        );
    }

    public function testRetrieveListOfReviewerChangesOfAPullRequest(): void
    {
        $pull_request = $this->createMock(PullRequest::class);
        $pull_request->method('getId')->willReturn(63);

        $user_1_id       = 102;
        $user_2_id       = 103;
        $user_3_id       = 104;
        $unknown_user_id = 404;

        $this->user_manager->method('getUserById')->willReturnMap([
            [$user_1_id, $this->buildUserWithID($user_1_id)],
            [$user_2_id, $this->buildUserWithID($user_2_id)],
            [$user_3_id, $this->buildUserWithID($user_3_id)],
            [$unknown_user_id, null],
        ]);

        $valid_change_timestamp = 1575044496;

        $this->reviewer_change_dao->method('searchByPullRequestID')->with($pull_request->getId())->willReturn([
            12 => [
                [
                    'change_date'      => 1575044496,
                    'change_user_id'   => $user_1_id,
                    'reviewer_user_id' => $user_2_id,
                    'is_removal'       => 0,
                ],
                [
                    'change_date'      => 1575044496,
                    'change_user_id'   => $user_1_id,
                    'reviewer_user_id' => $user_3_id,
                    'is_removal'       => 1,
                ],
                [
                    'change_date'      => 1575044496,
                    'change_user_id'   => $user_1_id,
                    'reviewer_user_id' => $unknown_user_id,
                    'is_removal'       => 1,
                ],
            ],
            168 => [
                [
                    'change_date'      => 1575051696,
                    'change_user_id'   => $unknown_user_id,
                    'reviewer_user_id' => $user_2_id,
                    'is_removal'       => 0,
                ],
            ],
        ]);

        $changes = $this->reviewer_change_retriever->getChangesForPullRequest($pull_request);
        self::assertCount(1, $changes);
        $valid_change = $changes[0];
        self::assertEquals($valid_change_timestamp, $valid_change->changedAt()->getTimestamp());
        self::assertEquals($user_1_id, $valid_change->changedBy()->getId());
        self::assertCount(1, $valid_change->getAddedReviewers());
        self::assertCount(1, $valid_change->getRemovedReviewers());
    }

    public function testCanRetrieveChangeWithAssociatedPullRequest(): void
    {
        $change_id       = 852;
        $pull_request_id = 11;

        $user_1_id = 102;
        $user_2_id = 103;

        $this->reviewer_change_dao->method('searchByChangeID')->with($change_id)->willReturn([
            [
                'pull_request_id'  => $pull_request_id,
                'change_date'      => 1575293481,
                'change_user_id'   => $user_1_id,
                'reviewer_user_id' => $user_2_id,
                'is_removal'       => 0,
            ],
        ]);

        $this->user_manager->method('getUserById')->willReturnMap([
            [$user_1_id, $this->buildUserWithID($user_1_id)],
            [$user_2_id, $this->buildUserWithID($user_2_id)],
        ]);

        $pull_request = $this->createMock(PullRequest::class);
        $this->pull_request_factory->method('getPullRequestById')->with($pull_request_id)
            ->willReturn($pull_request);

        $change_pull_request_association = $this->reviewer_change_retriever->getChangeWithTheAssociatedPullRequestByID($change_id);

        self::assertNotNull($change_pull_request_association);
        self::assertSame($pull_request, $change_pull_request_association->getPullRequest());
        $reviewer_change = $change_pull_request_association->getReviewerChange();
        self::assertEquals($user_1_id, $reviewer_change->changedBy()->getId());
        self::assertCount(1, $reviewer_change->getAddedReviewers());
        self::assertEquals($user_2_id, $reviewer_change->getAddedReviewers()[0]->getId());
        self::assertEmpty($reviewer_change->getRemovedReviewers());
    }

    public function testReviewerChangeIsNotReturnedWhenTheAssociatedPullRequestCannotBeFound(): void
    {
        $change_id = 854;

        $this->reviewer_change_dao->method('searchByChangeID')->with($change_id)->willReturn([
            [
                'pull_request_id'  => 11,
                'change_date'      => 1575293581,
                'change_user_id'   => 102,
                'reviewer_user_id' => 102,
                'is_removal'       => 0,
            ],
        ]);

        $this->pull_request_factory->method('getPullRequestById')->willThrowException(new PullRequestNotFoundException());

        $change_pull_request_association = $this->reviewer_change_retriever->getChangeWithTheAssociatedPullRequestByID($change_id);

        self::assertNull($change_pull_request_association);
    }

    public function testReviewerChangeAssociatedWithThePullRequestIsNotReturnedWhenTheUsersLinkedToItAreNotFound(): void
    {
        $change_id = 855;

        $this->reviewer_change_dao->method('searchByChangeID')->with($change_id)->willReturn([
            [
                'pull_request_id'  => 11,
                'change_date'      => 1575293481,
                'change_user_id'   => 102,
                'reviewer_user_id' => 102,
                'is_removal'       => 0,
            ],
        ]);

        $this->pull_request_factory->method('getPullRequestById')->willReturn($this->createMock(PullRequest::class));

        $this->user_manager->method('getUserById')->willReturn(null);

        $change_pull_request_association = $this->reviewer_change_retriever->getChangeWithTheAssociatedPullRequestByID($change_id);

        self::assertNull($change_pull_request_association);
    }

    public function testReviewerChangeIsNotReturnedWhenTheChangeIDDoesNotExist(): void
    {
        $this->reviewer_change_dao->method('searchByChangeID')->willReturn([]);

        $change_pull_request_association = $this->reviewer_change_retriever->getChangeWithTheAssociatedPullRequestByID(404);

        self::assertNull($change_pull_request_association);
    }

    private function buildUserWithID(int $user_id): PFUser
    {
        return new PFUser(['user_id' => $user_id, 'language_id' => 'en']);
    }
}
