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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Data\SynchronizedFields;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Adapter\Program\SynchronizedFieldsAdapter;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SynchronizedFieldCollectionBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|SynchronizedFieldsAdapter
     */
    private $fields_adapter;

    /**
     * @var SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder
     */
    private $collection;

    protected function setUp(): void
    {
        $this->fields_adapter = \Mockery::mock(SynchronizedFieldsAdapter::class);
        $this->collection     = new SynchronizedFieldDataFromProgramAndTeamTrackersCollectionBuilder(
            $this->fields_adapter
        );
    }

    public function testBuildFromMilestoneTrackersReturnsACollection(): void
    {
        $first_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(102)->build());
        $second_tracker = TrackerDataAdapter::build(TrackerTestBuilder::aTracker()->withId(104)->build());
        $milestones = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $first_synchronized_fields = $this->buildSynchronizedFieldsWithIds(1, 2, 3, 4, 5, 6);
        $this->fields_adapter->shouldReceive('build')->with($first_tracker)->andReturn($first_synchronized_fields);
        $second_synchronized_fields = $this->buildSynchronizedFieldsWithIds(1001, 1002, 1003, 1004, 1005, 1006);
        $this->fields_adapter->shouldReceive('build')->with($second_tracker)->andReturn($second_synchronized_fields);

        $expected_ids = [
            1,
            2,
            3,
            4,
            5,
            6,
            1001,
            1002,
            1003,
            1004,
            1005,
            1006
        ];
        $collection   = $this->collection->buildFromSourceTrackers(
            $milestones
        );
        $this->assertEquals($expected_ids, $collection->getSynchronizedFieldIDs());
    }

    private function buildSynchronizedFieldsWithIds(
        int $artlink_id,
        int $title_id,
        int $description_id,
        int $status_id,
        int $start_date_id,
        int $end_period_id
    ): SynchronizedFields {
        $artifact_link_field_data = new FieldData(
            new \Tracker_FormElement_Field_ArtifactLink(
                $artlink_id,
                89,
                1000,
                'art_link',
                'Links',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                1
            )
        );

        $title_field_data = new FieldData(
            new \Tracker_FormElement_Field_String(
                $title_id,
                89,
                1000,
                'title',
                'Title',
                'Irrelevant',
                true,
                'P',
                true,
                '',
                2
            )
        );

        $description_field_data = new FieldData(
            new \Tracker_FormElement_Field_Text(
                $description_id,
                89,
                1000,
                'description',
                'Description',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                3
            )
        );

        $status_field_data = new FieldData(
            new \Tracker_FormElement_Field_Selectbox(
                $status_id,
                89,
                1000,
                'status',
                'Status',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                4
            )
        );

        $start_date_field_data = new FieldData(
            new \Tracker_FormElement_Field_Date(
                $start_date_id,
                89,
                1000,
                'date',
                'Date',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                5
            )
        );

        $end_date_field_data = new FieldData(
            new \Tracker_FormElement_Field_Date(
                $end_period_id,
                89,
                1000,
                'date',
                'Date',
                'Irrelevant',
                true,
                'P',
                false,
                '',
                6
            )
        );

        return new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );
    }
}
