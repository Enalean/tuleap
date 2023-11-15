<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\InlineComment;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\PullRequest\InlineComment\Notification\PullRequestNewInlineCommentEvent;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorAssigner;
use Tuleap\PullRequest\REST\v1\Comment\ThreadCommentColorRetriever;
use Tuleap\PullRequest\REST\v1\PullRequestInlineCommentPOSTRepresentation;
use Tuleap\Reference\ExtractAndSaveCrossReferences;

final class InlineCommentCreator
{
    public function __construct(
        private CreateInlineComment $comment_saver,
        private ExtractAndSaveCrossReferences $reference_manager,
        private EventDispatcherInterface $event_dispatcher,
        private ThreadCommentColorRetriever $color_retriever,
        private ThreadCommentColorAssigner $color_assigner,
    ) {
    }

    public function insert(
        PullRequest $pull_request,
        \PFUser $user,
        PullRequestInlineCommentPOSTRepresentation $comment_data,
        \DateTimeImmutable $post_date,
        int $project_id,
    ): InsertedInlineComment {
        $pull_request_id = $pull_request->getId();

        $format = $comment_data->format;
        if (! $format) {
            $format = TimelineComment::FORMAT_MARKDOWN;
        }

        $new_comment = new NewInlineComment(
            $pull_request,
            $project_id,
            $comment_data->file_path,
            $comment_data->unidiff_offset,
            $comment_data->content,
            $format,
            $comment_data->position,
            $comment_data->parent_id ?? 0,
            $user,
            $post_date
        );

        $inserted = $this->comment_saver->insert($new_comment);

        $color = $this->color_retriever->retrieveColor($pull_request_id, (int) $comment_data->parent_id);
        $this->color_assigner->assignColor((int) $comment_data->parent_id, $color);

        $this->reference_manager->extractCrossRef(
            $comment_data->content,
            $pull_request_id,
            \pullrequestPlugin::REFERENCE_NATURE,
            $project_id,
            (int) $user->getId(),
            \pullrequestPlugin::PULLREQUEST_REFERENCE_KEYWORD
        );

        $this->event_dispatcher->dispatch(PullRequestNewInlineCommentEvent::fromInlineCommentID($inserted));

        return InsertedInlineComment::build($inserted, $color);
    }
}
