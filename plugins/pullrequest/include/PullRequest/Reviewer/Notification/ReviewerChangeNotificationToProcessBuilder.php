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

namespace Tuleap\PullRequest\Reviewer\Notification;

use Tuleap\PullRequest\Notification\EventSubjectToNotification;
use Tuleap\PullRequest\Notification\NotificationToProcessBuilder;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use UserHelper;

/**
 * @template-implements NotificationToProcessBuilder<\Tuleap\PullRequest\Reviewer\Change\ReviewerChangeEvent>
 */
final class ReviewerChangeNotificationToProcessBuilder implements NotificationToProcessBuilder
{
    /**
     * @var ReviewerChangeRetriever
     */
    private $reviewer_change_retriever;
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var HTMLURLBuilder
     */
    private $html_url_builder;

    public function __construct(
        ReviewerChangeRetriever $reviewer_change_retriever,
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder
    ) {
        $this->reviewer_change_retriever = $reviewer_change_retriever;
        $this->user_helper               = $user_helper;
        $this->html_url_builder          = $html_url_builder;
    }

    public function getNotificationsToProcess(EventSubjectToNotification $event): array
    {
        $reviewer_change_pull_request_association = $this->reviewer_change_retriever->getChangeWithTheAssociatedPullRequestByID(
            $event->getChangeID()
        );

        if ($reviewer_change_pull_request_association === null) {
            return [];
        }

        $pull_request    = $reviewer_change_pull_request_association->getPullRequest();
        $reviewer_change = $reviewer_change_pull_request_association->getReviewerChange();
        $change_user     = $reviewer_change->changedBy();
        $added_reviewers = $reviewer_change->getAddedReviewers();

        if (empty($added_reviewers)) {
            return [];
        }

        $notification = ReviewerAddedNotification::fromReviewerChangeInformation(
            $this->user_helper,
            $this->html_url_builder,
            $pull_request,
            $change_user,
            $added_reviewers
        );

        return [$notification];
    }
}
