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

use Mockery;
use Project;
use Psr\Log\LoggerInterface;
use Tracker;
use TrackerXmlImport;

class TestmanagementTrackersCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerXmlImport
     */
    private $xml_import;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var TestmanagementTrackersCreator
     */
    private $tracker_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker
     */
    private $created_tracker;

    protected function setup(): void
    {
        parent::setUp();

        $this->project         = Mockery::mock(Project::class);
        $this->created_tracker = Mockery::mock(Tracker::class);

        $this->xml_import      = Mockery::mock(TrackerXmlImport::class);
        $this->logger          = Mockery::mock(LoggerInterface::class);
        $this->tracker_creator = new TestmanagementTrackersCreator($this->xml_import, $this->logger);
    }

    public function testCreateFromXml(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../../../resources/templates/Tracker_campaign.xml');

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->withArgs([$this->project, $expected_path])
            ->andReturn($this->created_tracker);

        $result = $this->tracker_creator->createTrackerFromXML($this->project, 'campaign');
        $this->assertEquals($this->created_tracker, $result);
    }

    public function testCreateIssueTrackerFromXml(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../../../../tracker/resources/templates/Tracker_Bugs.xml');

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->withArgs([$this->project, $expected_path])
            ->andReturn($this->created_tracker);

        $result = $this->tracker_creator->createTrackerFromXML($this->project, 'bug');

        $this->assertEquals($this->created_tracker, $result);
    }

    public function testCreateTrackerFromXmlFail(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../broken_path.xml');
        $this->project->shouldReceive('getId')->andReturn(111);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->withArgs([$this->project, $expected_path]);

        $this->logger->shouldReceive('error')->once();
        $this->expectException(TrackerNotCreatedException::class);

        $this->tracker_creator->createTrackerFromXML($this->project, 'campaign');
    }

    // Bug tracker shouldn't raise exception because it's not mandatory for Testmanagement administration
    public function testCreateTrackerFromXmlDoesntStopIfBugTrackerCreationFail(): void
    {
        $expected_path = (string) realpath(__DIR__ . '/../broken_path.xml');
        $this->project->shouldReceive('getId')->andReturn(111);

        $this->xml_import->shouldReceive('createFromXMLFile')
            ->withArgs([$this->project, $expected_path]);

        $this->logger->shouldReceive('error')->once();

        $this->assertNull($this->tracker_creator->createTrackerFromXML($this->project, 'bug'));
    }
}
