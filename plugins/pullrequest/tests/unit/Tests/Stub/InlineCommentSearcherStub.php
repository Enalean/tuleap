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

namespace Tuleap\PullRequest\Tests\Stub;

use Tuleap\PullRequest\InlineComment\InlineComment;

final class InlineCommentSearcherStub implements \Tuleap\PullRequest\InlineComment\InlineCommentSearcher
{
    private function __construct(private readonly ?array $row)
    {
    }

    public function searchByCommentID(int $inline_comment_id): ?array
    {
        return $this->row;
    }

    public static function withNoComment(): self
    {
        return new self(null);
    }

    public static function withComment(InlineComment $comment): self
    {
        $row = [
            'id'                => $comment->getId(),
            'pull_request_id'   => $comment->getPullRequestId(),
            'user_id'           => $comment->getUserId(),
            'post_date'         => $comment->getPostDate()->getTimestamp(),
            'file_path'         => $comment->getFilePath(),
            'unidiff_offset'    => $comment->getUnidiffOffset(),
            'content'           => $comment->getContent(),
            'is_outdated'       => $comment->isOutdated() ? 1 : 0,
            'parent_id'         => $comment->getParentId(),
            'position'          => $comment->getPosition(),
            'color'             => $comment->getColor(),
            'format'            => $comment->getFormat(),
            'last_edition_date' => $comment->getLastEditionDate()
                ->mapOr(static fn(\DateTimeImmutable $last_edition_date) => $last_edition_date->getTimestamp(), null),
        ];
        return new self($row);
    }
}
