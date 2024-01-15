<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project;

use Event;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UGroupBinding;
use UGroupDao;
use UGroupManager;

final class UgroupDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private UGroupDao&MockObject $dao;
    private UGroupManager&MockObject $manager;
    private EventManager&MockObject $event_manager;
    private UgroupDuplicator $ugroup_duplicator;
    private MemberAdder&MockObject $member_adder;

    protected function setUp(): void
    {
        $this->dao               = $this->createMock(UGroupDao::class);
        $this->manager           = $this->createMock(UGroupManager::class);
        $binding                 = $this->createMock(UGroupBinding::class);
        $this->member_adder      = $this->createMock(MemberAdder::class);
        $this->event_manager     = $this->createMock(EventManager::class);
        $this->ugroup_duplicator = new UgroupDuplicator($this->dao, $this->manager, $binding, $this->member_adder, $this->event_manager);
    }

    public function testItDuplicatesOnlyStaticGroups(): void
    {
        $template       = ProjectTestBuilder::aProject()->build();
        $new_project_id = 120;
        $ugroup_mapping = [];

        $this->manager->expects(self::once())->method('getStaticUGroups')->with($template)->willReturn([]);

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugroup_mapping, UserTestBuilder::buildWithDefaults());

        self::assertEmpty($ugroup_mapping);
    }

    public function testItReturnsTheMappingBetweenSourceAndDestinationUGroups(): void
    {
        $template       = ProjectTestBuilder::aProject()->build();
        $new_project_id = 120;
        $ugroup_mapping = [];

        $source_ugroup_id = 201;
        $source_ugroup    = $this->createMock(ProjectUGroup::class);
        $source_ugroup->method('getId')->willReturn($source_ugroup_id);
        $source_ugroup->method('isStatic')->willReturn(true);
        $source_ugroup->method('isBound')->willReturn(false);
        $source_ugroup->method('getMembers')->willReturn([]);
        $this->manager->expects(self::once())->method('getStaticUGroups')->with($template)->willReturn([$source_ugroup,]);

        $new_ugroup_id = 301;
        $new_ugroup    = ProjectUGroupTestBuilder::aCustomUserGroup($new_ugroup_id)->build();
        $this->dao->expects(self::once())->method('createUgroupFromSourceUgroup')->with($source_ugroup_id, $new_project_id)->willReturn($new_ugroup_id);
        $this->manager->expects(self::once())->method('getById')->with($new_ugroup_id)->willReturn($new_ugroup);

        $this->event_manager->expects(self::once())->method('processEvent')
            ->with(Event::UGROUP_DUPLICATION, ['source_ugroup' => $source_ugroup, 'new_ugroup_id' => $new_ugroup_id]);

        $this->dao->expects(self::once())->method('createBinding')->with($new_project_id, $source_ugroup_id, $new_ugroup_id);

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugroup_mapping, UserTestBuilder::buildWithDefaults());
        self::assertEquals([201 => 301], $ugroup_mapping);
    }

    public function testItAddUsersFromSourceGroup(): void
    {
        $template       = ProjectTestBuilder::aProject()->build();
        $new_project_id = 120;
        $ugroup_mapping = [];

        $source_ugroup_id = 201;
        $user1            = UserTestBuilder::buildWithId(1);
        $user2            = UserTestBuilder::buildWithId(2);
        $source_ugroup    = $this->createMock(ProjectUGroup::class);
        $source_ugroup->method('getId')->willReturn($source_ugroup_id);
        $source_ugroup->method('isStatic')->willReturn(true);
        $source_ugroup->method('isBound')->willReturn(false);
        $source_ugroup->method('getMembers')->willReturn([$user1, $user2]);
        $this->manager->expects(self::once())->method('getStaticUGroups')->with($template)->willReturn([$source_ugroup]);

        $new_ugroup_id = 301;
        $new_ugroup    = ProjectUGroupTestBuilder::aCustomUserGroup($new_ugroup_id)->build();
        $this->dao->expects(self::once())->method('createUgroupFromSourceUgroup')->with($source_ugroup_id, $new_project_id)->willReturn($new_ugroup_id);
        $this->manager->expects(self::once())->method('getById')->with($new_ugroup_id)->willReturn($new_ugroup);

        $this->event_manager->expects(self::once())->method('processEvent')
            ->with(Event::UGROUP_DUPLICATION, ['source_ugroup' => $source_ugroup, 'new_ugroup_id' => $new_ugroup_id]);

        $this->dao->expects(self::once())->method('createBinding')->with($new_project_id, $source_ugroup_id, $new_ugroup_id);

        $project_admin = UserTestBuilder::buildWithDefaults();

        $this->member_adder->expects(self::exactly(2))->method('addMember')
            ->withConsecutive(
                [$user1, $new_ugroup, $project_admin],
                [$user2, $new_ugroup, $project_admin],
            );

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugroup_mapping, $project_admin);
    }

    public function testItAddUsersWithExceptionHandling(): void
    {
        $template       = ProjectTestBuilder::aProject()->build();
        $new_project_id = 120;
        $ugroup_mapping = [];

        $source_ugroup_id = 201;
        $user1            = UserTestBuilder::buildWithId(1);
        $user2            = UserTestBuilder::buildWithId(2);
        $source_ugroup    = $this->createMock(ProjectUGroup::class);
        $source_ugroup->method('getId')->willReturn($source_ugroup_id);
        $source_ugroup->method('isStatic')->willReturn(true);
        $source_ugroup->method('isBound')->willReturn(false);
        $source_ugroup->method('getMembers')->willReturn([$user1, $user2]);
        $this->manager->expects(self::once())->method('getStaticUGroups')->with($template)->willReturn([$source_ugroup]);

        $new_ugroup_id = 301;
        $new_ugroup    = ProjectUGroupTestBuilder::aCustomUserGroup($new_ugroup_id)->build();
        $this->dao->expects(self::once())->method('createUgroupFromSourceUgroup')->with($source_ugroup_id, $new_project_id)->willReturn($new_ugroup_id);
        $this->manager->expects(self::once())->method('getById')->with($new_ugroup_id)->willReturn($new_ugroup);

        $this->event_manager->expects(self::once())->method('processEvent')
            ->with(Event::UGROUP_DUPLICATION, ['source_ugroup' => $source_ugroup, 'new_ugroup_id' => $new_ugroup_id]);

        $this->dao->expects(self::once())->method('createBinding')->with($new_project_id, $source_ugroup_id, $new_ugroup_id);

        $this->member_adder->method('addMember')
            ->willThrowException(new CannotAddRestrictedUserToProjectNotAllowingRestricted($user1, ProjectTestBuilder::aProject()->withId(505)->build()));

        $this->ugroup_duplicator->duplicateOnProjectCreation($template, $new_project_id, $ugroup_mapping, UserTestBuilder::buildWithDefaults());
    }
}
