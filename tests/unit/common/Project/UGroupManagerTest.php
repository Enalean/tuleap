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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project;

use EventManager;
use ForgeAccess;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;

final class UGroupManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private UGroupManager&MockObject $user_group_manager;
    private UGroupDao&MockObject $user_group_dao;
    private EventManager&MockObject $event_manager;
    private UGroupUserDao&MockObject $user_group_user_dao;
    private DynamicUGroupMembersUpdater&MockObject $dynamic_user_group_member_updater;

    protected function setUp(): void
    {
        $this->user_group_dao      = $this->createMock(UGroupDao::class);
        $this->event_manager       = $this->createMock(EventManager::class);
        $this->user_group_user_dao = $this->createMock(UGroupUserDao::class);

        $this->dynamic_user_group_member_updater = $this->createMock(DynamicUGroupMembersUpdater::class);

        $this->user_group_manager = $this->getMockBuilder(UGroupManager::class)
            ->setConstructorArgs([
                $this->user_group_dao,
                $this->event_manager,
                $this->user_group_user_dao,
                $this->dynamic_user_group_member_updater,
            ])
            ->onlyMethods([
                'getDao',
            ])
            ->getMock();

        $this->user_group_manager->method('getDao')->willReturn($this->user_group_dao);
    }

    public function defaultUGroupDaoCall(): void
    {
        $this->user_group_dao->method('searchDynamicAndStaticByGroupId')
            ->willReturn([
                ['ugroup_id' => ProjectUGroup::ANONYMOUS],
                ['ugroup_id' => ProjectUGroup::AUTHENTICATED],
                ['ugroup_id' => ProjectUGroup::REGISTERED],
                ['ugroup_id' => ProjectUGroup::NONE],
            ]);
    }

    public function testGetAvailableUgroupsReturnsAnonymousGroupWhenPlatformAccessIsAnonymousAndProjectIsPublic(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $project = $this->buildAProject(Project::ACCESS_PUBLIC);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, ProjectUGroup::ANONYMOUS);
    }

    public function testGetAvailableUgroupsDoesNotReturnAnonymousGroupWhenPlatformAccessIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::ANONYMOUS);
    }

    public function testGetAvailableUgroupsDoesNotReturnAnonymousGroupWhenPlatformAccessIsRegular(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::ANONYMOUS);
    }

    public function testGetAvailableUgroupsReturnsAuthenticatedGroupWhenPlatformAccessIsRestrictedAndProjectAllowsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->buildAProject(Project::ACCESS_PUBLIC_UNRESTRICTED);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, ProjectUGroup::AUTHENTICATED);
    }

    public function testGetAvailableUgroupsDoesNotReturnAuthenticatedGroupWhenProjectDoesNotAllowRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::AUTHENTICATED);
    }

    public function testGetAvailableUgroupsReturnsRegisteredGroupWhenProjectIsPublic(): void
    {
        $project = $this->buildAProject(Project::ACCESS_PUBLIC);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, ProjectUGroup::REGISTERED);
    }

    public function testGetAvailableUgroupsDoesNotReturnRegisteredGroupWhenProjectIsPrivate(): void
    {
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::REGISTERED);
    }

    public function testGetAvailableUgroupsDoesNotReturnNoneGroup(): void
    {
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);
        $this->defaultUGroupDaoCall();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::NONE);
    }

    public function testGetAvailableUgroupsReturnsNonSystemGroups(): void
    {
        $this->user_group_dao->method('searchDynamicAndStaticByGroupId')
            ->willReturn([['ugroup_id' => 102]]);
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, 102);
    }

    private function buildAProject($access): Project
    {
        return new Project(['group_id' => 1, 'access' => $access]);
    }

    /**
     * @param ProjectUGroup[] $user_groups
     */
    private function assertUgroupsContainsId(array $user_groups, int $expected_id): void
    {
        self::assertContains(
            $expected_id,
            $this->flattenUserGroupsToUserGroupIDs(...$user_groups)
        );
    }

    /**
     * @param ProjectUGroup[] $user_groups
     */
    private function assertUgroupsNotContainsId(array $user_groups, int $not_expected_id): void
    {
        self::assertNotContains(
            $not_expected_id,
            $this->flattenUserGroupsToUserGroupIDs(...$user_groups)
        );
    }

    private function flattenUserGroupsToUserGroupIDs(ProjectUGroup ...$user_groups): array
    {
        return array_map(
            function (ProjectUGroup $user_group): int {
                return $user_group->getId();
            },
            $user_groups
        );
    }
}
