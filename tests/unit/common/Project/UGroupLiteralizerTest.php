<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UserManager;

final class UGroupLiteralizerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser&MockObject $user;
    private const PERMISSIONS_TYPE = 'PLUGIN_DOCMAN_%';

    private UGroupLiteralizer $ugroup_literalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user   = $this->createMock(\PFUser::class);
        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserByUserName')->willReturn($this->user);
        UserManager::setInstance($user_manager);
        $this->ugroup_literalizer = new UGroupLiteralizer();
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function testItIsProjectMember(): void
    {
        $this->user->method('getStatus')->willReturn('A');
        $userProjects = [
            ['group_id' => 101, 'unix_group_name' => 'gpig1'],
        ];
        $this->user->method('getProjects')->willReturn($userProjects);
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('getAllUgroups')->willReturn(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser(['site_active', 'gpig1_project_members']);
    }

    public function testItIsProjectAdmin(): void
    {
        $this->user->method('getStatus')->willReturn('A');
        $userProjects = [
            ['group_id' => 102, 'unix_group_name' => 'gpig2'],
        ];
        $this->user->method('getProjects')->willReturn($userProjects);
        $this->user->method('isMember')->willReturn(true);
        $this->user->method('getAllUgroups')->willReturn(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser(['site_active', 'gpig2_project_members', 'gpig2_project_admin']);
    }

    public function testItIsMemberOfAStaticUgroup(): void
    {
        $this->user->method('getStatus')->willReturn('A');
        $this->user->method('getProjects')->willReturn([]);
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('getAllUgroups')->willReturn(\TestHelper::arrayToDar(['ugroup_id' => 304]));

        $this->assertUserGroupsForUser(['site_active', 'ug_304']);
    }

    public function testItIsRestricted(): void
    {
        $this->user->method('getStatus')->willReturn('R');
        $this->user->method('getProjects')->willReturn([]);
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('getAllUgroups')->willReturn(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser(['site_restricted']);
    }

    public function testItIsNeitherRestrictedNorActive(): void
    {
        $this->user->method('getStatus')->willReturn('Not exists');
        $this->user->method('getProjects')->willReturn([]);
        $this->user->method('isMember')->willReturn(false);
        $this->user->method('getAllUgroups')->willReturn(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser([]);
    }

    private function assertUserGroupsForUser(array $expected): void
    {
        self::assertEquals($expected, $this->ugroup_literalizer->getUserGroupsForUserName('john_do'));
        self::assertEquals($expected, $this->ugroup_literalizer->getUserGroupsForUser($this->user));
    }

    public function testItCanTransformAnArrayWithUGroupMembersConstantIntoString(): void
    {
        $ugroup_ids = [ProjectUGroup::PROJECT_MEMBERS];
        $expected   = ['@gpig_project_members'];
        $this->assertUgroupIdsToString($ugroup_ids, $expected);
    }

    public function testItDoesntIncludeTwiceProjectMemberIfSiteActive(): void
    {
        $ugroup_ids = [ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_MEMBERS];
        $expected   = ['@site_active', '@gpig_project_members'];
        $this->assertUgroupIdsToString($ugroup_ids, $expected);
    }

    private function assertUgroupIdsToString($ugroup_ids, $expected): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withUnixName('gpig')
            ->build();

        $result = $this->ugroup_literalizer->ugroupIdsToString($ugroup_ids, $project);
        self::assertEquals($expected, $result);
    }

    public function testItCanReturnUgroupIdsFromAnItemAndItsPermissionTypes(): void
    {
        $object_id           = 100;
        $expected            = [ProjectUGroup::PROJECT_MEMBERS];
        $project             = ProjectTestBuilder::aProject()->build();
        $permissions_manager = $this->createMock(\PermissionsManager::class);
        $permissions_manager->method('getAuthorizedUGroupIdsForProject')->with($project, $object_id, self::PERMISSIONS_TYPE)->willReturn($expected);
        PermissionsManager::setInstance($permissions_manager);
        $result = $this->ugroup_literalizer->getUgroupIds($project, $object_id, self::PERMISSIONS_TYPE);
        self::assertEquals($expected, $result);
        PermissionsManager::clearInstance();
    }

    public function testItReturnsOnlyProjectUserUgroups(): void
    {
        $this->user->method('getStatus')->willReturn('A');
        $user_projects = [
            ['group_id' => 102, 'unix_group_name' => 'gpig2'],
        ];
        $user_groups   = [
            ['ugroup_id' => 105],
        ];
        $this->user->method('getProjects')->willReturn($user_projects);
        $this->user->method('isMember')->willReturn(true);
        $this->user->method('getAllUgroups')->willReturn($user_groups);

        $ugroups = $this->ugroup_literalizer->getProjectUserGroupsForUser($this->user);
        self::assertContains('gpig2_project_members', $ugroups);
        self::assertContains('gpig2_project_admin', $ugroups);
        self::assertContains('ug_105', $ugroups);
        self::assertNotContains('site_active', $ugroups);
        self::assertEquals(3, sizeof($ugroups));
    }

    public function testItReturnsOnlyProjectUserUgroupsIds(): void
    {
        $this->user->method('getStatus')->willReturn('A');
        $user_projects = [
            ['group_id' => 102, 'unix_group_name' => 'gpig2'],
        ];
        $user_groups   = [
            ['ugroup_id' => 105],
        ];
        $this->user->method('getProjects')->willReturn($user_projects);
        $this->user->method('isMember')->willReturn(true);
        $this->user->method('getAllUgroups')->willReturn($user_groups);

        $ugroups = $this->ugroup_literalizer->getProjectUserGroupsIdsForUser($this->user);
        self::assertContains('102_3', $ugroups);
        self::assertContains('102_4', $ugroups);
        self::assertContains('105', $ugroups);
        self::assertEquals(3, sizeof($ugroups));
    }
}
