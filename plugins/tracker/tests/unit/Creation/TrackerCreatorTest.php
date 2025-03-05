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
use Tuleap\Project\MappingRegistry;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDuplicator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
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
    private SemanticTimeframeDuplicator $semantic_timeframe_duplicator;

    protected function setUp(): void
    {
        $this->tracker_xml_import            = Mockery::mock(TrackerXmlImport::class);
        $this->tracker_factory               = Mockery::mock(TrackerFactory::class);
        $this->xml_error_displayer           = Mockery::mock(TrackerCreatorXmlErrorDisplayer::class);
        $this->creation_data_checker         = Mockery::mock(TrackerCreationDataChecker::class);
        $this->semantic_timeframe_duplicator = Mockery::mock(SemanticTimeframeDuplicator::class);

        $this->creator = new TrackerCreator(
            $this->tracker_xml_import,
            $this->tracker_factory,
            $this->xml_error_displayer,
            $this->creation_data_checker,
            $this->semantic_timeframe_duplicator
        );
    }

    public function testItCreateTrackerFromXml(): void
    {
        $project = Mockery::mock(\Project::class);
        $tracker = Mockery::mock(Tracker::class);
        $this->tracker_xml_import->shouldReceive('createFromXMLFileWithInfo')->withArgs(
            [
                $project,
                '/var/tmp/tracker_import',
                'Tracker Name',
                '',
                'tracker-shortname',
                'fiesta-red',
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
        $from_project = Mockery::mock(\Project::class);
        $from_project->shouldReceive('getID')->andReturn('110');

        $to_project = Mockery::mock(\Project::class);
        $to_project->shouldReceive('getID')->andReturn('110');

        $from_tracker = Mockery::mock(Tracker::class);
        $from_tracker->shouldReceive('getProject')
            ->once()
            ->andReturn($from_project);
        $from_tracker->shouldReceive('getId')->andReturn(101);

        $to_tracker = Mockery::mock(Tracker::class);
        $to_tracker->shouldReceive('getId')->andReturn(201);

        $this->tracker_factory->shouldReceive('create')->withArgs(
            [
                $to_project->getId(),
                Mockery::type(MappingRegistry::class),
                '101',
                'Tracker Name',
                '',
                'tracker-shortname',
                'peggy-pink',
            ]
        )->once()->andReturn(['tracker' => $to_tracker, 'field_mapping' => ['F101' => 1001, 'F102' => 1002], 'report_mapping' => []]);
        $this->creation_data_checker->shouldReceive('checkAtTrackerDuplication')->once();

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->once()
            ->with(101)
            ->andReturn($from_tracker);

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateInSameProject')
            ->once()
            ->with(101, 201, ['F101' => 1001, 'F102' => 1002]);

        $created_tracker = $this->creator->duplicateTracker(
            $to_project,
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
            '101',
            Mockery::mock(\PFUser::class)
        );

        $this->assertEquals($to_tracker, $created_tracker);
    }

    public function testItThrowExceptionWhenTrackerDuplicationFails(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn('110');
        $this->creation_data_checker->shouldReceive('checkAtTrackerDuplication')->once();
        $this->tracker_factory->shouldReceive('create')->withArgs(
            [
                $project->getId(),
                Mockery::type(MappingRegistry::class),
                '101',
                'Tracker Name',
                '',
                'tracker-shortname',
                'peggy-pink',
            ]
        )->once()->andReturn(false);

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateInSameProject')
            ->never();

        $this->expectException(TrackerCreationHasFailedException::class);
        $this->creator->duplicateTracker(
            $project,
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
            '101',
            Mockery::mock(\PFUser::class)
        );
    }

    public function testItAsksToDuplicateSemanticTimeframeWhenTemplateTrackerComesFromAnotherProjectAndIsBasedOnFields(): void
    {
        $from_project = Mockery::mock(\Project::class);
        $from_project->shouldReceive('getId')->andReturn('110');

        $to_project = Mockery::mock(\Project::class);
        $to_project->shouldReceive('getID')->andReturn('111');

        $from_tracker = Mockery::mock(Tracker::class);
        $from_tracker->shouldReceive('getProject')
            ->once()
            ->andReturn($from_project);
        $from_tracker->shouldReceive('getId')->andReturn(101);

        $to_tracker = Mockery::mock(Tracker::class);
        $to_tracker->shouldReceive('getId')->andReturn(201);

        $this->tracker_factory->shouldReceive('create')->withArgs(
            [
                $to_project->getId(),
                Mockery::type(MappingRegistry::class),
                '101',
                'Tracker Name',
                '',
                'tracker-shortname',
                'peggy-pink',
            ]
        )->once()->andReturn(['tracker' => $to_tracker, 'field_mapping' => ['F101' => 1001, 'F102' => 1002], 'report_mapping' => []]);
        $this->creation_data_checker->shouldReceive('checkAtTrackerDuplication')->once();

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(101)
            ->andReturn($from_tracker);

        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(201)
            ->andReturn($to_tracker);

        $this->semantic_timeframe_duplicator->shouldReceive('duplicateInSameProject')->never();
        $this->semantic_timeframe_duplicator->shouldReceive('duplicateBasedOnFieldConfiguration')->once();

        $created_tracker = $this->creator->duplicateTracker(
            $to_project,
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
            '101',
            Mockery::mock(\PFUser::class)
        );
    }
}
