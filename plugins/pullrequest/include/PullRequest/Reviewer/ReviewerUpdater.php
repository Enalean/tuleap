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

use DateTimeImmutable;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent;

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
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(
        ReviewerDAO $dao,
        PullRequestPermissionChecker $pull_request_permission_checker,
        EventDispatcherInterface $event_dispatcher
    ) {
        $this->dao                             = $dao;
        $this->pull_request_permission_checker = $pull_request_permission_checker;
        $this->event_dispatcher                = $event_dispatcher;
    }

    /**
     * @throws UserCannotBeAddedAsReviewerException
     * @throws ReviewersCannotBeUpdatedOnClosedPullRequestException
     */
    public function updatePullRequestReviewers(
        PullRequest $pull_request,
        PFUser $user_changing_the_reviewers,
        DateTimeImmutable $date_of_the_change,
        PFUser ...$new_reviewers
    ): void {
        if ($pull_request->getStatus() !== PullRequest::STATUS_REVIEW) {
            throw new ReviewersCannotBeUpdatedOnClosedPullRequestException($pull_request);
        }

        $new_reviewer_ids = [];

        foreach ($new_reviewers as $user) {
            try {
                $this->pull_request_permission_checker->checkPullRequestIsReadableByUser($pull_request, $user);
            } catch (UserCannotReadGitRepositoryException $exception) {
                throw new UserCannotBeAddedAsReviewerException($pull_request, $user);
            }

            $new_reviewer_ids[] = (int) $user->getId();
        }

        $change_id = $this->dao->setReviewers(
            $pull_request->getId(),
            (int) $user_changing_the_reviewers->getId(),
            $date_of_the_change->getTimestamp(),
            ...$new_reviewer_ids
        );

        if ($change_id === null) {
            return;
        }

        $reviewer_change_event = ReviewerChangeEvent::fromID($change_id);
        $this->event_dispatcher->dispatch($reviewer_change_event);
    }
}
