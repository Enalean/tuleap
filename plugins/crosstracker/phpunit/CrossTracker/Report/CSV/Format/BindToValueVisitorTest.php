<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV\Format;

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;

class BindToValueVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var BindToValueVisitor */
    private $visitor;
    /** @var Mockery\MockInterface | \Tracker_FormElement_Field_List */
    private $list_field;
    /** @var Mockery\MockInterface | BindParameters */
    private $parameters;
    /** @var Mockery\MockInterface | \Tracker_Artifact_ChangesetValue_List */
    private $changeset_value;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visitor         = new BindToValueVisitor();
        $this->list_field      = Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->parameters      = Mockery::mock(BindToValueParameters::class);
        $this->changeset_value = Mockery::mock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->parameters->shouldReceive('getChangesetValue')->andReturn($this->changeset_value);
    }

    public function testVisitListBindStatic()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(['212']);

        $static_bind_value = Mockery::mock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $static_bind_value->shouldReceive('getLabel')->andReturn('piceworth');

        $static_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind->shouldReceive('accept')->passthru();
        $static_bind->shouldReceive('getValue')->with('212')->andReturn($static_bind_value);

        $result = $static_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new TextValue('piceworth'), $result);
    }

    public function testItReturnsEmptyValueWhenListBindStaticHasNoValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn([]);

        $static_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind->shouldReceive('accept')->passthru();

        $result = $static_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindStaticHasNoneValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(
            [
                (string) \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
            ]
        );

        $static_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind->shouldReceive('accept')->passthru();

        $result = $static_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindStaticHasInvalidValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(['356']);

        $static_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Static::class);
        $static_bind->shouldReceive('accept')->passthru();
        $static_bind->shouldReceive('getValue')->andThrow(new \Tracker_FormElement_InvalidFieldValueException());

        $result = $static_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testVisitListBindUsers()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(['326']);

        $user = Mockery::mock(\PFUser::class);
        $users_bind_value = Mockery::mock(\Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $users_bind_value->shouldReceive('getUser')->andReturn($user);

        $users_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Users::class);
        $users_bind->shouldReceive('accept')->passthru();
        $users_bind->shouldReceive('getValue')->with('326')->andReturn($users_bind_value);

        $result = $users_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new UserValue($user), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUsersHasNoValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn([]);

        $users_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Users::class);
        $users_bind->shouldReceive('accept')->passthru();

        $result = $users_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUsersHasNoneValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(
            [
                (string) \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
            ]
        );

        $users_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Users::class);
        $users_bind->shouldReceive('accept')->passthru();

        $result = $users_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUsersHasInvalidValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(['99']);

        $users_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Users::class);
        $users_bind->shouldReceive('accept')->passthru();
        $users_bind->shouldReceive('getValue')->with('99')->andReturn(null);

        $result = $users_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testVisitListBindUgroups()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(['979']);

        $ugroups_bind_value = Mockery::mock(\Tracker_FormElement_Field_List_Bind_UgroupsValue::class);
        $ugroups_bind_value->shouldReceive('getLabel')->andReturn('hospitious');

        $ugroups_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Ugroups::class);
        $ugroups_bind->shouldReceive('accept')->passthru();
        $ugroups_bind->shouldReceive('getValue')->with('979')->andReturn($ugroups_bind_value);

        $result = $ugroups_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new TextValue('hospitious'), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUgroupsHasNoValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn([]);

        $ugroups_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Ugroups::class);
        $ugroups_bind->shouldReceive('accept')->passthru();

        $result = $ugroups_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUgroupsHasNoneValue()
    {
        $this->changeset_value->shouldReceive('getValue')->andReturn(
            [
                (string) \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID
            ]
        );

        $ugroups_bind = Mockery::mock(\Tracker_FormElement_Field_List_Bind_Ugroups::class);
        $ugroups_bind->shouldReceive('accept')->passthru();

        $result = $ugroups_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }

    public function testVisitListBindNull()
    {
        $null_bind = new \Tracker_FormElement_Field_List_Bind_Null($this->list_field);

        $result = $null_bind->accept($this->visitor, $this->parameters);

        $this->assertEquals(new EmptyValue(), $result);
    }
}
