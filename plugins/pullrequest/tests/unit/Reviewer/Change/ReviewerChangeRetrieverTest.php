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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\PullRequest\Tests\Stub\SearchPullRequestStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class ReviewerChangeRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&ReviewerChangeDAO $reviewer_change_dao;
    private RetrieveUserByIdStub $user_manager;
    private SearchPullRequestStub $pull_request_dao;

    protected function setUp(): void
    {
        $this->reviewer_change_dao = $this->createMock(ReviewerChangeDAO::class);
        $this->pull_request_dao    = SearchPullRequestStub::withNoRow();
        $this->user_manager        = RetrieveUserByIdStub::withNoUser();
    }

    public function buildReviewerChangeRetriever(): ReviewerChangeRetriever
    {
        return new ReviewerChangeRetriever(
            $this->reviewer_change_dao,
            new PullRequestRetriever($this->pull_request_dao),
            $this->user_manager
        );
    }

    public function testRetrieveListOfReviewerChangesOfAPullRequest(): void
    {
        $user_1_id       = 102;
        $user_2_id       = 103;
        $user_3_id       = 104;
        $unknown_user_id = 404;

        $this->user_manager = RetrieveUserByIdStub::withUsers(
            UserTestBuilder::buildWithId($user_1_id),
            UserTestBuilder::buildWithId($user_2_id),
            UserTestBuilder::buildWithId($user_3_id),
        );

        $valid_change_timestamp = 1575044496;

        $pull_request = PullRequestTestBuilder::aPullRequestInReview()->withId(63)->build();
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

        $changes = $this->buildReviewerChangeRetriever()->getChangesForPullRequest($pull_request);
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

        $this->user_manager = RetrieveUserByIdStub::withUsers(
            UserTestBuilder::buildWithId($user_1_id),
            UserTestBuilder::buildWithId($user_2_id),
        );

        $pull_request           = PullRequestTestBuilder::aPullRequestInReview()->withId($pull_request_id)->build();
        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest($pull_request);

        $change_pull_request_association = $this->buildReviewerChangeRetriever()->getChangeWithTheAssociatedPullRequestByID($change_id);

        self::assertNotNull($change_pull_request_association);
        self::assertEqualsCanonicalizing($pull_request, $change_pull_request_association->getPullRequest());
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

        $change_pull_request_association = $this->buildReviewerChangeRetriever()->getChangeWithTheAssociatedPullRequestByID($change_id);

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

        $this->pull_request_dao = SearchPullRequestStub::withAtLeastOnePullRequest(PullRequestTestBuilder::aPullRequestInReview()->build());

        $change_pull_request_association = $this->buildReviewerChangeRetriever()->getChangeWithTheAssociatedPullRequestByID($change_id);

        self::assertNull($change_pull_request_association);
    }

    public function testReviewerChangeIsNotReturnedWhenTheChangeIDDoesNotExist(): void
    {
        $this->reviewer_change_dao->method('searchByChangeID')->willReturn([]);

        $change_pull_request_association = $this->buildReviewerChangeRetriever()->getChangeWithTheAssociatedPullRequestByID(404);

        self::assertNull($change_pull_request_association);
    }
}
