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

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\User\RetrieveUserById;

class ReviewerChangeRetriever
{
    public function __construct(
        private readonly ReviewerChangeDAO $dao,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly RetrieveUserById $user_manager,
    ) {
    }

    public function getChangeWithTheAssociatedPullRequestByID(int $change_id): ?ReviewerChangePullRequestAssociation
    {
        $raw_change_information = $this->dao->searchByChangeID($change_id);
        if (empty($raw_change_information)) {
            return null;
        }

        return $this->pull_request_retriever->getPullRequestById($raw_change_information[0]['pull_request_id'])->match(
            function (PullRequest $pull_request) use ($raw_change_information) {
                $reviewer_change = $this->buildReviewerChangeFromRawReviewerInformation($raw_change_information);

                if ($reviewer_change === null) {
                    return null;
                }

                return new ReviewerChangePullRequestAssociation($reviewer_change, $pull_request);
            },
            static fn() => null
        );
    }

    /**
     * @return ReviewerChange[]
     */
    public function getChangesForPullRequest(PullRequest $pull_request): array
    {
        $changes     = [];
        $raw_changes = $this->dao->searchByPullRequestID($pull_request->getId());

        foreach ($raw_changes as $raw_change_reviewers) {
            $change = $this->buildReviewerChangeFromRawReviewerInformation($raw_change_reviewers);
            if ($change === null) {
                continue;
            }
            $changes[] = $change;
        }

        return $changes;
    }

    /**
     * @psalm-param non-empty-list<array{change_date: int, change_user_id: int, reviewer_user_id: int, is_removal: 0|1}> $raw_reviewers_information
     */
    private function buildReviewerChangeFromRawReviewerInformation(array $raw_reviewers_information): ?ReviewerChange
    {
        $change_user = $this->user_manager->getUserById($raw_reviewers_information[0]['change_user_id']);
        if ($change_user === null) {
            return null;
        }

        $added_reviewers   = [];
        $removed_reviewers = [];
        foreach ($raw_reviewers_information as $raw_change_reviewer) {
            $reviewer = $this->user_manager->getUserById($raw_change_reviewer['reviewer_user_id']);
            if ($reviewer === null) {
                continue;
            }
            if ($raw_change_reviewer['is_removal']) {
                $removed_reviewers[] = $reviewer;
            } else {
                $added_reviewers[] = $reviewer;
            }
        }

        return new ReviewerChange(
            new \DateTimeImmutable('@' . $raw_reviewers_information[0]['change_date']),
            $change_user,
            $added_reviewers,
            $removed_reviewers
        );
    }
}
