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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PermissionsManagerGetAuthorizedUGroupIdsForProjectTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /**
     * @var PermissionsManager&MockObject
     */
    private $permissions_manager;
    private string $permission_type;
    private string $object_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = $this->createPartialMock(PermissionsManager::class, [
            'getAuthorizedUgroups',
        ]);
    }

    public function testItReturnsTheListOfStaticGroups(): void
    {
        $this->stubAuthorizedUgroups(['ugroup_id' => 102]);
        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $this->assertAuthorizedUGroupIdsForProjectEqual([102], $project);
    }

    public function testItReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsAnonymous(): void
    {
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::ANONYMOUS]);
        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::PROJECT_MEMBERS], $project);
    }

    public function testItReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsAuthenticated(): void
    {
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::AUTHENTICATED]);
        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::PROJECT_MEMBERS], $project);
    }

    public function testItReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsRegistered(): void
    {
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::REGISTERED]);
        $project = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::PROJECT_MEMBERS], $project);
    }

    public function testItReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::ANONYMOUS]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsRegisteredUsers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::REGISTERED]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsAuthenticatedUsers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::AUTHENTICATED]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsProjectMembersWhenPlatformIsRegularProjectIsPublicAndUGroupIsProjectMembers(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::PROJECT_MEMBERS], $project);
    }

    public function testItReturnsAnonymousWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::ANONYMOUS]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::ANONYMOUS], $project);
    }

    public function testItReturnsRegisteredWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsAuthenticated(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::AUTHENTICATED]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsRegisteredWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsRegistered(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::REGISTERED]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsRegisteredWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::ANONYMOUS]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsRegisteredWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsRegistered(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = ProjectTestBuilder::aProject()->withAccessPublic()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::REGISTERED]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::REGISTERED], $project);
    }

    public function testItReturnsAuthenticatedWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = ProjectTestBuilder::aProject()->withAccessPublicIncludingRestricted()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::ANONYMOUS]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::AUTHENTICATED], $project);
    }

    public function testItReturnsAuthenticatedWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAuthenticated(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = ProjectTestBuilder::aProject()->withAccessPublicIncludingRestricted()->build();
        $this->stubAuthorizedUgroups(['ugroup_id' => ProjectUGroup::AUTHENTICATED]);

        $this->assertAuthorizedUGroupIdsForProjectEqual([ProjectUGroup::AUTHENTICATED], $project);
    }

    private function stubAuthorizedUgroups(array $groups): void
    {
        $this->permissions_manager->method('getAuthorizedUgroups')
            ->with($this->object_id, $this->permission_type, false)
            ->willReturn(TestHelper::arrayToDar($groups));
    }

    private function assertAuthorizedUGroupIdsForProjectEqual(array $groups, Project $project): void
    {
        $this->assertEquals(
            $groups,
            $this->permissions_manager->getAuthorizedUGroupIdsForProject($project, $this->object_id, $this->permission_type)
        );
    }
}
