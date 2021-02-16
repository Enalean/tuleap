<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\JiraImporter\Worklog;

use DateTimeImmutable;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;

/**
 * @psalm-immutable
 */
class Worklog
{
    /**
     * @var DateTimeImmutable
     */
    private $start_date;

    /**
     * @var int
     */
    private $seconds;

    /**
     * @var JiraUser
     */
    private $author;

    public function __construct(DateTimeImmutable $start_date, int $seconds, JiraUser $author)
    {
        $this->start_date = $start_date;
        $this->seconds    = $seconds;
        $this->author     = $author;
    }

    public static function buildFromAPIResponse(?array $worklog_response): self
    {
        if ($worklog_response === null) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog response does not have any content."
            );
        }

        if (
            ! isset($worklog_response['started']) ||
            ! isset($worklog_response['timeSpentSeconds']) ||
            ! isset($worklog_response['author']) ||
            ! is_array($worklog_response['author'])
        ) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog does not have all the expected content: `started`, `timeSpentSeconds` and `author`."
            );
        }

        if (
            ! isset($worklog_response['author']['displayName']) ||
            ! isset($worklog_response['author']['accountId'])
        ) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog author does not have all the expected content: `displayName` and `accountId`."
            );
        }

        $start_date = new DateTimeImmutable($worklog_response['started']);
        $seconds    = (int) $worklog_response['timeSpentSeconds'];
        $author     = new JiraUser($worklog_response['author']);

        return new self(
            $start_date,
            $seconds,
            $author
        );
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->start_date;
    }

    public function getSeconds(): int
    {
        return $this->seconds;
    }

    public function getAuthor(): JiraUser
    {
        return $this->author;
    }
}
