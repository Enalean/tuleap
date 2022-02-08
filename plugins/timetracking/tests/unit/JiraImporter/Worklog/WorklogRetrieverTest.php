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
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;

class WorklogRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsWorklogsFromAPIResponse(): void
    {
        $jira_client = new class extends JiraCloudClientStub
        {
            public function getUrl(string $url): ?array
            {
                return [
                    "maxResults" => 1048576,
                    "startAt"    => 0,
                    "total"      => 2,
                    "worklogs"   => [
                        [
                            "id"               => 10010,
                            "issueId"          => 10092,
                            "self"             => "https://example.com/rest/api/3/issue/1/worklog/10010",
                            "timeSpentSeconds" => 144000,
                            "started"          => "2021-02-08T19:06:41.386+0100",
                            "author" => [
                                "accountId"    => "whatever123",
                                "emailAddress" => "whatever@example.com",
                                "displayName"  => "What Ever",
                            ],
                        ],
                        [
                            "id"               => 10011,
                            "issueId"          => 10092,
                            "self"             => "https://example.com/rest/api/3/issue/1/worklog/10011",
                            "timeSpentSeconds" => 18000,
                            "started"          => "2021-02-10T06:09:32.083+0100",
                            "author" => [
                                "accountId"    => "whatever123",
                                "emailAddress" => "whatever@example.com",
                                "displayName"  => "What Ever",
                            ],
                        ],
                    ],
                ];
            }
        };

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

        $this->assertCount(2, $worklogs);

        $first_worklog = $worklogs[0];
        $this->assertSame(144000, $first_worklog->getSeconds());
        $this->assertSame(1612807601, $first_worklog->getStartDate()->getTimestamp());
        $this->assertSame("What Ever", $first_worklog->getAuthor()->getDisplayName());
        $this->assertSame("whatever@example.com", $first_worklog->getAuthor()->getEmailAddress());

        $last_worklog = $worklogs[1];
        $this->assertSame(18000, $last_worklog->getSeconds());
        $this->assertSame(1612933772, $last_worklog->getStartDate()->getTimestamp());
        $this->assertSame("What Ever", $last_worklog->getAuthor()->getDisplayName());
        $this->assertSame("whatever@example.com", $last_worklog->getAuthor()->getEmailAddress());
    }
}
