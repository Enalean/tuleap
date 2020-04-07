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
use PHPUnit\Framework\TestCase;
use ProjectHistoryDao;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UserPermissionsDao;

class AddProjectMemberTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    /**
     * @var AddProjectMember
     */
    private $add_project_member;
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
     * @var M\MockInterface|\UserManager
     */
    private $user_manager;
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
        $this->an_active_user_id = '101';
        $this->an_active_user = new \PFUser(['user_id' => $this->an_active_user_id, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'unix_status' => \PFUser::UNIX_STATUS_NO_UNIX_ACCOUNT]);
        $this->an_active_project_id = '202';
        $this->an_active_project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC]);
        $this->user_permissions_dao = M::mock(UserPermissionsDao::class);
        $this->user_permissions_dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with($this->an_active_project_id, $this->an_active_user_id)
            ->andReturnFalse()
            ->byDefault();
        $this->user_manager       = M::mock(\UserManager::class);
        $this->event_manager      = M::mock(\EventManager::class);
        $this->history_dao        = M::mock(ProjectHistoryDao::class);
        $this->ugroup_binding     = M::mock(\UGroupBinding::class);
        $this->add_project_member = new AddProjectMember($this->user_permissions_dao, $this->user_manager, $this->event_manager, $this->history_dao, $this->ugroup_binding);
    }

    public function testItAddsUserAsProjectMember(): void
    {
        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->with($this->an_active_project_id, $this->an_active_user_id)->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', ['group_id' => $this->an_active_project_id, 'user_id' => $this->an_active_user_id, 'user_unix_name' => 'foo'])->once();
        $this->history_dao->shouldReceive('groupAddHistory')->with('added_user', 'foo', $this->an_active_project_id, ['foo'])->once();
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject')->with($this->an_active_project)->once();

        $this->add_project_member->addProjectMember($this->an_active_user, $this->an_active_project);
    }

    public function testItDoesntAddUserThatIsAlreadyAProjectMember(): void
    {
        $this->user_permissions_dao->shouldReceive('isUserPartOfProjectMembers')->with($this->an_active_project_id, $this->an_active_user_id)->andReturnTrue();
        $this->user_permissions_dao->shouldNotReceive('addUserAsProjectMember');

        $this->expectException(AlreadyProjectMemberException::class);

        $this->add_project_member->addProjectMember($this->an_active_user, $this->an_active_project);
    }

    public function testItDoesntAddARestrictedUserToAPrivateWithoutRestrictedProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PRIVATE_WO_RESTRICTED]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $this->user_permissions_dao->shouldNotReceive('addUserAsProjectMember');

        $this->expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);

        $this->add_project_member->addProjectMember($user, $project);
    }

    public function testItAddsARestrictedUserToAPublicProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember');
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any());
        $this->history_dao->shouldReceive('groupAddHistory');
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject');

        $this->add_project_member->addProjectMember($user, $project);
    }

    public function testItAddsARestrictedUserToAPublicInclRestrictedProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PUBLIC_UNRESTRICTED]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember');
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any());
        $this->history_dao->shouldReceive('groupAddHistory');
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject');

        $this->add_project_member->addProjectMember($user, $project);
    }

    public function testItAddsARestrictedUserToAPrivateProject(): void
    {
        \ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $project = new \Project(['group_id' => $this->an_active_project_id, 'access' => \Project::ACCESS_PRIVATE]);
        $user    = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG]);

        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember');
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any());
        $this->history_dao->shouldReceive('groupAddHistory');
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject');

        $this->add_project_member->addProjectMember($user, $project);
    }

    public function testItGeneratesAUnixIdForNewProjectMembersWithUnixAccountButNoUnixId(): void
    {
        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->with($this->an_active_project_id, $this->an_active_user_id)->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any());
        $this->history_dao->shouldReceive('groupAddHistory');
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject');

        $user = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'unix_status' => \PFUser::UNIX_STATUS_ACTIVE, 'unix_uid' => '']);

        $this->user_manager->shouldReceive('assignNextUnixUid')->with($user)->once()->ordered();
        $this->user_manager->shouldReceive('updateDb')->with($user)->once()->ordered();

        $this->add_project_member->addProjectMember($user, $this->an_active_project);
    }

    public function testItDoesntGeneratesAUnixIdForNewProjectMembersWithUnixAccountThatAlreadyHaveAnUnixId(): void
    {
        $this->user_permissions_dao->shouldReceive('addUserAsProjectMember')->with($this->an_active_project_id, $this->an_active_user_id)->once();
        $this->event_manager->shouldReceive('processEvent')->with('project_admin_add_user', M::any());
        $this->history_dao->shouldReceive('groupAddHistory');
        $this->ugroup_binding->shouldReceive('reloadUgroupBindingInProject');

        $user = new \PFUser(['user_id' => $this->an_active_user_id, 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'unix_status' => \PFUser::UNIX_STATUS_ACTIVE, 'unix_uid' => '202010']);

        $this->user_manager->shouldNotReceive('assignNextUnixUid');
        $this->user_manager->shouldNotReceive('updateDb');

        $this->add_project_member->addProjectMember($user, $this->an_active_project);
    }
}
