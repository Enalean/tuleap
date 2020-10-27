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
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields\FieldData;

final class EndPeriodValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $end_date_field;

    /**
     * @var \Tracker_FormElement_Field_Integer
     */
    private $duration_field;

    /**
     * @var FieldEndDurationData
     */
    private $end_date_field_data;

    /**
     * @var FieldEndDateData
     */
    private $duration_field_data;

    protected function setUp(): void
    {
        $this->duration_field      = new \Tracker_FormElement_Field_Integer(
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
        $this->duration_field_data = new FieldData($this->duration_field);
        $this->end_date_field      = new \Tracker_FormElement_Field_Date(
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
        $this->end_date_field_data = new FieldData($this->end_date_field);
    }

    public function testItThrowsWhenDurationValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->duration_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new EndPeriodValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->duration_field_data, $source_changeset);
    }

    public function testItBuildDurationValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Integer::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn(12);
        $source_changeset->shouldReceive('getValue')->with($this->duration_field)->andReturn($changset_value);

        $adapter = new EndPeriodValueAdapter();

        $expected_data = new EndPeriodValueData("12");

        $data = $adapter->build($this->duration_field_data, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }

    public function testItThrowsWhenEndDateValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->end_date_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new EndPeriodValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->end_date_field_data, $source_changeset);
    }

    public function testItBuildEndDateValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn("2020-10-31");
        $source_changeset->shouldReceive('getValue')->with($this->end_date_field)->andReturn($changset_value);

        $adapter = new EndPeriodValueAdapter();

        $expected_data = new EndPeriodValueData("2020-10-31");

        $data = $adapter->build($this->end_date_field_data, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }
}
