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
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraCloudUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraServerUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUser;

/**
 * @psalm-immutable
 */
class Worklog
{
    public function __construct(private DateTimeImmutable $start_date, private int $seconds, private JiraUser $author, private string $comment)
    {
    }

    public static function buildFromJiraCloudAPIResponse(?array $worklog_response): self
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
        $author     = new ActiveJiraCloudUser($worklog_response['author']);

        $comment = '';
        if (isset($worklog_response['comment'])) {
            $comment = $worklog_response['comment'];
        }

        return new self(
            $start_date,
            $seconds,
            $author,
            $comment
        );
    }

    public static function buildFromJiraServerAPIResponse(?array $worklog_response): self
    {
        if ($worklog_response === null) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog response does not have any content."
            );
        }

        if (
            ! isset($worklog_response['started'], $worklog_response['timeSpentSeconds']) || ! isset($worklog_response['author']) || ! is_array(
                $worklog_response['author']
            )
        ) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog does not have all the expected content: `started`, `timeSpentSeconds` and `author`."
            );
        }


        if (
            ! isset($worklog_response['author']['displayName'], $worklog_response['author']['name']) ||
            ! is_string($worklog_response['author']['displayName']) ||
            ! is_string($worklog_response['author']['name'])
        ) {
            throw new WorklogAPIResponseNotWellFormedException(
                "Provided worklog author does not have all the expected content: `displayName` and `name`."
            );
        }

        $start_date = new DateTimeImmutable($worklog_response['started']);
        $seconds    = (int) $worklog_response['timeSpentSeconds'];
        $author     = ActiveJiraServerUser::buildFromPayload($worklog_response['author']);

        $comment = '';
        if (isset($worklog_response['comment'])) {
            $comment = $worklog_response['comment'];
        }

        return new self(
            $start_date,
            $seconds,
            $author,
            $comment
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

    public function getComment(): string
    {
        return $this->comment;
    }
}
