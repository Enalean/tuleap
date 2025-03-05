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
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\JiraImporter\Worklog;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorklogTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAWorklogObjectFromAPIResponse(): void
    {
        $response = [
            'id'               => 10010,
            'issueId'          => 10092,
            'self'             => 'https://example.com/rest/api/3/issue/1/worklog/10010',
            'timeSpentSeconds' => 144000,
            'started'          => '2021-02-08T19:06:41.386+0100',
            'author' => [
                'accountId'    => 'whatever123',
                'emailAddress' => 'whatever@example.com',
                'displayName'  => 'What Ever',
            ],
            'comment' => '*Comment* {color:#36b37e}*RED*{color}',
        ];

        $worklog = Worklog::buildFromJiraCloudAPIResponse($response);

        self::assertSame(1612807601, $worklog->getStartDate()->getTimestamp());
        self::assertSame(144000, $worklog->getSeconds());

        self::assertSame('What Ever', $worklog->getAuthor()->getDisplayName());
        self::assertSame('whatever@example.com', $worklog->getAuthor()->getEmailAddress());
        self::assertSame('*Comment* {color:#36b37e}*RED*{color}', $worklog->getComment());
    }

    public function testItThrowsAnExceptionIfMandatoryKeyIsMissing(): void
    {
        $response = [
            'id'               => 10010,
            'issueId'          => 10092,
            'self'             => 'https://example.com/rest/api/3/issue/1/worklog/10010',
            'timeSpentSeconds' => 144000,
            'started'          => '2021-02-08T19:06:41.386+0100',
            'author' => [
                'accountId'    => 'whatever123',
                'emailAddress' => 'whatever@example.com',
                'displayName'  => 'What Ever',
            ],
        ];

        $this->expectException(WorklogAPIResponseNotWellFormedException::class);
        $this->expectExceptionMessage('Provided worklog does not have all the expected content: `started`, `timeSpentSeconds` and `author`.');

        Worklog::buildFromJiraCloudAPIResponse($response);

        $response = [
            'id'               => 10010,
            'issueId'          => 10092,
            'self'             => 'https://example.com/rest/api/3/issue/1/worklog/10010',
            'timeSpentSeconds' => 144000,
            'started'          => '2021-02-08T19:06:41.386+0100',
            'author' => [
                'accountId'    => 'whatever123',
                'emailAddress' => 'whatever@example.com',
                'displayName'  => 'What Ever',
            ],
        ];

        $this->expectException(WorklogAPIResponseNotWellFormedException::class);
        $this->expectExceptionMessage('Provided worklog does not have all the expected content: `started`, `timeSpentSeconds` and `author`.');

        Worklog::buildFromJiraCloudAPIResponse($response);

        $response = [
            'id'               => 10010,
            'issueId'          => 10092,
            'self'             => 'https://example.com/rest/api/3/issue/1/worklog/10010',
            'author' => [
                'accountId'    => 'whatever123',
                'emailAddress' => 'whatever@example.com',
                'displayName'  => 'What Ever',
            ],
        ];

        $this->expectException(WorklogAPIResponseNotWellFormedException::class);
        $this->expectExceptionMessage('Provided worklog does not have all the expected content: `started`, `timeSpentSeconds` and `author`.');

        Worklog::buildFromJiraCloudAPIResponse($response);
    }

    public function testItThrowsAnExceptionIfMandatoryAuthorInformationIsMissing(): void
    {
        $response = [
            'id'               => 10010,
            'issueId'          => 10092,
            'self'             => 'https://example.com/rest/api/3/issue/1/worklog/10010',
            'timeSpentSeconds' => 144000,
            'started'          => '2021-02-08T19:06:41.386+0100',
            'author' => [
                'emailAddress' => 'whatever@example.com',
                'displayName'  => 'What Ever',
            ],
        ];

        $this->expectException(WorklogAPIResponseNotWellFormedException::class);
        $this->expectExceptionMessage('Provided worklog author does not have all the expected content: `displayName` and `accountId`.');

        Worklog::buildFromJiraCloudAPIResponse($response);

        $response = [
            'id'               => 10010,
            'issueId'          => 10092,
            'self'             => 'https://example.com/rest/api/3/issue/1/worklog/10010',
            'timeSpentSeconds' => 144000,
            'started'          => '2021-02-08T19:06:41.386+0100',
            'author' => [
                'accountId'    => 'whatever123',
                'emailAddress' => 'whatever@example.com',
            ],
        ];

        $this->expectException(WorklogAPIResponseNotWellFormedException::class);
        $this->expectExceptionMessage('Provided worklog author does not have all the expected content: `displayName` and `accountId`.');

        Worklog::buildFromJiraCloudAPIResponse($response);
    }
}
