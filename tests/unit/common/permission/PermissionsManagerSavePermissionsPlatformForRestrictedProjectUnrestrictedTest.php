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
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PermissionsManagerSavePermissionsPlatformForRestrictedProjectUnrestrictedTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    protected PermissionsManager $permissions_manager;
    protected Project $project;
    protected string $permission_type;
    protected string $object_id;
    protected PermissionsDao&MockObject $permissions_dao;
    protected int $project_id;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->project_id          = 404;
        $this->project             = ProjectTestBuilder::aProject()
            ->withId($this->project_id)
            ->withAccessPublicIncludingRestricted()
            ->build();
        $this->permissions_dao     = $this->createMock(PermissionsDao::class);
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = new PermissionsManager($this->permissions_dao);
        $this->permissions_dao->method('clearPermission')->willReturn(true);
        $this->permissions_dao->method('addHistory');
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
    }

    protected function expectPermissionsOnce($ugroup): void
    {
        $this->permissions_dao
            ->expects($this->once())
            ->method('addPermission')
            ->with($this->permission_type, $this->object_id, $ugroup)
            ->willReturn(true);
    }

    protected function savePermissions($ugroups): void
    {
        $this->permissions_manager->savePermissions($this->project, $this->object_id, $this->permission_type, $ugroups);
    }

    public function testItSavesAuthenticatedSelectedAnonymous(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions([ProjectUGroup::ANONYMOUS]);
    }

    public function testItSavesAuthenticatedWhenSelectedAuthenticated(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions([ProjectUGroup::AUTHENTICATED]);
    }

    public function testItSavesRegisteredWhenSelectedRegistered(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions([ProjectUGroup::REGISTERED]);
    }

    public function testItSavesProjectMembersWhenSelectedProjectMembers(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions([ProjectUGroup::PROJECT_MEMBERS]);
    }

    public function testItSavesOnlyAuthenticatedWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions([ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_ADMIN, 104]);
    }

    public function testItSavesOnlyRegisteredWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions([ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_ADMIN, 104]);
    }

    public function testItSavesOnlyAuthenticatedWhenPresentWithAuthenticatedProjectAdminsAndStaticGroup(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions([ProjectUGroup::AUTHENTICATED, ProjectUGroup::PROJECT_ADMIN, 104]);
    }

    public function testItSavesMembersAndStaticWhenPresentWithMembersProjectAdminsAndStaticGroup(): void
    {
        $matcher = self::exactly(2);
        $this->permissions_dao
            ->expects($matcher)
            ->method('addPermission')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(ProjectUGroup::PROJECT_MEMBERS, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(104, $parameters[2]);
                }
                return true;
            });

        $this->savePermissions([ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, 104]);
    }

    public function testItSavesAdminsAndStaticWhenPresentWithProjectAdminsAndStaticGroup(): void
    {
        $matcher = self::exactly(2);
        $this->permissions_dao
            ->expects($matcher)
            ->method('addPermission')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(ProjectUGroup::PROJECT_ADMIN, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(104, $parameters[2]);
                }
                return true;
            });

        $this->savePermissions([ProjectUGroup::PROJECT_ADMIN, 104]);
    }

    public function testItSavesSVNAdminWikiAdminAndStatic(): void
    {
        $matcher = self::exactly(3);
        $this->permissions_dao
            ->expects($matcher)
            ->method('addPermission')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(ProjectUGroup::SVN_ADMIN, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(ProjectUGroup::WIKI_ADMIN, $parameters[2]);
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame($this->permission_type, $parameters[0]);
                    self::assertSame($this->object_id, $parameters[1]);
                    self::assertSame(104, $parameters[2]);
                }
                return true;
            });

        $this->savePermissions([ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, 104]);
    }

    public function testItSavesProjectMembersWhenSVNAdminWikiAdminAndProjectMembers(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions([ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, ProjectUGroup::PROJECT_MEMBERS]);
    }

    public function testItSavesAuthenticatedWhenAuthenticatedAndRegisteredAndProjectMembersAreSelected(): void
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions([ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED, ProjectUGroup::PROJECT_MEMBERS]);
    }
}
