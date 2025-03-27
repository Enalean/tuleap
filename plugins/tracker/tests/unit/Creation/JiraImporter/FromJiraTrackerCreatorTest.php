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

namespace Tuleap\Tracker\Creation\JiraImporter;

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfiguration;
use Tuleap\Tracker\Creation\JiraImporter\Configuration\PlatformConfigurationRetriever;
use Tuleap\Tracker\Creation\JiraImporter\Import\JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraTuleapUsersMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\User\JiraUserOnTuleapCache;
use Tuleap\Tracker\Creation\JiraImporter\UserRole\UserIsNotProjectAdminException;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Creation\TrackerCreationHasFailedException;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\UserRole\UserRolesCheckerInterfaceStub;

#[DisableReturnValueGenerationForTestDoubles]
final class FromJiraTrackerCreatorTest extends TestCase
{
    private TrackerCreationDataChecker&MockObject $creation_data_checker;
    private TrackerFactory&MockObject $tracker_factory;
    private TrackerXmlImport&MockObject $tracker_xml_import;
    private TestLogger $logger;
    private JiraUserOnTuleapCache $jira_user_on_tuleap_cache;
    private PlatformConfigurationRetriever&MockObject $platform_configuration_retriever;

    protected function setUp(): void
    {
        $this->tracker_xml_import               = $this->createMock(TrackerXmlImport::class);
        $this->tracker_factory                  = $this->createMock(TrackerFactory::class);
        $this->creation_data_checker            = $this->createMock(TrackerCreationDataChecker::class);
        $this->logger                           = new TestLogger();
        $this->jira_user_on_tuleap_cache        = new JiraUserOnTuleapCache(new JiraTuleapUsersMapping(), UserTestBuilder::buildWithDefaults());
        $this->platform_configuration_retriever = $this->createMock(PlatformConfigurationRetriever::class);
    }

    public function testItDuplicatedATrackerFromJira(): void
    {
        $jira_client = $this->createMock(JiraClient::class);
        $jira_client->method('getUrl')
            ->willReturnOnConsecutiveCalls(
                ['id' => '10005', 'name' => 'Story', 'subtask' => false],
                ['startAt' => 0, 'total' => 0, 'isLast' => true, 'values' => []],
            );

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->creation_data_checker->expects($this->once())->method('checkAtProjectCreation');

        $this->platform_configuration_retriever->expects($this->once())->method('getJiraPlatformConfiguration')
            ->with($jira_client, $this->logger)
            ->willReturn(new PlatformConfiguration());


        $creator = $this->getMockBuilder(FromJiraTrackerCreator::class)
            ->setConstructorArgs([
                $this->tracker_xml_import,
                $this->tracker_factory,
                $this->creation_data_checker,
                $this->logger,
                $this->jira_user_on_tuleap_cache,
                $this->platform_configuration_retriever,
                UserRolesCheckerInterfaceStub::build(),
            ])
            ->onlyMethods(['getJiraExporter'])
            ->getMock();

        $jira_exporter = $this->createMock(JiraIssuesFromIssueTypeInDedicatedTrackerInXmlExporter::class);
        $creator->method('getJiraExporter')->willReturn($jira_exporter);

        $jira_exporter->expects($this->once())->method('exportIssuesToXml')->willReturn(new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><foo/>'));
        $this->tracker_xml_import->expects($this->once())->method('import')->willReturn([1]);
        $this->tracker_factory->method('getTrackerById')->with(1)->willReturn(TrackerTestBuilder::aTracker()->build());

        $creator->createFromJira(
            $project,
            'my new tracker',
            'my_tracker',
            'tracker desc',
            'inca-silver',
            new JiraCredentials('https://example.com', 'user@example.com', new ConcealedString('azerty123')),
            $jira_client,
            'Jira project',
            'Story',
            UserTestBuilder::aUser()->build(),
        );
        self::assertTrue($this->logger->hasInfoRecords());
    }

    public function testItDoesNotDuplicateATrackerFromJiraIfUserIsNotJiraAdmin(): void
    {
        $jira_client = $this->createMock(JiraClient::class);
        $jira_client->expects($this->once())->method('getUrl')->willReturn(['id' => '10005', 'name' => 'Story', 'subtask' => false]);

        $this->creation_data_checker->expects($this->once())->method('checkAtProjectCreation');

        $this->platform_configuration_retriever->expects($this->once())->method('getJiraPlatformConfiguration')
            ->with($jira_client, $this->logger)
            ->willReturn(new PlatformConfiguration());

        $creator = $this->getMockBuilder(FromJiraTrackerCreator::class)
            ->setConstructorArgs([
                $this->tracker_xml_import,
                $this->tracker_factory,
                $this->creation_data_checker,
                $this->logger,
                $this->jira_user_on_tuleap_cache,
                $this->platform_configuration_retriever,
                UserRolesCheckerInterfaceStub::withException(new UserIsNotProjectAdminException()),
            ])
            ->onlyMethods(['getJiraExporter'])
            ->getMock();

        $creator->expects(self::never())->method('getJiraExporter');
        $this->tracker_xml_import->expects(self::never())->method('import');
        $this->tracker_factory->expects(self::never())->method('getTrackerById');

        $this->expectException(TrackerCreationHasFailedException::class);

        $creator->createFromJira(
            ProjectTestBuilder::aProject()->build(),
            'my new tracker',
            'my_tracker',
            'tracker desc',
            'inca-silver',
            new JiraCredentials('https://example.com', 'user@example.com', new ConcealedString('azerty123')),
            $jira_client,
            'Jira project',
            'Story',
            UserTestBuilder::aUser()->build(),
        );
        self::assertTrue($this->logger->hasInfoRecords());
    }
}
