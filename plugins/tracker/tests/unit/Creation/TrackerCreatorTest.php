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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDuplicator;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerCreatorTest extends TestCase
{
    private TrackerCreationDataChecker&MockObject $creation_data_checker;
    private TrackerCreator $creator;
    private TrackerFactory&MockObject $tracker_factory;
    private TrackerXmlImport&MockObject $tracker_xml_import;
    private SemanticTimeframeDuplicator&MockObject $semantic_timeframe_duplicator;

    protected function setUp(): void
    {
        $this->tracker_xml_import            = $this->createMock(TrackerXmlImport::class);
        $this->tracker_factory               = $this->createMock(TrackerFactory::class);
        $this->creation_data_checker         = $this->createMock(TrackerCreationDataChecker::class);
        $this->semantic_timeframe_duplicator = $this->createMock(SemanticTimeframeDuplicator::class);

        $this->creator = new TrackerCreator(
            $this->tracker_xml_import,
            $this->tracker_factory,
            $this->createStub(TrackerCreatorXmlErrorDisplayer::class),
            $this->creation_data_checker,
            $this->semantic_timeframe_duplicator
        );
    }

    public function testItCreateTrackerFromXml(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $tracker = TrackerTestBuilder::aTracker()->build();
        $this->tracker_xml_import->expects($this->once())->method('createFromXMLFileWithInfo')->with(
            $project,
            '/var/tmp/tracker_import',
            'Tracker Name',
            '',
            'tracker-shortname',
            'fiesta-red',
        )->willReturn($tracker);

        $created_tracker = $this->creator->createTrackerFromXml(
            $project,
            '/var/tmp/tracker_import',
            'Tracker Name',
            '',
            'tracker-shortname',
            'fiesta-red'
        );

        self::assertEquals($tracker, $created_tracker);
    }

    public function testItDuplicateExistingTracker(): void
    {
        $from_project = ProjectTestBuilder::aProject()->withId(110)->build();
        $to_project   = ProjectTestBuilder::aProject()->withId(110)->build();

        $from_tracker = TrackerTestBuilder::aTracker()->withProject($from_project)->withId(101)->build();
        $to_tracker   = TrackerTestBuilder::aTracker()->withId(201)->build();

        $this->tracker_factory->expects($this->once())->method('create')->with(
            $to_project->getId(),
            self::isInstanceOf(MappingRegistry::class),
            '101',
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
        )->willReturn(['tracker' => $to_tracker, 'field_mapping' => ['F101' => 1001, 'F102' => 1002], 'report_mapping' => []]);
        $this->creation_data_checker->expects($this->once())->method('checkAtTrackerDuplication');

        $this->tracker_factory->expects($this->once())->method('getTrackerById')->with(101)->willReturn($from_tracker);

        $this->semantic_timeframe_duplicator->expects($this->once())->method('duplicateInSameProject')
            ->with(101, 201, ['F101' => 1001, 'F102' => 1002]);

        $created_tracker = $this->creator->duplicateTracker(
            $to_project,
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
            '101',
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertEquals($to_tracker, $created_tracker);
    }

    public function testItThrowExceptionWhenTrackerDuplicationFails(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(110)->build();
        $this->creation_data_checker->expects($this->once())->method('checkAtTrackerDuplication');
        $this->tracker_factory->expects($this->once())->method('create')->with(
            $project->getId(),
            self::isInstanceOf(MappingRegistry::class),
            '101',
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
        )->willReturn(false);

        $this->semantic_timeframe_duplicator->expects($this->never())->method('duplicateInSameProject');

        $this->expectException(TrackerCreationHasFailedException::class);
        $this->creator->duplicateTracker(
            $project,
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
            '101',
            UserTestBuilder::buildWithDefaults(),
        );
    }

    public function testItAsksToDuplicateSemanticTimeframeWhenTemplateTrackerComesFromAnotherProjectAndIsBasedOnFields(): void
    {
        $from_project = ProjectTestBuilder::aProject()->withId(110)->build();
        $to_project   = ProjectTestBuilder::aProject()->withId(111)->build();

        $from_tracker = TrackerTestBuilder::aTracker()->withId(101)->withProject($from_project)->build();
        $to_tracker   = TrackerTestBuilder::aTracker()->withId(201)->build();

        $this->tracker_factory->expects($this->once())->method('create')->with(
            $to_project->getId(),
            self::isInstanceOf(MappingRegistry::class),
            '101',
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
        )->willReturn(['tracker' => $to_tracker, 'field_mapping' => ['F101' => 1001, 'F102' => 1002], 'report_mapping' => []]);
        $this->creation_data_checker->expects($this->once())->method('checkAtTrackerDuplication');

        $this->tracker_factory->method('getTrackerById')->willReturnCallback(static fn(int $id) => match ($id) {
            101 => $from_tracker,
            201 => $to_tracker,
        });

        $this->semantic_timeframe_duplicator->expects($this->never())->method('duplicateInSameProject');
        $this->semantic_timeframe_duplicator->expects($this->once())->method('duplicateBasedOnFieldConfiguration');

        $this->creator->duplicateTracker(
            $to_project,
            'Tracker Name',
            '',
            'tracker-shortname',
            'peggy-pink',
            '101',
            UserTestBuilder::buildWithDefaults(),
        );
    }
}
