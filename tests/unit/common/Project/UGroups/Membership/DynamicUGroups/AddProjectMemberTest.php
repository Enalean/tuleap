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

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use ForgeAccess;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembers;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembersStub;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Test\Builders\UserTestBuilder;

final class AddProjectMemberTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private \PFUser $an_active_user;
    private \Project $an_active_project;
    private UserPermissionsDao&MockObject $user_permissions_dao;
    private string $an_active_user_id;
    private string $an_active_project_id;
    private \EventManager&MockObject $event_manager;
    private ProjectHistoryDao&MockObject $history_dao;
    private \UGroupBinding&MockObject $ugroup_binding;

    protected function setUp(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->an_active_user_id    = '101';
        $this->an_active_user       = new \PFUser(['user_id' => $this->an_active_user_id, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG]);
        $this->an_active_project_id = '202';
        $this->an_active_project    = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC]);
        $this->user_permissions_dao = $this->createMock(UserPermissionsDao::class);
        $this->event_manager        = $this->createMock(\EventManager::class);
        $this->history_dao          = $this->createMock(ProjectHistoryDao::class);
        $this->ugroup_binding       = $this->createMock(\UGroupBinding::class);
    }

    public function testItAddsUserAsProjectMember(): void
    {
        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($this->an_active_project)->build();

        $this->user_permissions_dao->expects(self::once())->method('addUserAsProjectMember')->with($this->an_active_project_id, $this->an_active_user_id);
        $this->event_manager->expects(self::once())->method('processEvent')->with('project_admin_add_user', ['group_id' => $this->an_active_project_id, 'user_id' => $this->an_active_user_id, 'user_unix_name' => 'foo']);
        $this->history_dao->expects(self::once())->method('addHistory')->with($this->an_active_project, $project_admin, self::anything(), 'added_user', 'foo', ['foo']);
        $this->ugroup_binding->expects(self::once())->method('reloadUgroupBindingInProject')->with($this->an_active_project);
        $this->user_permissions_dao
            ->method('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->willReturn(false);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($this->an_active_user, $this->an_active_project, $project_admin);
    }

    public function testItDoesntAddUserThatIsAlreadyAProjectMember(): void
    {
        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($this->an_active_project)->build();

        $this->user_permissions_dao->method('isUserPartOfProjectMembers')->with($this->an_active_project_id, $this->an_active_user_id)->willReturn(true);
        $this->user_permissions_dao->expects(self::never())->method('addUserAsProjectMember');

        self::expectException(AlreadyProjectMemberException::class);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($this->an_active_user, $this->an_active_project, $project_admin);
    }

    public function testItDoesntAddARestrictedUserToAPrivateWithoutRestrictedProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PRIVATE_WO_RESTRICTED]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->expects(self::never())->method('addUserAsProjectMember');

        self::expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItAddsARestrictedUserToAPublicProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->expects(self::atLeastOnce())->method('addUserAsProjectMember');
        $this->event_manager->expects(self::atLeastOnce())->method('processEvent')->with('project_admin_add_user', self::anything());
        $this->history_dao->expects(self::atLeastOnce())->method('addHistory');
        $this->ugroup_binding->expects(self::atLeastOnce())->method('reloadUgroupBindingInProject');
        $this->user_permissions_dao
            ->method('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->willReturn(false);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItAddsARestrictedUserToAPublicInclRestrictedProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC_UNRESTRICTED]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->expects(self::atLeastOnce())->method('addUserAsProjectMember');
        $this->event_manager->expects(self::atLeastOnce())->method('processEvent')->with('project_admin_add_user', self::anything());
        $this->history_dao->expects(self::atLeastOnce())->method('addHistory');
        $this->ugroup_binding->expects(self::atLeastOnce())->method('reloadUgroupBindingInProject');
        $this->user_permissions_dao
            ->method('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->willReturn(false);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItAddsARestrictedUserToAPrivateProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PRIVATE]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->expects(self::atLeastOnce())->method('addUserAsProjectMember');
        $this->event_manager->expects(self::atLeastOnce())->method('processEvent')->with('project_admin_add_user', self::anything());
        $this->history_dao->expects(self::atLeastOnce())->method('addHistory');
        $this->ugroup_binding->expects(self::atLeastOnce())->method('reloadUgroupBindingInProject');
        $this->user_permissions_dao
            ->method('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->willReturn(false);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItThrowsExceptionIfProjectAdminIsNotProjectAdmin(): void
    {
        $project_admin = UserTestBuilder::anActiveUser()->build();

        $this->user_permissions_dao->expects(self::never())->method('addUserAsProjectMember');
        $this->event_manager->expects(self::never())->method('processEvent')->with('project_admin_add_user', self::anything());
        $this->history_dao->expects(self::never())->method('addHistory');
        $this->ugroup_binding->expects(self::never())->method('reloadUgroupBindingInProject');
        $this->user_permissions_dao
            ->method('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->willReturn(false);

        self::expectException(UserIsNotAllowedToManageProjectMembersException::class);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::cannotManageMembers())
            ->addProjectMember($this->an_active_user, $this->an_active_project, $project_admin);
    }

    private function buildAddProjectMember(
        EnsureUserCanManageProjectMembers $members_manager_checker,
    ): AddProjectMember {
        return new AddProjectMember(
            $this->user_permissions_dao,
            $this->event_manager,
            $this->history_dao,
            $this->ugroup_binding,
            $members_manager_checker,
        );
    }
}
