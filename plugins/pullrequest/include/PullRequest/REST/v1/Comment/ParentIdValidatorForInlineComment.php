<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Luracast\Restler\RestException;
use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\InlineCommentRetriever;

final class ParentIdValidatorForInlineComment
{
    public function __construct(private readonly InlineCommentRetriever $inline_comment_retriever)
    {
    }

    /**
     * @throws RestException
     */
    public function checkParentValidity(int $parent_id, int $pullrequest_id): void
    {
        if ($parent_id === 0) {
            return;
        }

        $this->inline_comment_retriever->getInlineCommentByID($parent_id)->match(
            function (InlineComment $inline_comment) use ($pullrequest_id) {
                if ($inline_comment->getParentId() !== 0) {
                    throw new RestException(
                        400,
                        'You can only add parent on the first comment of thread of pullrequest'
                    );
                }

                if ($inline_comment->getPullRequestId() !== $pullrequest_id) {
                    throw new RestException(
                        400,
                        sprintf(
                            'Parent comment #%d must be the same than provided comment #%d for reply',
                            $inline_comment->getParentId(),
                            $pullrequest_id
                        )
                    );
                }
            },
            fn() => throw new RestException(404, sprintf('Comment with id #%d is not found', $parent_id))
        );
    }
}
