<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\PullRequest\Criterion\AuthorCriterion;
use Tuleap\PullRequest\Criterion\KeywordCriterion;
use Tuleap\PullRequest\Criterion\LabelCriterion;
use Tuleap\PullRequest\Criterion\PullRequestSortOrder;
use Tuleap\PullRequest\Criterion\RelatedToCriterion;
use Tuleap\PullRequest\Criterion\ReviewerCriterion;
use Tuleap\PullRequest\Criterion\SearchCriteria;
use Tuleap\PullRequest\Criterion\StatusCriterion;
use Tuleap\PullRequest\Criterion\TargetBranchCriterion;
use Tuleap\PullRequest\Label\PullRequestLabelDao;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Reviewer\ReviewerDAO;
use Tuleap\PullRequest\Tests\Builders\PullRequestTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class DaoTest extends TestCase
{
    private const REPOSITORY_ID = 5;
    private const LIMIT         = 10;
    private const OFFSET        = 0;

    private const BOB_USER_ID   = 102;
    private const ALICE_USER_ID = 103;

    private const LABEL_EMERGENCY_ID = 11;
    private const LABEL_EASY_FIX_ID  = 19;

    private Dao $dao;
    private PullRequestLabelDao $pull_requests_labels_dao;
    private ReviewerDAO $reviewer_dao;

    private int $open_pull_request_id;
    private int $merged_pull_request_id;
    private int $abandoned_pull_request_id;

    protected function setUp(): void
    {
        $this->dao                      = new Dao();
        $this->pull_requests_labels_dao = new PullRequestLabelDao();
        $this->reviewer_dao             = new ReviewerDAO();

        $this->open_pull_request_id      = $this->insertOpenPullRequest();
        $this->merged_pull_request_id    = $this->insertMergedPullRequest();
        $this->abandoned_pull_request_id = $this->insertAbandonedPullRequest();

        $this->addLabelsToPullRequest($this->open_pull_request_id, self::LABEL_EMERGENCY_ID);
        $this->addLabelsToPullRequest($this->merged_pull_request_id, self::LABEL_EMERGENCY_ID, self::LABEL_EASY_FIX_ID);
        $this->addLabelsToPullRequest($this->abandoned_pull_request_id, self::LABEL_EASY_FIX_ID);

        $this->assignReviewerToPullRequest($this->open_pull_request_id, self::BOB_USER_ID);
        $this->assignReviewerToPullRequest($this->merged_pull_request_id, self::ALICE_USER_ID);
        $this->assignReviewerToPullRequest($this->abandoned_pull_request_id, self::BOB_USER_ID);
    }

    protected function tearDown(): void
    {
        $this->dao->deletePullRequestWithAllItsContent($this->open_pull_request_id);
        $this->dao->deletePullRequestWithAllItsContent($this->merged_pull_request_id);
        $this->dao->deletePullRequestWithAllItsContent($this->abandoned_pull_request_id);
    }

    public function testItRetrievesOnlyOpenPullRequests(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(StatusCriterion::OPEN),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertSame(array_column($result->pull_requests, "id"), [$this->open_pull_request_id]);
        self::assertEquals(1, $result->total_size);
    }

    public function testItRetrievesOnlyClosedPullRequests(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(StatusCriterion::CLOSED),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([$this->merged_pull_request_id, $this->abandoned_pull_request_id], array_column($result->pull_requests, "id"));
    }

    public function testItRetrievesAllPullRequestsInDescendingOrder(): void
    {
        $this->retrieveAllPullRequests(
            PullRequestSortOrder::DESCENDING,
            [
                $this->abandoned_pull_request_id,
                $this->merged_pull_request_id,
                $this->open_pull_request_id,
            ]
        );
    }

    public function testItRetrievesAllPullRequestsInAscendingOrder(): void
    {
        $this->retrieveAllPullRequests(
            PullRequestSortOrder::ASCENDING,
            [
                $this->open_pull_request_id,
                $this->merged_pull_request_id,
                $this->abandoned_pull_request_id,
            ]
        );
    }

    /**
     * @param list<int> $expected_pr_ids
     * @return void
     */
    private function retrieveAllPullRequests(PullRequestSortOrder $order, array $expected_pr_ids)
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(),
            $order,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing($expected_pr_ids, array_column($result->pull_requests, "id"));
    }

    public function testItFiltersOnASpecificAuthor(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(null, [new AuthorCriterion(self::BOB_USER_ID)]),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([
            $this->open_pull_request_id,
            $this->merged_pull_request_id,
        ], array_column($result->pull_requests, "id"));
        self::assertEquals(2, $result->total_size);
    }

    public function testItFiltersOnLabels(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                null,
                [],
                [new LabelCriterion(self::LABEL_EMERGENCY_ID), new LabelCriterion(self::LABEL_EASY_FIX_ID)],
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([$this->merged_pull_request_id], array_column($result->pull_requests, "id"));
        self::assertEquals(1, $result->total_size);
    }

    public function testItFiltersOnTitleAndDescriptionWithKeywords(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                null,
                [],
                [],
                [new KeywordCriterion("nice")],
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([
            $this->abandoned_pull_request_id,
            $this->open_pull_request_id,
        ], array_column($result->pull_requests, "id"));
        self::assertEquals(2, $result->total_size);
    }

    public function testItFiltersOnTargetBranches(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                null,
                [],
                [],
                [],
                [new TargetBranchCriterion("walnut")],
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([
            $this->open_pull_request_id,
            $this->merged_pull_request_id,
        ], array_column($result->pull_requests, "id"));
        self::assertEquals(2, $result->total_size);
    }

    public function testItFiltersOnReviewers(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                null,
                [],
                [],
                [],
                [],
                [new ReviewerCriterion(self::ALICE_USER_ID)]
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([
            $this->merged_pull_request_id,
        ], array_column($result->pull_requests, "id"));
        self::assertEquals(1, $result->total_size);
    }

    public function testItFiltersOnRelatedTo(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                null,
                [],
                [],
                [],
                [],
                [],
                [new RelatedToCriterion(self::ALICE_USER_ID)]
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([
            $this->merged_pull_request_id,
            $this->abandoned_pull_request_id,
        ], array_column($result->pull_requests, "id"));
        self::assertEquals(2, $result->total_size);
    }

    public function testItAppliesAllTheFiltersOnPullRequestsRelatedToASpecificUser(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                StatusCriterion::OPEN,
                [],
                [new LabelCriterion(self::LABEL_EMERGENCY_ID)],
                [new KeywordCriterion("good")],
                [new TargetBranchCriterion("walnut")],
                [],
                [new RelatedToCriterion(self::BOB_USER_ID)]
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([$this->open_pull_request_id], array_column($result->pull_requests, "id"));
    }

    public function testItAppliesAllTheFilters(): void
    {
        $result = $this->dao->getPaginatedPullRequests(
            self::REPOSITORY_ID,
            new SearchCriteria(
                StatusCriterion::CLOSED,
                [new AuthorCriterion(self::ALICE_USER_ID)],
                [new LabelCriterion(self::LABEL_EASY_FIX_ID)],
                [new KeywordCriterion("external")],
                [new TargetBranchCriterion("baobab")],
                [new ReviewerCriterion(self::BOB_USER_ID)]
            ),
            PullRequestSortOrder::DESCENDING,
            self::LIMIT,
            self::OFFSET,
        );

        self::assertEqualsCanonicalizing([$this->abandoned_pull_request_id], array_column($result->pull_requests, "id"));
    }

    public function testItRetrievesAllPullRequestsAuthorsWhoAreNotAnonymous(): void
    {
        $han_onymous_user_id = 0;

        $id = $this->insertPullRequest(
            PullRequestTestBuilder::aPullRequestInReview()
                ->createdBy($han_onymous_user_id)
                ->withRepositoryId(self::REPOSITORY_ID)
                ->build(),
        );

        $result = $this->dao->getPaginatedPullRequestsAuthorsIds(
            self::REPOSITORY_ID,
            self::LIMIT,
            self::OFFSET
        );

        self::assertEqualsCanonicalizing(
            [self::BOB_USER_ID, self::ALICE_USER_ID],
            $result->authors_ids,
        );

        $this->dao->deletePullRequestWithAllItsContent($id);
    }

    private function insertOpenPullRequest(): int
    {
        return $this->insertPullRequest(
            PullRequestTestBuilder::aPullRequestInReview()
                ->withTitle("A good pull-request")
                ->withDescription(TimelineComment::FORMAT_TEXT, "A nice description")
                ->createdBy(self::BOB_USER_ID)
                ->createdAt(1)
                ->withRepositoryId(self::REPOSITORY_ID)
                ->toDestinationBranch("walnut")
                ->build(),
        );
    }

    private function insertMergedPullRequest(): int
    {
        $pull_request_id = $this->insertPullRequest(
            PullRequestTestBuilder::aMergedPullRequest()
                ->withTitle("Emergency fix")
                ->withDescription(TimelineComment::FORMAT_TEXT, "Everything is burning")
                ->createdBy(self::BOB_USER_ID)
                ->createdAt(2)
                ->withRepositoryId(self::REPOSITORY_ID)
                ->toDestinationBranch("walnut")
                ->build(),
        );

        $this->dao->markAsMerged($pull_request_id);

        return $pull_request_id;
    }

    private function insertAbandonedPullRequest(): int
    {
        $pull_request_id = $this->insertPullRequest(
            PullRequestTestBuilder::anAbandonedPullRequest()
                ->withTitle("A (not so) nice external contribution")
                ->withDescription(TimelineComment::FORMAT_MARKDOWN, "I've decided this software should make **coffee**")
                ->createdBy(self::ALICE_USER_ID)
                ->createdAt(3)
                ->withRepositoryId(self::REPOSITORY_ID)
                ->toDestinationBranch("baobab")
                ->build()
        );

        $this->dao->markAsAbandoned($pull_request_id);

        return $pull_request_id;
    }

    private function insertPullRequest(PullRequest $pull_request): int
    {
        return (int) $this->dao->create(
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

    private function addLabelsToPullRequest(int $pull_request_id, int ...$label_id): void
    {
        $this->pull_requests_labels_dao->addLabelsInTransaction($pull_request_id, $label_id);
    }

    private function assignReviewerToPullRequest(int $pull_request_id, int $reviewer_id): void
    {
        $this->reviewer_dao->setReviewers($pull_request_id, self::BOB_USER_ID, 1, $reviewer_id);
    }
}
