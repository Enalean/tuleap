<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Admin\ProjectUGroup\ApproveProjectAdministratorRemoval;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\Admin\ProjectUGroup\CannotRemoveLastProjectAdministratorException;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesWikiAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerWikiAdmin;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class DynamicUGroupMembersUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\MockInterface|ProjectMemberAdder
     */
    private $project_member_adder;
    /**
     * @var DynamicUGroupMembersUpdater
     */
    private $updater;

    protected function setUp(): void
    {
        $this->dao                  = Mockery::mock(UserPermissionsDao::class);
        $this->project_member_adder = Mockery::mock(ProjectMemberAdder::class);
        $this->event_manager        = Mockery::mock(\EventManager::class);

        $this->updater = new DynamicUGroupMembersUpdater(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->project_member_adder,
            $this->event_manager
        );
    }

    public function testTheLastProjectAdministratorCannotBeRemoved(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $admin_ugroup = Mockery::mock(ProjectUGroup::class);
        $admin_ugroup->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $this->dao->shouldReceive('isThereOtherProjectAdmin')->andReturns(false);
        $this->expectException(CannotRemoveLastProjectAdministratorException::class);

        $this->updater->removeUser($project, $admin_ugroup, $user);
    }

    public function testAProjectAdministratorCanBeRemovedWhenItIsNotTheLastOne(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $admin_ugroup = Mockery::mock(ProjectUGroup::class);
        $admin_ugroup->shouldReceive('getId')->andReturns(ProjectUGroup::PROJECT_ADMIN);
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $this->dao->shouldReceive('isThereOtherProjectAdmin')->andReturns(true);
        $this->event_manager->shouldReceive('processEvent')
            ->with(Mockery::type(ApproveProjectAdministratorRemoval::class))->once();
        $this->dao->shouldReceive('removeUserFromProjectAdmin')->once();
        $this->event_manager->shouldReceive('processEvent')
            ->with(Mockery::type(UserIsNoLongerProjectAdmin::class))->once();

        $this->updater->removeUser($project, $admin_ugroup, $user);
    }

    public function testRemoveWikiAdministratorDispatchesAnEvent(): void
    {
        $user        = $this->getRegularUserMock();
        $project     = $this->getPublicProjectMock();
        $wiki_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::WIKI_ADMIN]
        );

        $this->dao->shouldReceive('removeUserFromWikiAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserIsNoLongerWikiAdmin::class));

        $this->updater->removeUser($project, $wiki_admins, $user);
    }

    public function testRemoveForumAdministratorDispatchesAnEvent(): void
    {
        $user         = $this->getRegularUserMock();
        $project      = $this->getPublicProjectMock();
        $forum_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::FORUM_ADMIN]
        );

        $this->dao->shouldReceive('removeUserFromForumAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserIsNoLongerForumAdmin::class));

        $this->updater->removeUser($project, $forum_admins, $user);
    }

    public function testRemoveNewsEditorDispatchesAnEvent(): void
    {
        $user         = $this->getRegularUserMock();
        $project      = $this->getPublicProjectMock();
        $news_editors = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::NEWS_WRITER]
        );

        $this->dao->shouldReceive('removeUserFromNewsEditor')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserIsNoLongerNewsWriter::class));

        $this->updater->removeUser($project, $news_editors, $user);
    }

    public function testRemoveNewsAdministratorDispatchesAnEvent(): void
    {
        $user        = $this->getRegularUserMock();
        $project     = $this->getPublicProjectMock();
        $news_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::NEWS_ADMIN]
        );

        $this->dao->shouldReceive('removeUserFromNewsAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserIsNoLongerNewsAdministrator::class));

        $this->updater->removeUser($project, $news_admins, $user);
    }

    public function testAddUserThrowsWhenProjectExcludesRestrictedAndUserIsRestricted(): void
    {
        $user    = Mockery::mock(PFUser::class, ['isRestricted' => true, 'getId' => 217]);
        $project = Mockery::mock(
            Project::class,
            ['getAccess' => Project::ACCESS_PRIVATE_WO_RESTRICTED, 'getID' => 101]
        );
        $ugroup  = Mockery::mock(ProjectUGroup::class, ['getProject' => $project]);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->expectException(CannotAddRestrictedUserToProjectNotAllowingRestricted::class);

        $this->updater->addUser($project, $ugroup, $user);
    }

    public function testAddUserToProjectAdministratorsAlsoAddsToProjectMembers(): void
    {
        $this->setRegularForgeAccess();
        $user           = $this->getRegularUserMock();
        $project        = $this->getPublicProjectMock();
        $project_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::PROJECT_ADMIN]
        );
        $this->dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with(101, 217)
            ->andReturnFalse();
        $this->project_member_adder->shouldReceive('addProjectMember')->with($user, $project);
        $this->dao->shouldReceive('addUserAsProjectAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserBecomesProjectAdmin::class));

        $this->updater->addUser($project, $project_admins, $user);
    }

    public function testAddUserToProjectAdministratorsDoesNotAddToProjectMembersWhenUserAlreadyIs(): void
    {
        $this->setRegularForgeAccess();
        $user           = $this->getRegularUserMock();
        $project        = $this->getPublicProjectMock();
        $project_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::PROJECT_ADMIN]
        );
        $this->dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with(101, 217)
            ->andReturnTrue();

        $this->project_member_adder->shouldNotReceive('addProjectMember');
        $this->dao->shouldReceive('addUserAsProjectAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserBecomesProjectAdmin::class));

        $this->updater->addUser($project, $project_admins, $user);
    }

    public function testAddUserToWikiAdministratorsAlsoAddsToProjectMembers(): void
    {
        $this->setRegularForgeAccess();
        $user        = $this->getRegularUserMock();
        $project     = $this->getPublicProjectMock();
        $wiki_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::WIKI_ADMIN]
        );
        $this->dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with(101, 217)
            ->andReturnFalse();
        $this->project_member_adder->shouldReceive('addProjectMember')->with($user, $project);
        $this->dao->shouldReceive('addUserAsWikiAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserBecomesWikiAdmin::class));

        $this->updater->addUser($project, $wiki_admins, $user);
    }

    public function testAddUserToForumAdministratorsAlsoAddsToProjectMembers(): void
    {
        $this->setRegularForgeAccess();
        $user         = $this->getRegularUserMock();
        $project      = $this->getPublicProjectMock();
        $forum_admins = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::FORUM_ADMIN]
        );
        $this->dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with(101, 217)
            ->andReturnFalse();
        $this->project_member_adder->shouldReceive('addProjectMember')->with($user, $project);
        $this->dao->shouldReceive('addUserAsForumAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserBecomesForumAdmin::class));

        $this->updater->addUser($project, $forum_admins, $user);
    }

    public function testAddUserToNewsEditorsAlsoAddsToProjectMembers(): void
    {
        $this->setRegularForgeAccess();
        $user        = $this->getRegularUserMock();
        $project     = $this->getPublicProjectMock();
        $new_editors = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::NEWS_WRITER]
        );
        $this->dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with(101, 217)
            ->andReturnFalse();
        $this->project_member_adder->shouldReceive('addProjectMember')->with($user, $project);
        $this->dao->shouldReceive('addUserAsNewsEditor')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserBecomesNewsWriter::class));

        $this->updater->addUser($project, $new_editors, $user);
    }

    public function testAddUserToNewsAdministratorsAlsoAddsToProjectMembers(): void
    {
        $this->setRegularForgeAccess();
        $user                = $this->getRegularUserMock();
        $project             = $this->getPublicProjectMock();
        $news_administrators = Mockery::mock(
            ProjectUGroup::class,
            ['getProject' => $project, 'getId' => ProjectUGroup::NEWS_ADMIN]
        );
        $this->dao
            ->shouldReceive('isUserPartOfProjectMembers')
            ->with(101, 217)
            ->andReturnFalse();
        $this->project_member_adder->shouldReceive('addProjectMember')->with($user, $project);
        $this->dao->shouldReceive('addUserAsNewsAdmin')->once()->with(101, 217);
        $this->event_manager
            ->shouldReceive('processEvent')
            ->once()
            ->with(Mockery::type(UserBecomesNewsAdministrator::class));

        $this->updater->addUser($project, $news_administrators, $user);
    }

    private function getPublicProjectMock(): Project
    {
        $project = Mockery::mock(
            Project::class,
            ['getAccess' => Project::ACCESS_PUBLIC, 'getID' => 101]
        );
        return $project;
    }

    private function getRegularUserMock(): PFUser
    {
        $user = Mockery::mock(PFUser::class, ['isRestricted' => false, 'getId' => 217]);
        return $user;
    }

    private function setRegularForgeAccess(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
    }
}
