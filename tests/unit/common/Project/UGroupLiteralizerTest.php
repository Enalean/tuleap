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

namespace Tuleap\Project;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use UserManager;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UGroupLiteralizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected $membership;
    protected $user;
    public const PERMISSIONS_TYPE = 'PLUGIN_DOCMAN_%';

    /**
     * @var UGroupLiteralizer
     */
    private $ugroup_literalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user   = \Mockery::spy(\PFUser::class);
        $user_manager = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('getUserByUserName')->andReturns($this->user);
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
        $this->user->shouldReceive('getStatus')->andReturns('A');
        $userProjects = [
                ['group_id' => 101, 'unix_group_name' => 'gpig1']
        ];
        $this->user->shouldReceive('getProjects')->andReturns($userProjects);
        $this->user->shouldReceive('isMember')->andReturns(false);
        $this->user->shouldReceive('getAllUgroups')->andReturns(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser(['site_active', 'gpig1_project_members']);
    }

    public function testItIsProjectAdmin(): void
    {
        $this->user->shouldReceive('getStatus')->andReturns('A');
        $userProjects = [
                ['group_id' => 102, 'unix_group_name' => 'gpig2']
        ];
        $this->user->shouldReceive('getProjects')->andReturns($userProjects);
        $this->user->shouldReceive('isMember')->andReturns(true);
        $this->user->shouldReceive('getAllUgroups')->andReturns(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser(['site_active', 'gpig2_project_members', 'gpig2_project_admin']);
    }

    public function testItIsMemberOfAStaticUgroup(): void
    {
        $this->user->shouldReceive('getStatus')->andReturns('A');
        $this->user->shouldReceive('getProjects')->andReturns([]);
        $this->user->shouldReceive('isMember')->andReturns(false);
        $this->user->shouldReceive('getAllUgroups')->andReturns(\TestHelper::arrayToDar(['ugroup_id' => 304]));

        $this->assertUserGroupsForUser(['site_active', 'ug_304']);
    }

    public function testItIsRestricted(): void
    {
        $this->user->shouldReceive('getStatus')->andReturns('R');
        $this->user->shouldReceive('getProjects')->andReturns([]);
        $this->user->shouldReceive('isMember')->andReturns(false);
        $this->user->shouldReceive('getAllUgroups')->andReturns(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser(['site_restricted']);
    }

    public function testItIsNeitherRestrictedNorActive(): void
    {
        $this->user->shouldReceive('getStatus')->andReturns('Not exists');
        $this->user->shouldReceive('getProjects')->andReturns([]);
        $this->user->shouldReceive('isMember')->andReturns(false);
        $this->user->shouldReceive('getAllUgroups')->andReturns(\TestHelper::emptyDar());

        $this->assertUserGroupsForUser([]);
    }

    private function assertUserGroupsForUser(array $expected): void
    {
        $this->assertEquals($expected, $this->ugroup_literalizer->getUserGroupsForUserName('john_do'));
        $this->assertEquals($expected, $this->ugroup_literalizer->getUserGroupsForUser($this->user));
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
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getUnixName')->andReturns('gpig');

        $result = $this->ugroup_literalizer->ugroupIdsToString($ugroup_ids, $project);
        $this->assertEquals($expected, $result);
    }

    public function testItCanReturnUgroupIdsFromAnItemAndItsPermissionTypes(): void
    {
        $object_id = 100;
        $expected  = [ProjectUGroup::PROJECT_MEMBERS];
        $project   = \Mockery::spy(\Project::class);
        $permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($project, $object_id, self::PERMISSIONS_TYPE)->andReturns($expected);
        PermissionsManager::setInstance($permissions_manager);
        $result = $this->ugroup_literalizer->getUgroupIds($project, $object_id, self::PERMISSIONS_TYPE);
        $this->assertEquals($expected, $result);
        PermissionsManager::clearInstance();
    }

    public function testItReturnsOnlyProjectUserUgroups(): void
    {
        $this->user->shouldReceive('getStatus')->andReturns('A');
        $user_projects = [
            ['group_id' => 102, 'unix_group_name' => 'gpig2']
        ];
        $user_groups = [
            ['ugroup_id' => 105]
        ];
        $this->user->shouldReceive('getProjects')->andReturns($user_projects);
        $this->user->shouldReceive('isMember')->andReturns(true);
        $this->user->shouldReceive('getAllUgroups')->andReturns($user_groups);

        $ugroups = $this->ugroup_literalizer->getProjectUserGroupsForUser($this->user);
        $this->assertContains('gpig2_project_members', $ugroups);
        $this->assertContains('gpig2_project_admin', $ugroups);
        $this->assertContains('ug_105', $ugroups);
        $this->assertNotContains('site_active', $ugroups);
        $this->assertEquals(3, sizeof($ugroups));
    }

    public function testItReturnsOnlyProjectUserUgroupsIds(): void
    {
        $this->user->shouldReceive('getStatus')->andReturns('A');
        $user_projects = [
            ['group_id' => 102, 'unix_group_name' => 'gpig2']
        ];
        $user_groups = [
            ['ugroup_id' => 105]
        ];
        $this->user->shouldReceive('getProjects')->andReturns($user_projects);
        $this->user->shouldReceive('isMember')->andReturns(true);
        $this->user->shouldReceive('getAllUgroups')->andReturns($user_groups);

        $ugroups = $this->ugroup_literalizer->getProjectUserGroupsIdsForUser($this->user);
        $this->assertContains('102_3', $ugroups);
        $this->assertContains('102_4', $ugroups);
        $this->assertContains('105', $ugroups);
        $this->assertEquals(3, sizeof($ugroups));
    }
}
