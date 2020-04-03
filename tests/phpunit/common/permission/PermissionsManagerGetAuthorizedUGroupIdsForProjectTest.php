<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PermissionsManagerGetAuthorizedUGroupIdsForProjectTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    private $permissions_manager;
    private $project;
    private $permission_type;
    private $object_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project             = \Mockery::spy(\Project::class);
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = \Mockery::mock(\PermissionsManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItReturnsTheListOfStaticGroups()
    {
        $this->stubAuthorizedUgroups(array('ugroup_id' => 102));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(102));
    }

    public function testItReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsAnonymous()
    {
        $this->project->shouldReceive('isPublic')->andReturns(false);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function testItReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsAuthenticated()
    {
        $this->project->shouldReceive('isPublic')->andReturns(false);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function testItReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsRegistered()
    {
        $this->project->shouldReceive('isPublic')->andReturns(false);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function testItReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsRegisteredUsers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsAuthenticatedUsers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsProjectMembersWhenPlatformIsRegularProjectIsPublicAndUGroupIsProjectMembers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function testItReturnsAnonymousWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::ANONYMOUS));
    }

    public function testItReturnsRegisteredWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsAuthenticated()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsRegisteredWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsRegistered()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsRegisteredWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsRegisteredWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsRegistered()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function testItReturnsAuthenticatedWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('allowsRestricted')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::AUTHENTICATED));
    }

    public function testItReturnsAuthenticatedWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAuthenticated()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $this->project->shouldReceive('isPublic')->andReturns(true);
        $this->project->shouldReceive('allowsRestricted')->andReturns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::AUTHENTICATED));
    }

    private function stubAuthorizedUgroups($groups)
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUgroups')->with($this->object_id, $this->permission_type, false)->andReturns(\TestHelper::arrayToDar($groups));
    }

    private function assertAuthorizedUGroupIdsForProjectEqual($groups)
    {
        $this->assertEquals(
            $groups,
            $this->permissions_manager->getAuthorizedUGroupIdsForProject($this->project, $this->object_id, $this->permission_type)
        );
    }
}
