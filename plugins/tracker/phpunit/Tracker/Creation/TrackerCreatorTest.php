<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Creation;

use Mockery;
use Tracker;
use TrackerFactory;
use TrackerXmlImport;

final class TrackerCreatorTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerCreationDataChecker
     */
    private $creation_data_checker;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerCreatorXmlErrorDisplayer
     */
    private $xml_error_displayer;
    /**
     * @var TrackerCreator
     */
    private $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerXmlImport
     */
    private $tracker_xml_import;

    protected function setUp(): void
    {
        $this->tracker_xml_import    = Mockery::mock(TrackerXmlImport::class);
        $this->tracker_factory       = Mockery::mock(TrackerFactory::class);
        $this->xml_error_displayer   = Mockery::mock(TrackerCreatorXmlErrorDisplayer::class);
        $this->creation_data_checker = Mockery::mock(TrackerCreationDataChecker::class);

        $this->creator = new TrackerCreator(
            $this->tracker_xml_import,
            $this->tracker_factory,
            $this->xml_error_displayer,
            $this->creation_data_checker
        );
    }

    public function testItCreateTrackerFromXml(): void
    {
        $project = Mockery::mock(\Project::class);
        $tracker    = Mockery::mock(Tracker::class);
        $this->tracker_xml_import->shouldReceive('createFromXMLFileWithInfo')->withArgs(
            [
                $project,
                '/var/tmp/tracker_import',
                'Tracker Name',
                '',
                'tracker-shortname',
                'fiesta-red'
            ]
        )->once()->andReturn($tracker);

        $created_tracker = $this->creator->createTrackerFromXml(
            $project,
            '/var/tmp/tracker_import',
            'Tracker Name',
            '',
            'tracker-shortname',
            'fiesta-red'
        );

        $this->assertEquals($tracker, $created_tracker);
    }

    public function testItDuplicateExistingTracker(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn("110");

        $tracker = Mockery::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('create')->withArgs(
            [
                $project->getId(),
                -1,
                "101",
                "Tracker Name",
                "",
                "tracker-shortname",
                "peggy-pink"
            ]
        )->once()->andReturn(['tracker' => $tracker]);
        $this->creation_data_checker->shouldReceive('checkAtTrackerDuplication')->once();

        $created_tracker = $this->creator->duplicateTracker(
            $project,
            "Tracker Name",
            "",
            "tracker-shortname",
            "peggy-pink",
            "101",
            Mockery::mock(\PFUser::class)
        );

        $this->assertEquals($tracker, $created_tracker);
    }

    public function testItThrowExceptionWhenTrackerDuplicationFails(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn("110");
        $this->creation_data_checker->shouldReceive('checkAtTrackerDuplication')->once();
        $this->tracker_factory->shouldReceive('create')->withArgs(
            [
                $project->getId(),
                -1,
                "101",
                "Tracker Name",
                "",
                "tracker-shortname",
                "peggy-pink"
            ]
        )->once()->andReturn(false);

        $this->expectException(TrackerCreationHasFailedException::class);
        $this->creator->duplicateTracker(
            $project,
            "Tracker Name",
            "",
            "tracker-shortname",
            "peggy-pink",
            "101",
            Mockery::mock(\PFUser::class)
        );
    }
}
