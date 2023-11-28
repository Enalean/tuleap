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

use Tuleap\Option\Option;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Timeline\TimelineEvent;

/**
 * @psalm-immutable
 */
final class Comment implements TimelineEvent, TimelineComment
{
    /**
     * @param Option<\DateTimeImmutable> $last_edition_date
     */
    public function __construct(
        private int $id,
        private int $pull_request_id,
        private int $user_id,
        private int $post_date,
        private string $content,
        private int $parent_id,
        private string $color,
        private string $format,
        private Option $last_edition_date,
    ) {
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

    /**
     * @return Option<\DateTimeImmutable>
     */
    public function getLastEditionDate(): Option
    {
        return $this->last_edition_date;
    }

    public static function buildWithNewContent(Comment $comment, string $new_content, \DateTimeImmutable $last_edition_date): self
    {
        return new self(
            $comment->id,
            $comment->pull_request_id,
            $comment->user_id,
            $comment->post_date,
            $new_content,
            $comment->parent_id,
            $comment->color,
            $comment->format,
            Option::fromValue($last_edition_date)
        );
    }

    public static function buildFromRow(array $row): self
    {
        $last_edition_date = Option::fromNullable($row['last_edition_date'])
            ->map(static fn(int $timestamp) => new \DateTimeImmutable('@' . $timestamp));
        return new Comment(
            $row['id'],
            $row['pull_request_id'],
            $row['user_id'],
            $row['post_date'],
            $row['content'],
            (int) $row['parent_id'],
            $row['color'],
            $row['format'],
            $last_edition_date,
        );
    }

    public static function buildWithNewId(int $new_comment_id, Comment $comment): self
    {
        return new self(
            $new_comment_id,
            $comment->pull_request_id,
            $comment->user_id,
            $comment->post_date,
            $comment->content,
            $comment->parent_id,
            $comment->color,
            $comment->format,
            $comment->last_edition_date
        );
    }
}
