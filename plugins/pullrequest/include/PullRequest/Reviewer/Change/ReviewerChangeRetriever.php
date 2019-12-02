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
use UserManager;

class ReviewerChangeRetriever
{
    /**
     * @var ReviewerChangeDAO
     */
    private $dao;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(ReviewerChangeDAO $dao, UserManager $user_manager)
    {
        $this->dao          = $dao;
        $this->user_manager = $user_manager;
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
