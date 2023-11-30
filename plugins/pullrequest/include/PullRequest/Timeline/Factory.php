<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reviewer\Change\ReviewerChangeRetriever;
use Tuleap\PullRequest\Timeline\Dao as TimeLineDao;

class Factory
{
    /** @var \Tuleap\PullRequest\Comment\Dao */
    private $comments_dao;

    /** @var \Tuleap\PullRequest\InlineComment\Dao */
    private $inline_comments_dao;

    /** @var \Tuleap\PullRequest\Timeline\Dao */
    private $timeline_dao;
    /**
     * @var ReviewerChangeRetriever
     */
    private $reviewer_change_retriever;

    public function __construct(
        CommentDao $comments_dao,
        InlineCommentDao $inline_comments_dao,
        TimeLineDao $timeline_dao,
        ReviewerChangeRetriever $reviewer_change_retriever,
    ) {
        $this->comments_dao              = $comments_dao;
        $this->inline_comments_dao       = $inline_comments_dao;
        $this->timeline_dao              = $timeline_dao;
        $this->reviewer_change_retriever = $reviewer_change_retriever;
    }

    public function getPaginatedTimelineByPullRequestId(PullRequest $pull_request, $limit, $offset)
    {
        $comments = [];
        foreach ($this->comments_dao->searchAllByPullRequestId($pull_request->getId()) as $row) {
            $comments[] = Comment::buildFromRow($row);
        }
        $total_comment_events = $this->comments_dao->foundRows();

        $inline_comments = [];
        foreach ($this->inline_comments_dao->searchAllByPullRequestId($pull_request->getId()) as $row) {
            $inline_comments[] = InlineComment::buildFromRow($row);
        }
        $total_inline_comment_events = $this->inline_comments_dao->foundRows();

        $reviewer_changes = $this->reviewer_change_retriever->getChangesForPullRequest($pull_request);

        $timeline_events = [];
        foreach ($this->timeline_dao->searchAllByPullRequestId($pull_request->getId()) as $row) {
            $timeline_events[] = TimelineGlobalEvent::buildFromRow($row);
        }
        $total_timeline_events = $this->timeline_dao->foundRows();

        $full_timeline = array_merge($comments, $inline_comments, $reviewer_changes, $timeline_events);
        usort($full_timeline, static function (TimelineEvent $event1, TimelineEvent $event2): int {
            return $event1->getPostDate()->getTimestamp() - $event2->getPostDate()->getTimestamp();
        });
        $timeline     = array_slice($full_timeline, $offset, $limit);
        $total_events = $total_comment_events + $total_inline_comment_events + count($reviewer_changes) + $total_timeline_events;
        return new PaginatedTimeline($timeline, $total_events);
    }
}
