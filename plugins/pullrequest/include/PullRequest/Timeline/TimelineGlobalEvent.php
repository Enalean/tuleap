<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

    /** @var int */
    private $id;

    /** @var int */
    private $pull_request_id;

    /** @var int */
    private $user_id;

    /** @var int */
    private $post_date;

    /** @var int */
    private $type;

    public function __construct($id, $pull_request_id, $user_id, $post_date, $type)
    {
        $this->id              = $id;
        $this->pull_request_id = $pull_request_id;
        $this->user_id         = $user_id;
        $this->post_date       = $post_date;
        $this->type            = $type;
    }

    public static function buildFromRow(array $row)
    {
        return new TimelineGlobalEvent(
            $row['id'],
            $row['pull_request_id'],
            $row['user_id'],
            $row['post_date'],
            $row['type']
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

    public function getType()
    {
        return $this->type;
    }
}
