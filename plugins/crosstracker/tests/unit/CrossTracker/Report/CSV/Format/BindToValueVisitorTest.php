<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;

final class BindToValueVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BindToValueVisitor $visitor;
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_FormElement_Field_List */
    private $list_field;
    /** @var \PHPUnit\Framework\MockObject\MockObject&BindParameters */
    private $parameters;
    /** @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_Artifact_ChangesetValue_List */
    private $changeset_value;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visitor         = new BindToValueVisitor();
        $this->list_field      = $this->createMock(\Tracker_FormElement_Field_List::class);
        $this->parameters      = $this->createMock(BindToValueParameters::class);
        $this->changeset_value = $this->createMock(\Tracker_Artifact_ChangesetValue_List::class);
        $this->parameters->method('getChangesetValue')->willReturn($this->changeset_value);
    }

    public function testVisitListBindStatic(): void
    {
        $this->changeset_value->method('getValue')->willReturn(['212']);

        $static_bind_value = $this->createMock(\Tracker_FormElement_Field_List_Bind_StaticValue::class);
        $static_bind_value->method('getLabel')->willReturn('piceworth');

        $static_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Static::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();

        $static_bind->method('getValue')->with('212')->willReturn($static_bind_value);

        $result = $static_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new TextValue('piceworth'), $result);
    }

    public function testItReturnsEmptyValueWhenListBindStaticHasNoValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn([]);

        $static_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Static::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();

        $result = $static_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindStaticHasNoneValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn(
            [
                (string) \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID,
            ]
        );

        $static_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Static::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $static_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindStaticHasInvalidValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn(['356']);

        $static_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Static::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();

        $static_bind->method('getValue')->willThrowException(new \Tracker_FormElement_InvalidFieldValueException());

        $result = $static_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testVisitListBindUsers(): void
    {
        $this->changeset_value->method('getValue')->willReturn(['326']);

        $user             = UserTestBuilder::aUser()->build();
        $users_bind_value = $this->createMock(\Tracker_FormElement_Field_List_Bind_UsersValue::class);
        $users_bind_value->method('getUser')->willReturn($user);

        $users_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Users::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();

        $users_bind->method('getValue')->with('326')->willReturn($users_bind_value);

        $result = $users_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new UserValue($user), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUsersHasNoValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn([]);

        $users_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Users::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $users_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUsersHasNoneValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn(
            [
                (string) \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID,
            ]
        );

        $users_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Users::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $users_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUsersHasInvalidValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn(['99']);

        $users_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Users::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();

        $users_bind->method('getValue')->with('99')->willReturn(null);

        $result = $users_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testVisitListBindUgroups(): void
    {
        $this->changeset_value->method('getValue')->willReturn(['979']);

        $ugroups_bind_value = $this->createMock(\Tracker_FormElement_Field_List_Bind_UgroupsValue::class);
        $ugroups_bind_value->method('getLabel')->willReturn('hospitious');

        $ugroups_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Ugroups::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();

        $ugroups_bind->method('getValue')->with('979')->willReturn($ugroups_bind_value);

        $result = $ugroups_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new TextValue('hospitious'), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUgroupsHasNoValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn([]);

        $ugroups_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Ugroups::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $ugroups_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testItReturnsEmptyValueWhenListBindUgroupsHasNoneValue(): void
    {
        $this->changeset_value->method('getValue')->willReturn(
            [
                (string) \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID,
            ]
        );

        $ugroups_bind = $this->getMockBuilder(\Tracker_FormElement_Field_List_Bind_Ugroups::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $result = $ugroups_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }

    public function testVisitListBindNull(): void
    {
        $null_bind = new \Tracker_FormElement_Field_List_Bind_Null($this->list_field);

        $result = $null_bind->accept($this->visitor, $this->parameters);

        self::assertEquals(new EmptyValue(), $result);
    }
}
