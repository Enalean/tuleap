<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest\Comment;

use DataAccessObject;

class Dao extends DataAccessObject {

    public function save($pull_request_id, $user_id, $content) {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $user_id         = $this->da->escapeInt($user_id);
        $content         = $this->da->quoteSmart($content);

        $sql = "INSERT INTO plugin_pullrequest_comments (pull_request_id, user_id, content)
                VALUES ($pull_request_id, $user_id, $content)";

        return $this->updateAndGetLastId($sql);
    }

    public function searchByPullRequestId($pull_request_id, $limit, $offset, $order) {
        $pull_request_id = $this->da->escapeInt($pull_request_id);
        $limit           = $this->da->escapeInt($limit);
        $offset          = $this->da->escapeInt($offset);

        if (strtolower($order) !== 'asc') {
            $order = 'desc';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_comments
                WHERE pull_request_id = $pull_request_id
                ORDER BY id $order
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }
}
