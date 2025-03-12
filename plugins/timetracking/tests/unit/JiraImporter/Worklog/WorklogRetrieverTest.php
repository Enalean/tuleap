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

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraServerClientStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WorklogRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('getTestData')]
    public function testItBuildsWorklogsFromJiraCloudAPIResponse(JiraClient $jira_client, callable $tests): void
    {
        $retriever = new WorklogRetriever(
            $jira_client,
            new NullLogger()
        );

        $issue = new IssueAPIRepresentation(
            'ISSUE-1',
            10092,
            [],
            []
        );

        $worklogs = $retriever->getIssueWorklogsFromAPI($issue);

        $tests($worklogs);
    }

    public static function getTestData(): iterable
    {
        return [
            'it builds worklogs from Jira Cloud API Response' => [
                'jira_client' => JiraCloudClientStub::aJiraCloudClient([
                    '/rest/api/2/issue/ISSUE-1/worklog?startAt=0' => [
                        'maxResults' => 1048576,
                        'startAt'    => 0,
                        'total'      => 2,
                        'worklogs'   => [
                            [
                                'id'               => 10010,
                                'issueId'          => 10092,
                                'self'             => 'https://example.com/rest/api/2/issue/1/worklog/10010',
                                'timeSpentSeconds' => 144000,
                                'started'          => '2021-02-08T19:06:41.386+0100',
                                'author' => [
                                    'accountId'    => 'whatever123',
                                    'emailAddress' => 'whatever@example.com',
                                    'displayName'  => 'What Ever',
                                ],
                            ],
                            [
                                'id'               => 10011,
                                'issueId'          => 10092,
                                'self'             => 'https://example.com/rest/api/2/issue/1/worklog/10011',
                                'timeSpentSeconds' => 18000,
                                'started'          => '2021-02-10T06:09:32.083+0100',
                                'author' => [
                                    'accountId'    => 'whatever123',
                                    'emailAddress' => 'whatever@example.com',
                                    'displayName'  => 'What Ever',
                                ],
                            ],
                        ],
                    ],
                ]),
                'tests' => function (array $worklogs) {
                    self::assertCount(2, $worklogs);

                    $first_worklog = $worklogs[0];
                    self::assertSame(144000, $first_worklog->getSeconds());
                    self::assertSame(1612807601, $first_worklog->getStartDate()->getTimestamp());
                    self::assertSame('What Ever', $first_worklog->getAuthor()->getDisplayName());
                    self::assertSame('whatever@example.com', $first_worklog->getAuthor()->getEmailAddress());

                    $last_worklog = $worklogs[1];
                    self::assertSame(18000, $last_worklog->getSeconds());
                    self::assertSame(1612933772, $last_worklog->getStartDate()->getTimestamp());
                    self::assertSame('What Ever', $last_worklog->getAuthor()->getDisplayName());
                    self::assertSame('whatever@example.com', $last_worklog->getAuthor()->getEmailAddress());
                },
            ],
            'it builds worklogs from Jira Server API Response' => [
                'jira_client' => JiraServerClientStub::aJiraServerClient([
                    '/rest/api/2/issue/ISSUE-1/worklog?startAt=0' => [
                        'startAt'    => 0,
                        'maxResults' => 1,
                        'total'      => 1,
                        'worklogs'   => [
                            [
                                'self'             => 'https://jira.example.com/rest/api/2/issue/1/worklog/12609',
                                'author'           => [
                                    'self'         => 'https://jira.example.com/rest/api/2/user?username=john.doe',
                                    'name'         => 'john.doe',
                                    'key'          => 'john.doe',
                                    'emailAddress' => 'john.doe@example.com',
                                    'avatarUrls'   => [
                                        '48x48' => 'https://jira.example.com/secure/useravatar?avatarId=10341',
                                        '24x24' => 'https://jira.example.com/secure/useravatar?size=small&avatarId=10341',
                                        '16x16' => 'https://jira.example.com/secure/useravatar?size=xsmall&avatarId=10341',
                                        '32x32' => 'https://jira.example.com/secure/useravatar?size=medium&avatarId=10341',
                                    ],
                                    'displayName'  => 'John Doe',
                                    'active'       => true,
                                    'timeZone'     => 'Europe/Paris',
                                ],
                                'updateAuthor'     => [
                                    'self'         => 'https://jira.example.com/rest/api/2/user?username=john.doe',
                                    'name'         => 'john.doe',
                                    'key'          => 'john.doe',
                                    'emailAddress' => 'john.doe@example.com',
                                    'avatarUrls'   => [
                                        '48x48' => 'https://jira.example.com/secure/useravatar?avatarId=10341',
                                        '24x24' => 'https://jira.example.com/secure/useravatar?size=small&avatarId=10341',
                                        '16x16' => 'https://jira.example.com/secure/useravatar?size=xsmall&avatarId=10341',
                                        '32x32' => 'https://jira.example.com/secure/useravatar?size=medium&avatarId=10341',
                                    ],
                                    'displayName'  => 'John Doe',
                                    'active'       => true,
                                    'timeZone'     => 'Europe/Paris',
                                ],
                                'comment'          => 'DHCP Issue',
                                'created'          => '2022-03-18T09:29:14.392+0100',
                                'updated'          => '2022-03-18T09:29:14.392+0100',
                                'started'          => '2022-03-18T09:27:00.000+0100',
                                'timeSpent'        => '30m',
                                'timeSpentSeconds' => 1800,
                                'id'               => '12609',
                                'issueId'          => '1',
                            ],
                        ],
                    ],
                ]),
                'tests' => function (array $worklogs) {
                    self::assertCount(1, $worklogs);

                    self::assertSame(1800, $worklogs[0]->getSeconds());
                    self::assertSame(1647592020, $worklogs[0]->getStartDate()->getTimestamp());
                    self::assertSame('John Doe', $worklogs[0]->getAuthor()->getDisplayName());
                    self::assertSame('john.doe@example.com', $worklogs[0]->getAuthor()->getEmailAddress());
                },
            ],
        ];
    }
}
