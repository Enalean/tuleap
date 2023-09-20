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

namespace Tuleap\PullRequest\InlineComment;

use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\PullRequest\Timeline\TimelineEvent;

final class InlineComment implements TimelineEvent, TimelineComment
{
    public function __construct(
        private int $id,
        private int $pull_request_id,
        private int $user_id,
        private int $post_date,
        private string $file_path,
        private int $unidiff_offset,
        private string $content,
        private bool $is_outdated,
        private int $parent_id,
        private string $position,
        private string $color,
        private string $format,
    ) {
    }

    public static function buildFromRow($row): InlineComment
    {
        return new InlineComment(
            (int) $row['id'],
            (int) $row['pull_request_id'],
            (int) $row['user_id'],
            $row['post_date'],
            $row['file_path'],
            (int) $row['unidiff_offset'],
            $row['content'],
            (bool) $row['is_outdated'],
            (int) $row['parent_id'],
            $row['position'],
            $row['color'],
            $row['format']
        );
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

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function getUnidiffOffset(): int
    {
        return $this->unidiff_offset;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function isOutdated(): bool
    {
        return $this->is_outdated;
    }

    public function markAsOutdated(): void
    {
        $this->is_outdated = true;
    }

    public function setUnidiffOffset($unidiff_offset): void
    {
        $this->unidiff_offset = $unidiff_offset;
    }

    public function getParentId(): int
    {
        return $this->parent_id;
    }

    public function getPosition(): string
    {
        return $this->position;
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
