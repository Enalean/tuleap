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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ProjectHistoryDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembers;
use Tuleap\Project\Admin\ProjectMembers\EnsureUserCanManageProjectMembersStub;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Test\Builders\UserTestBuilder;

class AddProjectMemberTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var \PFUser
     */
    private $an_active_user;
    /**
     * @var \Project
     */
    private $an_active_project;
    /**
     * @var \Mockery\MockInterface|UserPermissionsDao
     */
    private $user_permissions_dao;
    /**
     * @var int
     */
    private $an_active_user_id;
    /**
     * @var int
     */
    private $an_active_project_id;
    /**
     * @var \EventManager|M\MockInterface
     */
    private $event_manager;
    /**
     * @var M\MockInterface|ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var M\MockInterface|\UGroupBinding
     */
    private $ugroup_binding;

    protected function setUp(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $this->an_active_user_id    = '101';
        $this->an_active_user       = new \PFUser(['user_id' => $this->an_active_user_id, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG]);
        $this->an_active_project_id = '202';
        $this->an_active_project    = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC]);
        $this->user_permissions_dao = M::mock(UserPermissionsDao::class);
        $this->user_permissions_dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->andReturnFalse()
            ->byDefault();
        $this->event_manager  = M::mock(\EventManager::class);
        $this->history_dao    = M::mock(ProjectHistoryDao::class);
        $this->ugroup_binding = M::mock(\UGroupBinding::class);
    }

    public function testItAddsUserAsProjectMember(): void
    {
        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($this->an_active_project)->build();

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->with($this->an_active_project_id, $this->an_active_user_id)->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', ['group_id' => $this->an_active_project_id, 'user_id' => $this->an_active_user_id, 'user_unix_name' => 'foo'])->once();
        $this->history_dao->shouldReceive('addHistory')->with($this->an_active_project, $project_admin, M::any(), 'added_user', 'foo', ['foo'])->once();
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject')->with($this->an_active_project)->once();

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($this->an_active_user, $this->an_active_project, $project_admin);
    }

    public function testItDoesntAddUserThatIsAlreadyAProjectMember(): void
    {
        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($this->an_active_project)->build();

        $this->user_permissions_dao->shouldReceive('isUserPartOfProjectMembers')->with($this->an_active_project_id, $this->an_active_user_id)->andReturnTrue();
        $this->user_permissions_dao->shouldNotReceive('addUserAsProjectMember');

        $this->expectException(AlreadyProjectMemberException::class);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($this->an_active_user, $this->an_active_project, $project_admin);
    }

    public function testItDoesntAddARestrictedUserToAPrivateWithoutRestrictedProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PRIVATE_WO_RESTRICTED]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->shouldNotReceive('addUserAsProjectMember');

        $this->expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItAddsARestrictedUserToAPublicProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->atLeast()->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any())->atLeast()->once();
        $this->history_dao->shouldReceive('addHistory')->atLeast()->once();
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject')->atLeast()->once();

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItAddsARestrictedUserToAPublicInclRestrictedProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC_UNRESTRICTED]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->atLeast()->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any())->atLeast()->once();
        $this->history_dao->shouldReceive('addHistory')->atLeast()->once();
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject')->atLeast()->once();

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItAddsARestrictedUserToAPrivateProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PRIVATE]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $project_admin = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->atLeast()->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any())->atLeast()->once();
        $this->history_dao->shouldReceive('addHistory')->atLeast()->once();
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject')->atLeast()->once();

        $this->buildAddProjectMember(EnsureUserCanManageProjectMembersStub::canManageMembers())
            ->addProjectMember($user, $project, $project_admin);
    }

    public function testItThrowsExceptionIfProjectAdminIsNotProjectAdmin(): void
    {
        $project_admin = UserTestBuilder::anActiveUser()->build();

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->never();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any())->never();
        $this->history_dao->shouldReceive('addHistory')->never();
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject')->never();

        $this->expectException(UserIsNotAllowedToManageProjectMembersException::class);

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
