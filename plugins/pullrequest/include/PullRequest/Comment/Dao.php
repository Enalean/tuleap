<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use Tuleap\DB\DataAccessObject;

class Dao extends DataAccessObject
{

    public function save($pull_request_id, $user_id, $post_date, $content): int
    {
        $sql = 'INSERT INTO plugin_pullrequest_comments (pull_request_id, user_id, post_date, content)
                VALUES (?, ?, ?, ?)';
        $this->getDB()->run($sql, $pull_request_id, $user_id, $post_date, $content);

        return (int) $this->getDB()->lastInsertId();
    }

    public function searchByPullRequestId($pull_request_id, $limit, $offset, $order)
    {
        if (strtolower($order) !== 'asc') {
            $order = 'desc';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_comments
                WHERE pull_request_id = ?
                ORDER BY id $order
                LIMIT ?, ?";

        return $this->getDB()->run($sql, $pull_request_id, $offset, $limit);
    }

    /**
     * @return array|null
     *
     * @psalm-return array{id:int, pull_request_id:int, user_id:int, post_date:int, content:string}|null
     */
    public function searchByCommentID(int $comment_id): ?array
    {
        $sql = 'SELECT id, pull_request_id, user_id, post_date, content
                FROM plugin_pullrequest_comments
                WHERE id = ?';

        return $this->getDB()->row($sql, $comment_id);
    }

    public function searchAllByPullRequestId($pull_request_id)
    {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_pullrequest_comments
                WHERE pull_request_id = ?';

        return $this->getDB()->run($sql, $pull_request_id);
    }
}
