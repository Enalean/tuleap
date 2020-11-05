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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackers;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldDataFromProgramAndTeamTrackersCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;

final class RequiredFieldCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RequiredFieldChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = new RequiredFieldChecker(new NullLogger());
    }

    public function testAllowsCreationWhenOnlySynchronizedFieldsAreRequired(): void
    {
        $required_title = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $required_title->shouldReceive('isRequired')->andReturn(true);
        $required_title->shouldReceive('getId')->andReturn('789');
        $non_required_artifact_link = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $non_required_artifact_link->shouldReceive('isRequired')->andReturn(false);
        $non_required_artifact_link->shouldReceive('getId')->andReturn('987');

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getFormElementFields')->andReturn([$required_title, $non_required_artifact_link]);
        $tracker->shouldReceive('getGroupId')->andReturn('147');

        $other_tracker_with_no_required_field = \Mockery::mock(\Tracker::class);
        $other_non_required_field             = \Mockery::mock(\Tracker_FormElement_Field_Date::class);
        $other_non_required_field->shouldReceive('isRequired')->andReturn(false);
        $other_tracker_with_no_required_field->shouldReceive('getFormElementFields')->andReturn(
            [$other_non_required_field]
        );
        $other_tracker_with_no_required_field->shouldReceive('getGroupId')->andReturn('148');

        $synchronized_field = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(
            $required_title,
            $non_required_artifact_link
        );
        $collection         = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($synchronized_field);
        $no_other_required_fields = $this->checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            new ProgramIncrementsTrackerCollection(
                [TrackerDataAdapter::build($tracker), TrackerDataAdapter::build($other_tracker_with_no_required_field)]
            ),
            $collection
        );
        $this->assertTrue($no_other_required_fields);
    }

    public function testDisallowsCreationWhenAnyFieldIsRequiredAndNotSynchronized(): void
    {
        $required_title = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $required_title->shouldReceive('isRequired')->andReturn(true);
        $required_title->shouldReceive('getId')->andReturn('789');
        $required_artifact_link = \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $required_artifact_link->shouldReceive('isRequired')->andReturn(true);
        $required_artifact_link->shouldReceive('getId')->andReturn('789');

        $other_required_field = \Mockery::mock(\Tracker_FormElement_Field_String::class);
        $other_required_field->shouldReceive('isRequired')->andReturn(true);
        $other_required_field->shouldReceive('getId')->andReturn('987');
        $other_required_field->shouldReceive('getLabel')->andReturn('some_label');

        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn('412');
        $tracker->shouldReceive('getFormElementFields')->andReturn([$required_title, $required_artifact_link, $other_required_field]);
        $tracker->shouldReceive('getGroupId')->andReturn('147');

        $synchronized_field = $this->buildSynchronizedFieldDataFromProgramAndTeamTrackers(
            $required_title,
            $required_artifact_link
        );
        $collection         = new SynchronizedFieldDataFromProgramAndTeamTrackersCollection();
        $collection->add($synchronized_field);

        $no_other_required_fields = $this->checker->areRequiredFieldsOfTeamTrackersLimitedToTheSynchronizedFields(
            new ProgramIncrementsTrackerCollection([TrackerDataAdapter::build($tracker)]),
            $collection
        );
        $this->assertFalse($no_other_required_fields);
    }

    private function buildSynchronizedFieldDataFromProgramAndTeamTrackers(
        \Tracker_FormElement_Field_Text $title_field,
        \Tracker_FormElement_Field_ArtifactLink $artifact_link_field
    ): SynchronizedFieldDataFromProgramAndTeamTrackers {
        $artifact_link_field_data = new FieldData($artifact_link_field);

        $title_field_data = new FieldData($title_field);

        $description_field_data = new FieldData(
            new \Tracker_FormElement_Field_Text(
                3,
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
                4,
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
            new \Tracker_FormElement_Field_Date(5, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 5)
        );

        $end_date_field_data = new FieldData(
            new \Tracker_FormElement_Field_Date(6, 89, 1000, 'date', 'Date', 'Irrelevant', true, 'P', false, '', 6)
        );

        $synchronized_fields = new SynchronizedFields(
            $artifact_link_field_data,
            $title_field_data,
            $description_field_data,
            $status_field_data,
            $start_date_field_data,
            $end_date_field_data
        );

        return new SynchronizedFieldDataFromProgramAndTeamTrackers($synchronized_fields);
    }
}
