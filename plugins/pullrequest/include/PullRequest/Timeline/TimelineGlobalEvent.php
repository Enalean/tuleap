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

namespace Tuleap\PullRequest\Timeline;

class TimelineGlobalEvent implements TimelineEvent
{
    public const UPDATE  = 1;
    public const REBASE  = 2;
    public const MERGE   = 3;
    public const ABANDON = 4;
    public const REOPEN  = 5;

    private function __construct(private int $id, private int $pull_request_id, private int $user_id, private int $post_date, private int $type)
    {
    }

    public static function buildFromRow(array $row): self
    {
        return new TimelineGlobalEvent(
            $row['id'],
            $row['pull_request_id'],
            $row['user_id'],
            $row['post_date'],
            $row['type'],
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

    public function getType(): int
    {
        return $this->type;
    }
}
