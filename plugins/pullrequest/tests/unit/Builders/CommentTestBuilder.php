<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Tests\Builders;

use DateTimeImmutable;
use Tuleap\Option\Option;
use Tuleap\PullRequest\Comment\Comment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use function Psl\Type\int;

final class CommentTestBuilder
{
    private int $id              = 12;
    private int $pull_request_id = 54;
    private int $user_id         = 105;
    private int $post_date       = 1695212990;
    private int $parent_id       = 0;
    private string $color        = '';
    /**
     * @var Option<int>
     */
    private Option $last_edition_date;

    private function __construct(
        private readonly string $content,
        private readonly string $format,
    ) {
        $this->last_edition_date = Option::nothing(int());
    }

    public static function aMarkdownComment(string $content): self
    {
        return new self(
            $content,
            TimelineComment::FORMAT_MARKDOWN
        );
    }

    public static function aTextComment(string $content): self
    {
        return new self(
            $content,
            TimelineComment::FORMAT_TEXT
        );
    }

    public function withId(int $comment_id): self
    {
        $this->id = $comment_id;
        return $this;
    }

    public function byAuthor(\PFUser $author): self
    {
        $this->user_id = (int) $author->getId();
        return $this;
    }

    public function onPullRequest(PullRequest $pull_request): self
    {
        $this->pull_request_id = $pull_request->getId();
        return $this;
    }

    public function childOf(int $parent_comment_id): self
    {
        $this->parent_id = $parent_comment_id;
        return $this;
    }

    public function withEditionDate(DateTimeImmutable $last_edition_date): self
    {
        $this->last_edition_date = Option::fromValue($last_edition_date->getTimestamp());
        return $this;
    }

    public function build(): Comment
    {
        return new Comment(
            $this->id,
            $this->pull_request_id,
            $this->user_id,
            $this->post_date,
            $this->content,
            $this->parent_id,
            $this->color,
            $this->format,
            $this->last_edition_date
        );
    }
}
