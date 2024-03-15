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

namespace Tuleap\PullRequest\Reviewer;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DBFactory;
use Tuleap\PullRequest\Dao;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;

final class ReviewerDAOTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const REPOSITORY_ID = 5;
    private const FORK_ID       = 19;

    private int $bob_user_id;
    private int $alice_user_id;
    private int $pull_request_id;
    private int $pull_request_in_fork_id;

    private Dao $pull_requests_dao;
    private ReviewerDAO $reviewers_dao;

    protected function setUp(): void
    {
        $db                = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->bob_user_id = (int) $db->insertReturnId(
            'user',
            [
                'user_name' => 'bob',
                'email' => 'bob@example.com',
            ]
        );

        $this->alice_user_id = (int) $db->insertReturnId(
            'user',
            [
                'user_name' => 'alice',
                'email' => 'alice@example.com',
            ]
        );

        $this->pull_requests_dao = new Dao();
        $this->reviewers_dao     = new ReviewerDAO();

        $pull_request_in_repository = PullRequestTestBuilder::aPullRequestInReview()
            ->withRepositoryId(self::REPOSITORY_ID)
            ->withRepositoryDestinationId(self::REPOSITORY_ID)
            ->build();

        $pull_request_in_fork = PullRequestTestBuilder::aMergedPullRequest()
            ->withRepositoryId(self::FORK_ID)
            ->withRepositoryDestinationId(self::REPOSITORY_ID)
            ->build();

        $this->pull_request_id         = $this->createPullRequest($pull_request_in_repository);
        $this->pull_request_in_fork_id = $this->createPullRequest($pull_request_in_fork);
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $user_to_delete_condition = EasyStatement::open()->in('user_id IN (?*)', [$this->bob_user_id, $this->alice_user_id]);
        $db->safeQuery("DELETE FROM user WHERE $user_to_delete_condition", $user_to_delete_condition->values());

        $this->pull_requests_dao->deletePullRequestWithAllItsContent($this->pull_request_id);
        $this->pull_requests_dao->deletePullRequestWithAllItsContent($this->pull_request_in_fork_id);
    }

    public function testCanSearchReviewersOnANotExistingPullRequest(): void
    {
        $this->assertEmpty($this->reviewers_dao->searchReviewers(9999999));
    }

    public function testCanSetReviewersOnAPullRequest(): void
    {
        $this->setReviewersOfPullRequest($this->pull_request_id, $this->bob_user_id, $this->alice_user_id);

        $new_reviewer_rows = $this->reviewers_dao->searchReviewers($this->pull_request_id);

        $found_reviewers_ids = [];
        foreach ($new_reviewer_rows as $new_reviewer_row) {
            $found_reviewers_ids[] = $new_reviewer_row['user_id'];
        }
        $this->assertEqualsCanonicalizing([$this->bob_user_id, $this->alice_user_id], $found_reviewers_ids);

        $unknown_user_id = 3;

        $this->setReviewersOfPullRequest($this->pull_request_id, $unknown_user_id);
        $this->assertEmpty($this->reviewers_dao->searchReviewers($this->pull_request_id));
    }

    public function testItReturnsRepositoryReviewers(): void
    {
        $this->setReviewersOfPullRequest($this->pull_request_id, $this->bob_user_id);
        $this->setReviewersOfPullRequest($this->pull_request_in_fork_id, $this->alice_user_id);

        $reviewers_in_repository = $this->reviewers_dao->searchRepositoryPullRequestsReviewersIds(self::REPOSITORY_ID, 50, 0);
        $reviewers_in_fork       = $this->reviewers_dao->searchRepositoryPullRequestsReviewersIds(self::FORK_ID, 50, 0);

        self::assertEquals(2, $reviewers_in_repository->total_size);
        self::assertEqualsCanonicalizing([$this->bob_user_id, $this->alice_user_id], $reviewers_in_repository->reviewers_ids);

        self::assertEquals(1, $reviewers_in_fork->total_size);
        self::assertEqualsCanonicalizing([$this->alice_user_id], $reviewers_in_fork->reviewers_ids);
    }

    private function setReviewersOfPullRequest(int $pull_request_id, int ...$reviewers_ids): void
    {
        $user_doing_change_id = 101;

        $this->reviewers_dao->setReviewers(
            $pull_request_id,
            $user_doing_change_id,
            1,
            ...$reviewers_ids
        );
    }

    private function createPullRequest(PullRequest $pull_request): int
    {
        return (int) $this->pull_requests_dao->create(
            $pull_request->getRepositoryId(),
            $pull_request->getTitle(),
            $pull_request->getDescription(),
            $pull_request->getUserId(),
            $pull_request->getCreationDate(),
            $pull_request->getBranchSrc(),
            $pull_request->getSha1Src(),
            $pull_request->getRepoDestId(),
            $pull_request->getBranchDest(),
            $pull_request->getSha1Dest(),
            $pull_request->getMergeStatus(),
            $pull_request->getDescriptionFormat(),
        );
    }
}
