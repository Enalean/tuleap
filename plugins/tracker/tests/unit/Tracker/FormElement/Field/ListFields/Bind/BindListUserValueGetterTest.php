<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use DataAccessObject;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List_Bind_DefaultvalueDao;
use Tracker_FormElement_Field_List_Bind_UsersValue;

class BindListUserValueGetterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BindListUserValueGetter
     */
    private $getter;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_Bind_DefaultvalueDao
     */
    private $default_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserHelper
     */
    private $user_helper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default_dao = Mockery::mock(Tracker_FormElement_Field_List_Bind_DefaultvalueDao::class);
        $this->user_helper = Mockery::mock(\UserHelper::class);

        $this->getter = Mockery::mock(BindListUserValueGetter::class, [$this->default_dao, $this->user_helper])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    public function testItReturnsAnEmptyArrayWhenTrackerISNotFound(): void
    {
        $ugroups       = ['group_members'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn(null);

        $da = Mockery::mock(DataAccessObject::class);
        $this->default_dao->shouldReceive('getDa')->andReturn($da);

        $this->assertEquals([], $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForProjectMemberGroup(): void
    {
        $ugroups       = ['group_members'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->once()->andReturn('user.user_name');

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->getter->shouldReceive('getUGroupUtilsDynamicMembers')->withArgs(
            [
                ProjectUGroup::PROJECT_MEMBERS,
                $keyword,
                $bindvalue_ids,
                $tracker,
                false,
                false
            ]
        )->once()->andReturn('sql fragement');

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals($expected, $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForProjectAdminGroup(): void
    {
        $ugroups       = ['group_admins'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->once()->andReturn('user.user_name');

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->getter->shouldReceive('getUGroupUtilsDynamicMembers')->withArgs(
            [
                ProjectUGroup::PROJECT_ADMIN,
                $keyword,
                $bindvalue_ids,
                $tracker,
                false,
                false
            ]
        )->once()->andReturn('sql fragement');

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals($expected, $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItReturnsAnEmptyArrayIfNoUserIsFoundInUgroups(): void
    {
        $ugroups       = ['group_admins'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->once()->andReturn('user.user_name');

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->getter->shouldReceive('getUGroupUtilsDynamicMembers')->withArgs(
            [
                ProjectUGroup::PROJECT_ADMIN,
                $keyword,
                $bindvalue_ids,
                $tracker,
                false,
                false
            ]
        )->once()->andReturn(null);

        $this->default_dao->shouldReceive('retrieve')->never();

        $this->assertEquals([], $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForArtifactSubmitters(): void
    {
        $ugroups       = ['artifact_submitters'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLQuery')->once();
        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->twice()->andReturn('user.user_name');
        $this->user_helper->shouldReceive('getUsersSorted')->never();

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals($expected, $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForArtifactModifiers(): void
    {
        $ugroups       = ['artifact_modifiers'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLQuery')->once();
        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->twice()->andReturn('user.user_name');
        $this->user_helper->shouldReceive('getUsersSorted')->never();

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals($expected, $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForDynamicUGroup(): void
    {
        $ugroups       = ['ugroup_3'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->once()->andReturn('user.user_name');

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $this->getter->shouldReceive('getUGroupUtilsDynamicMembers')->withArgs(
            [
                ProjectUGroup::PROJECT_MEMBERS,
                $keyword,
                $bindvalue_ids,
                $tracker,
                false,
                false
            ]
        )->once()->andReturn('sql fragement');

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals($expected, $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForStaticUGroup(): void
    {
        $ugroups       = ['ugroup_109'];
        $keyword       = "";
        $bindvalue_ids = [];

        $field         = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker       = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->once()->andReturn('user.user_name');

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $this->getter->shouldReceive('getAllMembersOfStaticGroup')->withArgs(
            [
                $keyword,
                $bindvalue_ids,
                $matches = ["ugroup_109", 109]
            ]
        )->once()->andReturn('sql fragement');

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals(
            $expected,
            $this->getter->getUsersValueByKeywordAndIds($ugroups, $keyword, $bindvalue_ids, $field)
        );
    }


    public function testItExtractActiveUserListForStaticUGroup(): void
    {
        $ugroups       = ['ugroup_109'];

        $field   = Mockery::mock(Tracker_FormElement_Field::class);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(1);
        $tracker->shouldReceive('getGroupId')->andReturn(101);
        $field->shouldReceive('getTracker')->andReturn($tracker);

        $da = Mockery::mock(DataAccessObject::class);
        $da->shouldReceive('escapeIntImplode');
        $da->shouldReceive('escapeInt');

        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->once()->andReturn('user.user_name');

        $this->default_dao->shouldReceive('getDa')->once()->andReturn($da);

        $this->default_dao->shouldReceive('retrieve')->once()->andReturn(
            [
                ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
                ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name']
            ]
        );

        $this->user_helper->shouldReceive('getDisplayNameSQLQuery')->once();
        $this->user_helper->shouldReceive('getDisplayNameSQLOrder')->andReturn('user.user_name');

        $this->getter->shouldReceive('getAllMembersOfStaticGroup')->never();

        $expected = [
            101 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                101,
                'user 1',
                'user 1 full name'
            ),
            102 => new Tracker_FormElement_Field_List_Bind_UsersValue(
                102,
                'user 2',
                'user 2 full name'
            ),
        ];

        $this->assertEquals(
            $expected,
            $this->getter->getActiveUsersValue($ugroups, $field)
        );
    }
}
