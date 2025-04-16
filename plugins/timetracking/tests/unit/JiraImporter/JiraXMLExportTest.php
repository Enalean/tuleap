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

namespace Tuleap\Timetracking\JiraImporter;

use PFUser;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use Tuleap\Timetracking\JiraImporter\Worklog\Worklog;
use Tuleap\Timetracking\JiraImporter\Worklog\WorklogRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\IssueAPIRepresentationCollection;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\ActiveJiraCloudUser;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserRetriever;
use XML_SimpleXMLCDATAFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JiraXMLExportTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&WorklogRetriever
     */
    private $worklog_retriever;
    /**
     * @var JiraUserRetriever&\PHPUnit\Framework\MockObject\MockObject
     */
    private $jira_user_retriever;
    private JiraXMLExport $exporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worklog_retriever   = $this->createMock(WorklogRetriever::class);
        $this->jira_user_retriever = $this->createMock(JiraUserRetriever::class);

        $this->exporter = new JiraXMLExport(
            $this->worklog_retriever,
            new XML_SimpleXMLCDATAFactory(),
            $this->jira_user_retriever,
            new NullLogger()
        );
    }

    public function testItEnablesTimetrackingConfigurationForJiraTrackerAndImportTimes(): void
    {
        $xml_tracker            = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $platform_configuration = new PlatformConfiguration();
        $issue_collection       = new IssueAPIRepresentationCollection();

        $platform_configuration->addAllowedConfiguration('jira_timetracking');

        $issue_representation = new IssueAPIRepresentation(
            'ISSUE-1',
            10092,
            [],
            []
        );

        $issue_collection->addIssueRepresentationInCollection($issue_representation);

        $this->worklog_retriever
            ->expects($this->once())
            ->method('getIssueWorklogsFromAPI')
            ->with($issue_representation)
            ->willReturn([
                new Worklog(
                    new \DateTimeImmutable('2021-02-08T19:06:41.386+0100'),
                    18000,
                    new ActiveJiraCloudUser(
                        [
                            'accountId'    => 'whatever123',
                            'emailAddress' => 'whatever@example.com',
                            'displayName'  => 'What Ever',
                        ]
                    ),
                    'content 01 content 02'
                ),
            ]);

        $time_user = $this->createMock(PFUser::class);
        $time_user->method('getUserName')->willReturn('user_time');
        $time_user->method('getId')->willReturn('147');

        $this->jira_user_retriever
            ->expects($this->once())
            ->method('retrieveJiraAuthor')
            ->willReturn($time_user);

        $this->exporter->exportJiraTimetracking(
            $xml_tracker,
            $platform_configuration,
            $issue_collection
        );

        $this->assertTimetrackingConfiguration($xml_tracker);

        self::assertTrue(isset($xml_tracker->timetracking->time));
        self::assertSame('2021-02-08T19:06:41+01:00', (string) $xml_tracker->timetracking->time->day);
        self::assertSame('300', (string) $xml_tracker->timetracking->time->minutes);
        self::assertSame('user_time', (string) $xml_tracker->timetracking->time->user);
        self::assertSame('username', (string) $xml_tracker->timetracking->time->user['format']);
        self::assertSame('content 01 content 02', (string) $xml_tracker->timetracking->time->step);
    }

    public function testItOnlyEnablesTimetrackingConfigurationIfProviderIsNotKnown(): void
    {
        $xml_tracker            = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');
        $platform_configuration = new PlatformConfiguration();
        $issue_collection       = new IssueAPIRepresentationCollection();

        $this->worklog_retriever->expects($this->never())->method('getIssueWorklogsFromAPI');

        $this->exporter->exportJiraTimetracking(
            $xml_tracker,
            $platform_configuration,
            $issue_collection
        );

        $this->assertTimetrackingConfiguration($xml_tracker);

        self::assertFalse(isset($xml_tracker->timetracking->time));
    }

    private function assertTimetrackingConfiguration(SimpleXMLElement $xml_tracker): void
    {
        self::assertTrue(isset($xml_tracker->timetracking));
        self::assertSame('1', (string) $xml_tracker->timetracking['is_enabled']);

        self::assertTrue(isset($xml_tracker->timetracking->permissions));
        self::assertTrue(isset($xml_tracker->timetracking->permissions->write));
        self::assertCount(1, $xml_tracker->timetracking->permissions->write->children());
        self::assertSame('project_members', (string) $xml_tracker->timetracking->permissions->write->ugroup);

        self::assertFalse(isset($xml_tracker->timetracking->permissions->read));
    }
}
