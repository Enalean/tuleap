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

namespace Tuleap\PullRequest\REST\v1\InlineComment;

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\REST\v1\Comment\CommentContent;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-mutation-free
 */
final class InlineCommentRepresentation
{
    public readonly int $id;
    public readonly string $file_path;
    public readonly int $unidiff_offset;
    public readonly string $position;
    public readonly string $post_date;
    public readonly ?string $last_edition_date;
    public readonly string $content;
    public readonly string $raw_content;
    public readonly string $post_processed_content;
    public readonly int $parent_id;
    public readonly string $format;
    public readonly string $color;
    public readonly MinimalUserRepresentation $user;

    public function __construct(
        InlineComment $comment,
        CommentContent $comment_content,
        MinimalUserRepresentation $user,
    ) {
        $this->id                     = $comment->getId();
        $this->file_path              = $comment->getFilePath();
        $this->unidiff_offset         = $comment->getUnidiffOffset();
        $this->position               = $comment->getPosition();
        $this->post_date              = JsonCast::toDate($comment->getPostDate());
        $this->last_edition_date      = JsonCast::toDate($comment->getLastEditionDate()->unwrapOr(null));
        $this->content                = $comment_content->purified_content;
        $this->raw_content            = $comment_content->raw_content;
        $this->post_processed_content = $comment_content->post_processed_content;
        $this->parent_id              = $comment->getParentId();
        $this->format                 = $comment->getFormat();
        $this->color                  = $comment->getColor();
        $this->user                   = $user;
    }
}
