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

final class StartDateValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $start_date;

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
            'start_date',
            'start_date',
            '',
            true,
            null,
            true,
            true,
            1
        );

        $duration_field = \Mockery::mock(\Tracker_FormElement_Field_Integer::class);

        $this->synchronized_fields = new SynchronizedFields(
            \Mockery::mock(\Tracker_FormElement_Field_ArtifactLink::class),
            \Mockery::mock(\Tracker_FormElement_Field_String::class),
            \Mockery::mock(\Tracker_FormElement_Field_Text::class),
            \Mockery::mock(\Tracker_FormElement_Field_Selectbox::class),
            TimeframeFields::fromStartDateAndDuration($this->start_date, $duration_field)
        );
    }

    public function testItThrowsWhenStartDateValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->start_date)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new StartDateValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->synchronized_fields, $source_changeset);
    }

    public function testItBuildStartDateValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn("2020-10-01");
        $source_changeset->shouldReceive('getValue')->with($this->start_date)->andReturn($changset_value);

        $adapter = new StartDateValueAdapter();

        $expected_data = new StartDateValueData("2020-10-01");

        $data = $adapter->build($this->synchronized_fields, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }
}
