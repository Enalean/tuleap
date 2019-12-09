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

namespace Tuleap\PullRequest\Notification;

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\ReviewerRetriever;
use Tuleap\PullRequest\Timeline\Dao as TimelineDAO;
use Tuleap\PullRequest\Timeline\TimelineGlobalEvent;
use UserManager;

class OwnerRetriever
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var TimelineDAO
     */
    private $timeline_dao;
    /**
     * @var ReviewerRetriever
     */
    private $reviewer_retriever;

    public function __construct(UserManager $user_manager, ReviewerRetriever $reviewer_retriever, TimelineDAO $timeline_dao)
    {
        $this->user_manager       = $user_manager;
        $this->reviewer_retriever = $reviewer_retriever;
        $this->timeline_dao       = $timeline_dao;
    }

    /**
     * @return \PFUser[]
     */
    public function getOwners(PullRequest $pull_request): array
    {
        $owners = $this->reviewer_retriever->getReviewers($pull_request);

        $owners[] = $this->user_manager->getUserById($pull_request->getUserId());

        $pull_request_updater_rows = $this->timeline_dao->searchUserIDsByPullRequestIDAndEventType(
            $pull_request->getId(),
            TimelineGlobalEvent::UPDATE
        );
        foreach ($pull_request_updater_rows as $pull_request_updater_row) {
            $owners[] = $this->user_manager->getUserById($pull_request_updater_row['user_id']);
        }

        return array_unique(array_filter($owners));
    }
}
