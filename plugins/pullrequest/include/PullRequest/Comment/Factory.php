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

namespace Tuleap\PullRequest\Comment;

use Psr\EventDispatcher\EventDispatcherInterface;
use ReferenceManager;
use pullrequestPlugin;
use PFUser;
use Tuleap\PullRequest\Comment\Notification\PullRequestNewCommentEvent;

class Factory
{
    public function __construct(
        private Dao $dao,
        private ReferenceManager $reference_manager,
        private EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function save(Comment $comment, PFUser $user, $project_id): int
    {
        $saved = $this->dao->save(
            $comment->getPullRequestId(),
            $comment->getUserId(),
            $comment->getPostDate(),
            $comment->getContent(),
            $comment->getParentId(),
            $comment->getFormat()
        );

        $this->reference_manager->extractCrossRef(
            $comment->getContent(),
            $comment->getPullRequestId(),
            pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $user->getId(),
            pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        $this->event_dispatcher->dispatch(PullRequestNewCommentEvent::fromCommentID($saved));

        return $saved;
    }

    public function getPaginatedCommentsByPullRequestId($pull_request_id, $limit, $offset, $order)
    {
        $comments = [];

        foreach ($this->dao->searchByPullRequestId($pull_request_id, $limit, $offset, $order) as $row) {
            $comments[] = $this->instantiateFromRow($row);
        }

        return new PaginatedComments($comments, $this->dao->foundRows());
    }

    private function instantiateFromRow($row): Comment
    {
        return new Comment(
            $row['id'],
            $row['pull_request_id'],
            $row['user_id'],
            $row['post_date'],
            $row['content'],
            (int) $row['parent_id'],
            $row['color'],
            $row['format']
        );
    }
}
