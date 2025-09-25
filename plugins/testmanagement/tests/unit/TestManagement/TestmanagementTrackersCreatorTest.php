<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use TrackerXmlImport;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TestmanagementTrackersCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerXmlImport|MockObject $xml_import;
    private TestmanagementTrackersCreator $tracker_creator;
    private Project $project;
    private Tracker $created_tracker;
    private TestLogger $logger;


    #[\Override]
    protected function setup(): void
    {
        parent::setUp();

        $this->project         = ProjectTestBuilder::aProject()->withId(111)->build();
        $this->created_tracker = TrackerTestBuilder::aTracker()->build();

        $this->xml_import      = $this->createMock(TrackerXmlImport::class);
        $this->logger          = new TestLogger();
        $this->tracker_creator = new TestmanagementTrackersCreator($this->xml_import, $this->logger);
    }

    public function testCreateFromXml(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../../../resources/templates/Tracker_campaign.xml');

        $this->xml_import->method('createFromXMLFile')
            ->with($this->project, $expected_path)
            ->willReturn($this->created_tracker);

        $result = $this->tracker_creator->createTrackerFromXML($this->project, 'campaign');
        $this->assertEquals($this->created_tracker, $result);
    }

    public function testCreateIssueTrackerFromXml(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../../../../tracker/resources/templates/Tracker_Bugs.xml');

        $this->xml_import->method('createFromXMLFile')
            ->with($this->project, $expected_path)
            ->willReturn($this->created_tracker);

        $result = $this->tracker_creator->createTrackerFromXML($this->project, 'bug');

        $this->assertEquals($this->created_tracker, $result);

        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testCreateTrackerFromXmlFail(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../broken_path.xml');

        $this->xml_import->method('createFromXMLFile')
            ->with($this->project, $expected_path);

        $this->expectException(TrackerNotCreatedException::class);

        $this->tracker_creator->createTrackerFromXML($this->project, 'campaign');

        self::assertTrue($this->logger->hasErrorRecords());
    }

    // Bug tracker shouldn't raise exception because it's not mandatory for Testmanagement administration
    public function testCreateTrackerFromXmlDoesntStopIfBugTrackerCreationFail(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../broken_path.xml');

        $this->xml_import->method('createFromXMLFile')
            ->with($this->project, $expected_path);

        $this->assertNull($this->tracker_creator->createTrackerFromXML($this->project, 'bug'));

        self::assertTrue($this->logger->hasErrorRecords());
    }
}
