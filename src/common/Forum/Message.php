<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Forum;

/**
 * @psalm-immutable
 */
final class Message
{
    /**
     * @var string
     */
    private $subject;
    /**
     * @var string
     */
    private $body;
    /**
     * @var string
     */
    private $user_name;
    /**
     * @var int
     */
    private $date;
    /**
     * @var int
     */
    private $project_id;
    /**
     * @var int
     */
    private $thread_id;
    /**
     * @var int
     */
    private $forum_id;
    /**
     * @var string
     */
    private $forum_name;

    public function __construct(
        string $subject,
        string $body,
        string $user_name,
        int $date,
        int $project_id,
        int $thread_id,
        int $forum_id,
        string $forum_name,
    ) {
        $this->subject    = $subject;
        $this->body       = $body;
        $this->user_name  = $user_name;
        $this->date       = $date;
        $this->project_id = $project_id;
        $this->thread_id  = $thread_id;
        $this->forum_id   = $forum_id;
        $this->forum_name = $forum_name;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getUserName(): string
    {
        return $this->user_name;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }

    public function getThreadId(): int
    {
        return $this->thread_id;
    }

    public function getForumId(): int
    {
        return $this->forum_id;
    }

    public function getForumName(): string
    {
        return $this->forum_name;
    }
}
