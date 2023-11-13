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

use Tuleap\PullRequest\InlineComment\InlineComment;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;

final class InlineCommentTestBuilder
{
    private int $id              = 13;
    private int $pull_request_id = 54;
    private int $user_id         = 105;
    private int $post_date       = 1695212990;
    private int $parent_id       = 0;
    private string $color        = '';
    private string $file_path    = 'plugins/pullrequests/tests/unit/Builder/InlineCommentTestBuilder.php';
    private int $unidiff_offset  = 37;
    private bool $is_outdated    = false;
    private string $position     = 'right';

    private function __construct(
        private readonly string $content,
        private readonly string $format,
    ) {
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

    public function withId(int $inline_comment_id): self
    {
        $this->id = $inline_comment_id;
        return $this;
    }

    public function withFilePath(string $file_path): self
    {
        $this->file_path = $file_path;
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

    public function thatIsUpToDate(): self
    {
        $this->is_outdated = false;
        return $this;
    }

    public function onUnidiffOffset(int $unidiff_offset): self
    {
        $this->unidiff_offset = $unidiff_offset;
        return $this;
    }

    public function build(): InlineComment
    {
        return new InlineComment(
            $this->id,
            $this->pull_request_id,
            $this->user_id,
            $this->post_date,
            $this->file_path,
            $this->unidiff_offset,
            $this->content,
            $this->is_outdated,
            $this->parent_id,
            $this->position,
            $this->color,
            $this->format,
        );
    }
}
