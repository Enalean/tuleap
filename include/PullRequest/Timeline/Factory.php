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

namespace Tuleap\PullRequest\Timeline;

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\Dao as CommentDao;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\Dao as InlineCommentDao;

class Factory
{

    /** @var Tuleap\PullRequest\Comment\Dao */
    private $comments_dao;

    /** @var Tuleap\PullRequest\InlineComment\Dao */
    private $inline_comments_dao;


    public function __construct(CommentDao $comments_dao, InlineCommentDao $inline_comments_dao)
    {
        $this->comments_dao        = $comments_dao;
        $this->inline_comments_dao = $inline_comments_dao;
    }

    public function getPaginatedTimelineByPullRequestId($pull_request_id, $limit, $offset)
    {
        $comments = array();
        foreach ($this->comments_dao->searchAllByPullRequestId($pull_request_id) as $row) {
            $comments[] = $this->buildComment($row);
        }
        $inline_comments = array();
        foreach ($this->inline_comments_dao->searchAllByPullRequestId($pull_request_id) as $row) {
            $inline_comments[] = InlineComment::buildFromRow($row);
        }

        $full_timeline   = array_merge($comments, $inline_comments);
        usort($full_timeline, array($this, 'sortByPostDate'));
        $timeline     = array_slice($full_timeline, $offset, $limit);
        $total_events = $this->comments_dao->foundRows() +
                        $this->inline_comments_dao->foundRows();
        return new PaginatedTimeline($timeline, $total_events);
    }

    private function sortByPostDate($event1, $event2)
    {
        return $event1->getPostDate() - $event2->getPostDate();
    }

    private function buildComment($row)
    {
        return new Comment(
            $row['id'],
            $row['pull_request_id'],
            $row['user_id'],
            $row['post_date'],
            $row['content']
        );
    }
}
