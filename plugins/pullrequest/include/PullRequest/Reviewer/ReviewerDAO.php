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

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;
use Tuleap\PullRequest\PullRequest\Reviewer\RepositoryPullRequestsReviewersIdsPage;
use Tuleap\PullRequest\PullRequest\Reviewer\SearchRepositoryReviewers;
use Tuleap\PullRequest\PullRequest\Reviewer\SearchReviewers;

class ReviewerDAO extends DataAccessObject implements SearchReviewers, SearchRepositoryReviewers
{
    /**
     * @psalm-return list<array{
     *  "user_id": int,
     *  "user_name": string,
     *  "email": string,
     *  "password": string,
     *  "realname": string,
     *  "register_purpose": string | null,
     *  "status": string,
     *  "ldap_id": string | null,
     *  "add_date": int,
     *  "approved_by": int,
     *  "confirm_hash": string | null,
     *  "mail_siteupdates": int,
     *  "mail_va": int,
     *  "sticky_login": int,
     *  "authorized_keys": string | null,
     *  "email_new": string | null,
     *  "timezone": string | null,
     *  "language_id": string,
     *  "last_pwd_update": int,
     *  "expiry_date": int | null,
     *  "has_custom_avatar": int,
     *  "is_first_timer": int,
     *  "passwordless_only": int
     *  }>
     */
    public function searchReviewers(int $pull_request_id): array
    {
        return $this->getDB()->run(
            'SELECT user.*
                       FROM plugin_pullrequest_reviewer_change_user
                       JOIN plugin_pullrequest_reviewer_change ON (plugin_pullrequest_reviewer_change.change_id = plugin_pullrequest_reviewer_change_user.change_id)
                       JOIN user ON (user.user_id = plugin_pullrequest_reviewer_change_user.user_id)
                       WHERE plugin_pullrequest_reviewer_change.pull_request_id = ?
                       GROUP BY user.user_id
                       HAVING SUM(IF(plugin_pullrequest_reviewer_change_user.is_removal = FALSE, 1, -1)) = 1',
            $pull_request_id
        );
    }

    public function setReviewers(
        int $pull_request_id,
        int $user_doing_the_change_id,
        int $change_timestamp,
        int ...$user_ids,
    ): ?int {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use (
            $pull_request_id,
            $user_doing_the_change_id,
            $change_timestamp,
            $user_ids
        ): ?int {
            $current_reviewer_ids = array_map(
                static function (array $user_row): int {
                    return $user_row['user_id'];
                },
                $this->searchReviewers($pull_request_id)
            );

            $added_reviewer_ids   = array_diff($user_ids, $current_reviewer_ids);
            $removed_reviewer_ids = array_diff($current_reviewer_ids, $user_ids);

            if (empty($added_reviewer_ids) && empty($removed_reviewer_ids)) {
                return null;
            }

            $change_id = (int) $db->insertReturnId(
                'plugin_pullrequest_reviewer_change',
                [
                    'pull_request_id' => $pull_request_id,
                    'user_id'         => $user_doing_the_change_id,
                    'change_date'     => $change_timestamp,
                ]
            );

            $change_user_rows = [];
            foreach ($added_reviewer_ids as $reviewer_id) {
                $change_user_rows[] = [
                    'change_id'  => $change_id,
                    'user_id'    => $reviewer_id,
                    'is_removal' => false,
                ];
            }
            foreach ($removed_reviewer_ids as $reviewer_id) {
                $change_user_rows[] = [
                    'change_id'  => $change_id,
                    'user_id'    => $reviewer_id,
                    'is_removal' => true,
                ];
            }
            $db->insertMany(
                'plugin_pullrequest_reviewer_change_user',
                $change_user_rows
            );

            return $change_id;
        });
    }

    public function searchRepositoryPullRequestsReviewersIds(int $repository_id, int $limit, int $offset): RepositoryPullRequestsReviewersIdsPage
    {
        return $this->getDB()->tryFlatTransaction(function () use ($repository_id, $limit, $offset) {
            $sql_select_count = "SELECT COUNT(*) OVER ()";
            $sql_select_data  = "SELECT change_user.user_id";

            $sql_query_body = "
                FROM plugin_pullrequest_reviewer_change_user change_user
                JOIN plugin_pullrequest_reviewer_change ON (plugin_pullrequest_reviewer_change.change_id = change_user.change_id)
                JOIN plugin_pullrequest_review ON (plugin_pullrequest_review.id = plugin_pullrequest_reviewer_change.pull_request_id)
                WHERE plugin_pullrequest_review.repository_id = ?
                GROUP BY change_user.user_id
                HAVING SUM(IF(change_user.is_removal = FALSE, 1, -1)) > 0
            ";

            return new RepositoryPullRequestsReviewersIdsPage(
                (int) $this->getDB()->single($sql_select_count . $sql_query_body . 'LIMIT 1', [$repository_id]),
                $this->getDB()->column($sql_select_data . $sql_query_body . 'LIMIT ? OFFSET ?', [$repository_id, $limit, $offset]),
            );
        });
    }
}
