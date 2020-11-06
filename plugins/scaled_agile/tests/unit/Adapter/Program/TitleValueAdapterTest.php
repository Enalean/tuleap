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
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\UnsupportedTitleFieldException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TitleValueAdapterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \PFUser
     */
    private $user;

    /**
     * @var Artifact
     */
    private $artifact_data;

    /**
     * @var \Tracker_FormElement_Field_String
     */
    private $title_field;

    /**
     * @var \Tracker_FormElement_Field_String
     */
    private $field_title_data;

    protected function setUp(): void
    {
        $this->title_field      = new \Tracker_FormElement_Field_String(
            1,
            10,
            null,
            'title',
            'title',
            '',
            true,
            null,
            true,
            true,
            1
        );
        $this->field_title_data = new FieldData($this->title_field);

        $this->user          = UserTestBuilder::aUser()->withId(101)->build();
        $submitted_on        = 123456789;
        $project             = new \Project(
            ['group_id' => '101', 'unix_group_name' => "project", 'group_name' => 'My project']
        );
        $tracker             = TrackerTestBuilder::aTracker()->withId(1)->withProject($project)->build();
        $this->artifact_data = new Artifact(1, $tracker->getId(), $this->user->getId(), $submitted_on, true);
        $this->artifact_data->setTracker($tracker);
    }

    public function testItThrowsWhenTitleValueIsNotFound(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $source_changeset->shouldReceive('getValue')->with($this->title_field)->andReturnNull();
        $source_changeset->shouldReceive('getId')->andReturn(1);

        $replication_data = ReplicationDataAdapter::build($this->artifact_data, $this->user, $source_changeset);

        $adapter = new TitleValueAdapter();

        $this->expectException(ChangesetValueNotFoundException::class);

        $adapter->build($this->field_title_data, $replication_data);
    }

    public function testItThrowsWhenTitleIsNotAString(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $source_changeset->shouldReceive('getValue')->with($this->title_field)->andReturn($changset_value);

        $replication_data = ReplicationDataAdapter::build($this->artifact_data, $this->user, $source_changeset);

        $adapter = new TitleValueAdapter();

        $this->expectException(UnsupportedTitleFieldException::class);

        $adapter->build($this->field_title_data, $replication_data);
    }

    public function testItBuildTitleValue(): void
    {
        $source_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changset_value = \Mockery::mock(\Tracker_Artifact_ChangesetValue_String::class);
        $changset_value->shouldReceive('getValue')->once()->andReturn("My title");
        $source_changeset->shouldReceive('getValue')->with($this->title_field)->andReturn($changset_value);

        $replication_data = ReplicationDataAdapter::build($this->artifact_data, $this->user, $source_changeset);

        $adapter = new TitleValueAdapter();

        $expected_data = new TitleValue("My title");

        $data = $adapter->build($this->field_title_data, $replication_data);

        $this->assertEquals($expected_data, $data);
    }
}
