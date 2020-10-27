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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\TimeframeFields;

final class EndPeriodValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_Date
     */
    private $end_date_field;

    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $start_date;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_Integer
     */
    private $duration_field;

    /**
     * @var SynchronizedFields
     */
    private $synchronized_fields;

    protected function setUp(): void
    {
        $this->start_date = new \Tracker_FormElement_Field_Date(
            1,
            10,
            null,
            'duration',
            'duration',
            '',
            true,
            null,
            true,
            true,
            1
        );

        $this->duration_field = new \Tracker_FormElement_Field_Integer(
            10,
            10,
            null,
            'duration',
            'duration',
            '',
            true,
            null,
            true,
            true,
            1
        );
        $this->end_date_field = new \Tracker_FormElement_Field_Date(
            20,
            10,
            null,
            'end period',
            'end period',
            '',
            true,
            null,
            true,
            true,
            1
        );
    }

    public function testItThrowsWhenDurationValueIsNotFound(): void
    {
        $this->synchronized_fields = new SynchronizedFields(
            \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class),
            \Mockery::mock(\Tracker_FormElement_Field_String::class),
            \Mockery::mock(\Tracker_FormElement_Field_Text::class),
            \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class),
            TimeframeFields::fromStartDateAndDuration($this->start_date, $this->duration_field)
        );

        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->duration_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new EndPeriodValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->synchronized_fields, $source_changeset);
    }

    public function testItBuildDurationValue(): void
    {
        $this->synchronized_fields = new SynchronizedFields(
            \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class),
            \Mockery::mock(\Tracker_FormElement_Field_String::class),
            \Mockery::mock(\Tracker_FormElement_Field_Text::class),
            \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class),
            TimeframeFields::fromStartDateAndDuration($this->start_date, $this->duration_field)
        );

        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn(12);
        $source_changeset->shouldReceive('getValue')->with($this->duration_field)->andReturn($changset_value);

        $adapter = new EndPeriodValueAdapter();

        $expected_data = new EndPeriodValueData("12");

        $data = $adapter->build($this->synchronized_fields, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }

    public function testItThrowsWhenEndDateValueIsNotFound(): void
    {
        $this->synchronized_fields = new SynchronizedFields(
            \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class),
            \Mockery::mock(\Tracker_FormElement_Field_String::class),
            \Mockery::mock(\Tracker_FormElement_Field_Text::class),
            \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class),
            TimeframeFields::fromStartAndEndDates($this->start_date, $this->end_date_field)
        );

        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->end_date_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new EndPeriodValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->synchronized_fields, $source_changeset);
    }

    public function testItBuildEndDateValue(): void
    {
        $this->synchronized_fields = new SynchronizedFields(
            \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class),
            \Mockery::mock(\Tracker_FormElement_Field_String::class),
            \Mockery::mock(\Tracker_FormElement_Field_Text::class),
            \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class),
            TimeframeFields::fromStartAndEndDates($this->start_date, $this->end_date_field)
        );

        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn("2020-10-31");
        $source_changeset->shouldReceive('getValue')->with($this->end_date_field)->andReturn($changset_value);

        $adapter = new EndPeriodValueAdapter();

        $expected_data = new EndPeriodValueData("2020-10-31");

        $data = $adapter->build($this->synchronized_fields, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }
}
