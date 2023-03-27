<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Timeline\SearchMergeEvent;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final class PullRequestStatusInfoRepresentationBuilder
{
    public function __construct(
        private SearchMergeEvent $search_merge_event,
        private RetrieveUserById $retrieve_user_by_id,
    ) {
    }

    public function buildPullRequestStatusInfoRepresentation(PullRequest $pull_request): ?PullRequestStatusInfoRepresentation
    {
        if ($pull_request->getStatus() === PullRequest::STATUS_REVIEW) {
            return null;
        }

        $merge_event = $this->search_merge_event->searchMergeEventForPullRequest($pull_request->getId());
        if (! $merge_event) {
            return null;
        }

        $status_updater = $this->retrieve_user_by_id->getUserById($merge_event['user_id']);
        if (! $status_updater) {
            return null;
        }

        return new PullRequestStatusInfoRepresentation(
            PullRequestStatusTypeConverter::fromIntStatusToStringStatus($merge_event['type']),
            JsonCast::toDate($merge_event['post_date']),
            MinimalUserRepresentation::build($status_updater),
        );
    }
}
