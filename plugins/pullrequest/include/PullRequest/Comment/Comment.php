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

use Tuleap\PullRequest\Timeline\TimelineEvent;

/**
 * @psalm-immutable
 */
final class Comment implements TimelineEvent
{
    public const FORMAT_TEXT     = 'text';
    public const FORMAT_MARKDOWN = "commonmark";

    public function __construct(private int $id, private int $pull_request_id, private int $user_id, private int $post_date, private string $content, private int $parent_id, private string $color, private string $format)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPullRequestId(): int
    {
        return $this->pull_request_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getPostDate(): int
    {
        return $this->post_date;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}
