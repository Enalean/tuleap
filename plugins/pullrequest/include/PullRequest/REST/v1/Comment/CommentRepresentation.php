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

namespace Tuleap\PullRequest\REST\v1\Comment;

use Tuleap\PullRequest\Comment\Comment;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

/**
 * @psalm-mutation-free
 */
final class CommentRepresentation
{
    public const TYPE = 'comment';

    public int $id;

    public int $project_id;

    public string $post_date;

    public ?string $last_edition_date;

    public string $content;

    public string $raw_content;

    public string $post_processed_content;

    public string $type;

    public int $parent_id;

    public string $format;

    public string $color;

    public function __construct(
        Comment $comment,
        CommentContent $comment_content,
        int $project_id,
        public MinimalUserRepresentation $user,
    ) {
        $this->id                     = $comment->getId();
        $this->color                  = $comment->getColor();
        $this->parent_id              = $comment->getParentId();
        $this->format                 = $comment->getFormat();
        $this->post_date              = JsonCast::toDate($comment->getPostDate());
        $this->last_edition_date      = JsonCast::toDate($comment->getLastEditionDate()->unwrapOr(null));
        $this->project_id             = $project_id;
        $this->type                   = self::TYPE;
        $this->content                = $comment_content->purified_content;
        $this->raw_content            = $comment_content->raw_content;
        $this->post_processed_content = $comment_content->post_processed_content;
    }
}
