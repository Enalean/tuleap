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

use Tuleap\DB\DataAccessObject;

class ReviewerChangeDAO extends DataAccessObject
{
    /**
     * @psalm-return list<array{pull_request_id: int, change_date: int, change_user_id: int, reviewer_user_id: int, is_removal: 0|1}>
     */
    public function searchByChangeID(int $change_id): array
    {
        return $this->getDB()->run(
            'SELECT
                plugin_pullrequest_reviewer_change.pull_request_id,
                plugin_pullrequest_reviewer_change.change_date,
                plugin_pullrequest_reviewer_change.user_id AS change_user_id,
                plugin_pullrequest_reviewer_change_user.user_id AS reviewer_user_id,
                plugin_pullrequest_reviewer_change_user.is_removal
            FROM plugin_pullrequest_reviewer_change
            JOIN plugin_pullrequest_reviewer_change_user ON (plugin_pullrequest_reviewer_change_user.change_id = plugin_pullrequest_reviewer_change.change_id)
            WHERE plugin_pullrequest_reviewer_change.change_id = ?',
            $change_id
        );
    }

    /**
     * @psalm-return array<int,non-empty-list<array{change_date: int, change_user_id: int, reviewer_user_id: int, is_removal: 0|1}>>
     */
    public function searchByPullRequestID(int $pull_request_id): array
    {
        return $this->getDB()->safeQuery(
            'SELECT
                plugin_pullrequest_reviewer_change.change_id,
                plugin_pullrequest_reviewer_change.change_date,
                plugin_pullrequest_reviewer_change.user_id AS change_user_id,
                plugin_pullrequest_reviewer_change_user.user_id AS reviewer_user_id,
                plugin_pullrequest_reviewer_change_user.is_removal
            FROM plugin_pullrequest_reviewer_change
            JOIN plugin_pullrequest_reviewer_change_user ON (plugin_pullrequest_reviewer_change_user.change_id = plugin_pullrequest_reviewer_change.change_id)
            WHERE pull_request_id = ?
            ',
            [$pull_request_id],
            \PDO::FETCH_GROUP | \PDO::FETCH_ASSOC
        );
    }
}
