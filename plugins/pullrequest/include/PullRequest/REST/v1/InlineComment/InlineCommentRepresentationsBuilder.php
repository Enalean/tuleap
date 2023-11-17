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

namespace Tuleap\PullRequest\REST\v1\InlineComment;

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\InlineComment\SearchInlineCommentsOnFile;
use Tuleap\PullRequest\PullRequest;
use Tuleap\User\REST\MinimalUserRepresentation;
use Tuleap\User\RetrieveUserById;

final class InlineCommentRepresentationsBuilder
{
    public function __construct(
        private readonly SearchInlineCommentsOnFile $comments_searcher,
        private readonly RetrieveUserById $user_retriever,
        private readonly SingleRepresentationBuilder $single_builder,
    ) {
    }

    /**
     * @return InlineCommentRepresentation[]
     */
    public function getForFile(PullRequest $pull_request, string $file_path, int $project_id): array
    {
        return array_map(
            fn(InlineComment $comment) => $this->buildForOneComment($comment, $project_id),
            $this->comments_searcher->searchUpToDateByFilePath($pull_request->getId(), $file_path)
        );
    }

    private function buildForOneComment(
        InlineComment $inline_comment,
        int $project_id,
    ): InlineCommentRepresentation {
        $comment_author = $this->user_retriever->getUserById($inline_comment->getUserId());
        if ($comment_author === null) {
            // User ID from comment is supposed to match an existing user. DB is corrupt ?
            throw new \RuntimeException(sprintf('Could not find user with id #%s', $inline_comment->getUserId()));
        }
        return $this->single_builder->build(
            $project_id,
            MinimalUserRepresentation::build($comment_author),
            $inline_comment
        );
    }
}
