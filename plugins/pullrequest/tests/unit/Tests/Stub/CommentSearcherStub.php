<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\Comment\CommentSearcher;

final class CommentSearcherStub implements CommentSearcher
{
    private function __construct(private readonly ?array $row)
    {
    }

    public function searchByCommentID(int $comment_id): ?array
    {
        return $this->row;
    }

    public static function withNoComment(): self
    {
        return new self(null);
    }

    public static function withComment(Comment $comment): self
    {
        $row = [
            'id'                => $comment->getId(),
            'pull_request_id'   => $comment->getPullRequestId(),
            'user_id'           => $comment->getUserId(),
            'post_date'         => $comment->getPostDate(),
            'content'           => $comment->getContent(),
            'parent_id'         => $comment->getParentId(),
            'color'             => $comment->getColor(),
            'format'            => $comment->getFormat(),
            'last_edition_date' => $comment->getLastEditionDate()
                ->mapOr(static fn(\DateTimeImmutable $last_edition_date) => $last_edition_date->getTimestamp(), null),
        ];
        return new self($row);
    }
}
