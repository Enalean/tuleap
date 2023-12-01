<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Comment;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\Comment\Notification\PullRequestNewCommentEvent;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\Reference\ExtractAndSaveCrossReferences;

final class CommentCreator
{
    public function __construct(
        private readonly CreateComment $comment_saver,
        private readonly ExtractAndSaveCrossReferences $reference_manager,
        private readonly EventDispatcherInterface $event_dispatcher,
        private readonly ThreadCommentColorRetriever $color_retriever,
        private readonly ThreadCommentColorAssigner $color_assigner,
    ) {
    }

    public function create(NewComment $new_comment): Comment
    {
        $pull_request_id = $new_comment->pull_request->getId();

        $comment_id = $this->comment_saver->create($new_comment);

        $color = $this->color_retriever->retrieveColor($pull_request_id, $new_comment->parent_id);
        $this->color_assigner->assignColor($new_comment->parent_id, $color);

        $this->reference_manager->extractCrossRef(
            $new_comment->content,
            $pull_request_id,
            \pullrequestPlugin::REFERENCE_NATURE,
            $new_comment->project_id,
            (int) $new_comment->author->getId(),
            \pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        $this->event_dispatcher->dispatch(PullRequestNewCommentEvent::fromCommentID($comment_id));

        return Comment::fromNewComment($new_comment, $comment_id, $color);
    }
}
