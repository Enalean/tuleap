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

class Factory {

    /** @var Tuleap\PullRequest\Comment\Dao */
    private $dao;


    public function __construct(Dao $dao) {
        $this->dao = $dao;
    }

    public function save(Comment $comment) {
        return $this->dao->save(
            $comment->getPullRequestId(),
            $comment->getUserId(),
            $comment->getContent()
        );
    }

    public function getPaginatedCommentsByPullRequestId($pull_request_id, $limit, $offset, $order) {
        $comments = array();

        foreach($this->dao->searchByPullRequestId($pull_request_id, $limit, $offset, $order) as $row) {
            $comments[] = $this->instantiateFromRow($row);
        }

        return new PaginatedComments($comments, $this->dao->foundRows());
    }

    private function instantiateFromRow($row) {
        return new Comment(
            $row['id'],
            $row['pull_request_id'],
            $row['user_id'],
            $row['content']
        );
    }
}
