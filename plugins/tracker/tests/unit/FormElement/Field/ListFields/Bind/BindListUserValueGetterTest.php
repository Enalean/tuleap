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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use TestHelper;
use Tracker_FormElement_Field;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use UserHelper;

#[DisableReturnValueGenerationForTestDoubles]
final class BindListUserValueGetterTest extends TestCase
{
    private BindListUserValueGetter&MockObject $getter;
    private BindDefaultValueDao&MockObject $default_dao;
    private UserHelper&MockObject $user_helper;

    protected function setUp(): void
    {
        $this->default_dao     = $this->createMock(BindDefaultValueDao::class);
        $this->user_helper     = $this->createMock(UserHelper::class);
        $platform_users_getter = $this->createMock(PlatformUsersGetter::class);
        $platform_users_getter->method('getRegisteredUsers')->willReturn([]);

        $this->getter = $this->getMockBuilder(BindListUserValueGetter::class)
            ->setConstructorArgs([$this->default_dao, $this->user_helper, $platform_users_getter, new DatabaseUUIDV7Factory()])
            ->onlyMethods(['getUGroupUtilsDynamicMembers', 'getAllMembersOfStaticGroup'])
            ->getMock();
    }

    public function testItReturnsAnEmptyArrayWhenTrackerISNotFound(): void
    {
        $ugroups       = ['group_members'];
        $bindvalue_ids = [];

        $field = $this->createMock(Tracker_FormElement_Field::class);
        $field->method('getTracker')->willReturn(null);

        $this->default_dao->method('getDa')->willReturn(TestHelper::emptyDa());

        self::assertEquals([], $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForProjectMemberGroup(): void
    {
        $ugroups       = ['group_members'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(3541)->inTracker($tracker)->build();

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLOrder')->willReturn('user.user_name');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->getter->expects($this->once())->method('getUGroupUtilsDynamicMembers')
            ->with(ProjectUGroup::PROJECT_MEMBERS, $bindvalue_ids, $tracker)->willReturn('sql fragement');

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $subset = $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }

    public function testItExtractUserListForProjectAdminGroup(): void
    {
        $ugroups       = ['group_admins'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(354)->inTracker($tracker)->build();

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLOrder')->willReturn('user.user_name');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->getter->expects($this->once())->method('getUGroupUtilsDynamicMembers')
            ->with(ProjectUGroup::PROJECT_ADMIN, $bindvalue_ids, $tracker)->willReturn('sql fragement');

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $subset = $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }

    public function testItReturnsAnEmptyArrayIfNoUserIsFoundInUgroups(): void
    {
        $ugroups       = ['group_admins'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(354)->inTracker($tracker)->build();

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLOrder')->willReturn('user.user_name');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->getter->expects($this->once())->method('getUGroupUtilsDynamicMembers')
            ->with(ProjectUGroup::PROJECT_ADMIN, $bindvalue_ids, $tracker)->willReturn(null);

        $this->default_dao->expects($this->never())->method('retrieve');

        self::assertEquals([], $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field));
    }

    public function testItExtractUserListForArtifactSubmitters(): void
    {
        $ugroups       = ['artifact_submitters'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(652)->inTracker($tracker)->build();

        $this->user_helper->expects($this->exactly(2))->method('getDisplayNameSQLOrder')->willReturn('user.user_name');
        $this->user_helper->method('getDisplayNameSQLQuery')->willReturn('');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $subset = $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }

    public function testItExtractUserListForArtifactModifiers(): void
    {
        $ugroups       = ['artifact_modifiers'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(654)->inTracker($tracker)->build();

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLQuery')->willReturn('user.user_name');
        $this->user_helper->method('getDisplayNameSQLOrder')->willReturn('');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $subset = $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }

    public function testItExtractUserListForDynamicUGroup(): void
    {
        $ugroups       = ['ugroup_3'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(354)->inTracker($tracker)->build();

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLOrder')->willReturn('user.user_name');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $this->getter->expects($this->once())->method('getUGroupUtilsDynamicMembers')
            ->with(ProjectUGroup::PROJECT_MEMBERS, $bindvalue_ids, $tracker)->willReturn('sql fragement');

        $subset = $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }

    public function testItExtractUserListForStaticUGroup(): void
    {
        $ugroups       = ['ugroup_109'];
        $bindvalue_ids = [];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(354)->inTracker($tracker)->build();

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLOrder')->willReturn('user.user_name');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $this->getter->expects($this->once())->method('getAllMembersOfStaticGroup')
            ->with($bindvalue_ids, ['ugroup_109', 109])->willReturn('sql fragement');

        $subset = $this->getter->getSubsetOfUsersValueWithUserIds($ugroups, $bindvalue_ids, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }

    public function testItExtractActiveUserListForStaticUGroup(): void
    {
        $ugroups = ['ugroup_109'];

        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $field   = ListFieldBuilder::aListField(654)->inTracker($tracker)->build();

        $this->user_helper->expects($this->exactly(2))->method('getDisplayNameSQLOrder')->willReturn('user.user_name');
        $this->user_helper->method('getDisplayNameSQLQuery')->willReturn('');

        $this->default_dao->expects($this->once())->method('getDa')->willReturn(TestHelper::emptyDa());

        $this->default_dao->expects($this->once())->method('retrieve')->willReturn([
            ['user_id' => 101, 'user_name' => 'user 1', 'full_name' => 'user 1 full name'],
            ['user_id' => 102, 'user_name' => 'user 2', 'full_name' => 'user 2 full name'],
        ]);

        $this->user_helper->expects($this->once())->method('getDisplayNameSQLQuery')->willReturn('user.user_name');

        $this->getter->expects($this->never())->method('getAllMembersOfStaticGroup');

        $subset = $this->getter->getActiveUsersValue($ugroups, $field);
        self::assertEquals('user 1', $subset[101]->getUsername());
        self::assertEquals('user 1 full name', $subset[101]->getLabel());

        self::assertEquals('user 2', $subset[102]->getUsername());
        self::assertEquals('user 2 full name', $subset[102]->getLabel());
    }
}
