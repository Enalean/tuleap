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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class StartDateValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Artifact
     */
    private $artifact_data;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var \Tracker_FormElement_Field_Date
     */
    private $field_start_date;

    /**
     * @var FieldData
     */
    private $start_date;

    protected function setUp(): void
    {
        $this->field_start_date = new \Tracker_FormElement_Field_Date(
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
        $this->start_date = new FieldData($this->field_start_date);

        $this->user          = UserTestBuilder::aUser()->withId(101)->build();
        $submitted_on        = 123456789;
        $project             = new \Project(
            ['group_id' => '101', 'unix_group_name' => "project", 'group_name' => 'My project']
        );
        $tracker             = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $this->artifact_data = new Artifact(1, $tracker->getId(), $this->user->getId(), $submitted_on, true);
        $this->artifact_data->setTracker($tracker);
    }

    public function testItThrowsWhenStartDateValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->field_start_date)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $adapter = new StartDateValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $replication_data = ReplicationDataAdapter::build($this->artifact_data, $this->user, $source_changeset);
        $adapter->build($this->start_date, $replication_data);
    }

    public function testItBuildStartDateValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_Date::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn("2020-10-01");
        $source_changeset->shouldReceive('getValue')->with($this->field_start_date)->andReturn($changset_value);

        $adapter = new StartDateValueAdapter();

        $expected_data = new StartDateValue("2020-10-01");

        $replication_data = ReplicationDataAdapter::build($this->artifact_data, $this->user, $source_changeset);
        $data = $adapter->build($this->start_date, $replication_data);

        $this->assertEquals($expected_data, $data);
    }
}
