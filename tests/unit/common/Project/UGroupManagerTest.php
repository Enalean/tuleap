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
 *
 */

namespace Tuleap\Project;

use EventManager;
use ForgeAccess;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;

class UGroupManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /**
     * @var UGroupManager
     */
    private $user_group_manager;

    /**
     * @var \Mockery\MockInterface
     */
    private $user_group_dao;

    /**
     * @var \Mockery\MockInterface
     */
    private $event_manager;

    /**
     * @var \Mockery\MockInterface
     */
    private $user_group_user_dao;

    /**
     * @var \Mockery\MockInterface
     */
    private $dynamic_user_group_member_updater;

    /**
     * @before
     */
    public function instantiateMocks()
    {
        $globals = array_merge([], $GLOBALS);

        $this->user_group_dao = Mockery::mock(UGroupDao::class);
        $this->event_manager = Mockery::mock(EventManager::class);
        $this->user_group_user_dao = Mockery::mock(UGroupUserDao::class);
        $this->user_group_dao->shouldReceive('searchDynamicAndStaticByGroupId')
            ->andReturn([
                ['ugroup_id' => ProjectUGroup::ANONYMOUS],
                ['ugroup_id' => ProjectUGroup::AUTHENTICATED],
                ['ugroup_id' => ProjectUGroup::REGISTERED],
                ['ugroup_id' => ProjectUGroup::NONE],
            ])
            ->byDefault();

        $this->dynamic_user_group_member_updater = Mockery::mock(DynamicUGroupMembersUpdater::class);

        $this->user_group_manager = Mockery::mock(
            UGroupManager::class,
            [
                $this->user_group_dao,
                $this->event_manager,
                $this->user_group_user_dao,
                $this->dynamic_user_group_member_updater
            ]
        )->makePartial();

        $this->user_group_manager->shouldReceive('getDao')
            ->andReturn($this->user_group_dao);

        $GLOBALS = $globals;
    }

    public function testGetAvailableUgroupsReturnsAnonymousGroupWhenPlatformAccessIsAnonymousAndProjectIsPublic()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $project = $this->buildAProject(Project::ACCESS_PUBLIC);

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, ProjectUGroup::ANONYMOUS);
    }

    public function testGetAvailableUgroupsDoesNotReturnAnonymousGroupWhenPlatformAccessIsRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->buildAProject();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::ANONYMOUS);
    }

    public function testGetAvailableUgroupsDoesNotReturnAnonymousGroupWhenPlatformAccessIsRegular()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = $this->buildAProject();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::ANONYMOUS);
    }

    public function testGetAvailableUgroupsReturnsAuthenticatedGroupWhenPlatformAccessIsRestrictedAndProjectAllowsRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->buildAProject(Project::ACCESS_PUBLIC_UNRESTRICTED);

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, ProjectUGroup::AUTHENTICATED);
    }

    public function testGetAvailableUgroupsDoesNotReturnAuthenticatedGroupWhenProjectDoesNotAllowRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::AUTHENTICATED);
    }

    public function testGetAvailableUgroupsReturnsRegisteredGroupWhenProjectIsPublic()
    {
        $project = $this->buildAProject(Project::ACCESS_PUBLIC);

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, ProjectUGroup::REGISTERED);
    }

    public function testGetAvailableUgroupsDoesNotReturnRegisteredGroupWhenProjectIsPrivate()
    {
        $project = $this->buildAProject(Project::ACCESS_PRIVATE);

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::REGISTERED);
    }

    public function testGetAvailableUgroupsDoesNotReturnNoneGroup()
    {
        $project = $this->buildAProject();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsNotContainsId($user_groups, ProjectUGroup::NONE);
    }

    public function testGetAvailableUgroupsReturnsNonSystemGroups()
    {
        $this->user_group_dao->shouldReceive('searchDynamicAndStaticByGroupId')
            ->andReturn([['ugroup_id' => 102]]);
        $project = $this->buildAProject();

        $user_groups = $this->user_group_manager->getAvailableUGroups($project);

        $this->assertUgroupsContainsId($user_groups, 102);
    }

    private function buildAProject($access = Project::ACCESS_PRIVATE): Project
    {
        return new Project(['group_id' => 1, 'access' => $access]);
    }

    /**
     * @param ProjectUGroup[] $user_groups
     */
    private function assertUgroupsContainsId(array $user_groups, int $expected_id): void
    {
        $this->assertContains(
            $expected_id,
            $this->flattenUserGroupsToUserGroupIDs(...$user_groups)
        );
    }

    /**
     * @param ProjectUGroup[] $user_groups
     */
    private function assertUgroupsNotContainsId(array $user_groups, int $not_expected_id): void
    {
        $this->assertNotContains(
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
