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

use PFUser;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;

final class ReviewerUpdater
{
    /**
     * @var ReviewerDAO
     */
    private $dao;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pull_request_permission_checker;

    public function __construct(ReviewerDAO $dao, PullRequestPermissionChecker $pull_request_permission_checker)
    {
        $this->dao                             = $dao;
        $this->pull_request_permission_checker = $pull_request_permission_checker;
    }

    /**
     * @throws UserCannotBeAddedAsReviewerException
     */
    public function updatePullRequestReviewers(PullRequest $pull_request, PFUser ...$new_reviewers): void
    {
        $new_reviewer_ids = [];

        foreach ($new_reviewers as $user) {
            try {
                $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($pull_request, $user);
            } catch (UserCannotReadGitRepositoryException $exception) {
                throw new UserCannotBeAddedAsReviewerException($pull_request, $user);
            }

            $new_reviewer_ids[] = (int) $user->getId();
        }

        $this->dao->setReviewers($pull_request->getId(), ...$new_reviewer_ids);
    }
}
