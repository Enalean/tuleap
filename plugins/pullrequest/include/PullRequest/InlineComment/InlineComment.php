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

use Tuleap\PullRequest\Timeline\TimelineEvent;

class InlineComment implements TimelineEvent
{
    private $id;
    private $pull_request_id;
    private $user_id;
    private $post_date;
    private $file_path;
    private $unidiff_offset;
    private $content;
    private $is_outdated;

    public function __construct(
        $id,
        $pull_request_id,
        $user_id,
        $post_date,
        $file_path,
        $unidiff_offset,
        $content,
        $is_outdated
    ) {
        $this->id              = $id;
        $this->pull_request_id = $pull_request_id;
        $this->user_id         = $user_id;
        $this->post_date       = $post_date;
        $this->file_path       = $file_path;
        $this->unidiff_offset  = $unidiff_offset;
        $this->content         = $content;
        $this->is_outdated     = $is_outdated;
    }

    public static function buildFromRow($row)
    {
        return new InlineComment(
            (int) $row['id'],
            (int) $row['pull_request_id'],
            (int) $row['user_id'],
            $row['post_date'],
            $row['file_path'],
            (int) $row['unidiff_offset'],
            $row['content'],
            (bool) $row['is_outdated']
        );
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPullRequestId()
    {
        return $this->pull_request_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getPostDate(): int
    {
        return $this->post_date;
    }

    public function getFilePath()
    {
        return $this->file_path;
    }

    public function getUnidiffOffset()
    {
        return $this->unidiff_offset;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function isOutdated()
    {
        return $this->is_outdated;
    }

    public function markAsOutdated()
    {
        $this->is_outdated = true;
    }

    public function setUnidiffOffset($unidiff_offset)
    {
        $this->unidiff_offset = $unidiff_offset;
    }
}
