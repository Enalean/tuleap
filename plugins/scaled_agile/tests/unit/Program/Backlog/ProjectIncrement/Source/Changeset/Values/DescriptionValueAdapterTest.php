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

final class DescriptionValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Tracker_FormElement_Field_Text
     */
    private $description_field;

    /**
     * @var \Tracker_FormElement_Field_String
     */
    private $field_description_data;

    protected function setUp(): void
    {
        $this->description_field      = new \Tracker_FormElement_Field_Text(
            1,
            10,
            null,
            'description',
            'description',
            '',
            true,
            null,
            true,
            true,
            1
        );
        $this->field_description_data = new FieldData($this->description_field);
    }

    public function testItThrowsWhenDescriptionValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->description_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new DescriptionValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->field_description_data, $source_changeset);
    }

    public function testItBuildDescriptionValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_String::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn("My description");
        $changset_value->shouldReceive('getFormat')->once()->andReturn("text");
        $source_changeset->shouldReceive('getValue')->with($this->description_field)->andReturn($changset_value);

        $adapter = new DescriptionValueAdapter();

        $expected_data = new DescriptionValueData("My description", "text");

        $data = $adapter->build($this->field_description_data, $source_changeset);

        $this->assertEquals($expected_data, $data);
    }
}
