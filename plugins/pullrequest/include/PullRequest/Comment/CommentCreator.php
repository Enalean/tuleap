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

    public function create(Comment $new_comment, int $project_id): Comment
    {
        $pull_request_id = $new_comment->getPullRequestId();

        $comment_id = $this->comment_saver->save(
            $pull_request_id,
            $new_comment->getUserId(),
            $new_comment->getPostDate(),
            $new_comment->getContent(),
            $new_comment->getFormat(),
            $new_comment->getParentId()
        );

        $color = $this->color_retriever->retrieveColor($pull_request_id, $new_comment->getParentId());
        $this->color_assigner->assignColor($new_comment->getParentId(), $color);

        $this->reference_manager->extractCrossRef(
            $new_comment->getContent(),
            $pull_request_id,
            \pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            $new_comment->getUserId(),
            \pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        $this->event_dispatcher->dispatch(PullRequestNewCommentEvent::fromCommentID($comment_id));

        return Comment::buildWithNewId($comment_id, $new_comment, $color);
    }
}
