<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

Mock::generate('PermissionsDao');

class PermissionsManagerTest extends TuleapTestCase
{

    function testDuplicatePermissionsPassParamters()
    {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');
        $ugroup_mapping   = array(110 => 210,
                                  120 => 220);
        $duplicate_type  = PermissionsDao::DUPLICATE_SAME_PROJECT;

        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, $duplicate_type, $ugroup_mapping));

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicatePermissions($source, $target, $permission_types, $ugroup_mapping, $duplicate_type);
    }

    function testDuplicateSameProjectShouldNotHaveUgroupMapping()
    {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');

        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, PermissionsDao::DUPLICATE_SAME_PROJECT, false));

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithStatic($source, $target, $permission_types);
    }

    function testDuplicateNewProjectShouldHaveUgroupMapping()
    {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');
        $ugroup_mapping   = array(110 => 210,
                                  120 => 220);

        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, PermissionsDao::DUPLICATE_NEW_PROJECT, $ugroup_mapping));

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithStaticMapping($source, $target, $permission_types, $ugroup_mapping);
    }

    function testDuplicateOtherProjectShouldNotHaveUgroupMapping()
    {
        $source           = 123;
        $target           = 234;
        $permission_types = array('STUFF_READ');

        $dao = new MockPermissionsDao();
        $dao->expectOnce('duplicatePermissions', array($source, $target, $permission_types, PermissionsDao::DUPLICATE_OTHER_PROJECT, false));

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithoutStatic($source, $target, $permission_types);
    }
}

class PermissionsManager_getAuthorizedUGroupIdsForProjectTest extends TuleapTestCase
{

    private $permissions_manager;
    private $project;
    private $permission_type;
    private $object_id;

    public function setUp()
    {
        parent::setUp();
        $this->project             = mock('Project');
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = partial_mock('PermissionsManager', array('getAuthorizedUgroups'));
        ForgeConfig::store();
    }

    public function tearDown()
    {
        parent::tearDown();
        ForgeConfig::restore();
    }

    public function itReturnsTheListOfStaticGroups()
    {
        $this->stubAuthorizedUgroups(array('ugroup_id' => 102));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(102));
    }

    public function itReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsAnonymous()
    {
        stub($this->project)->isPublic()->returns(false);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsAuthenticated()
    {
        stub($this->project)->isPublic()->returns(false);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itReturnsProjectMembersWhenProjectIsPrivateAndUGroupIsRegistered()
    {
        stub($this->project)->isPublic()->returns(false);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function itReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsRegisteredUsers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function itReturnsRegisteredUsersWhenPlatformIsRegularProjectIsPublicAndUGroupIsAuthenticatedUsers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function itReturnsProjectMembersWhenPlatformIsRegularProjectIsPublicAndUGroupIsProjectMembers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::PROJECT_MEMBERS));
    }




    public function itReturnsAnonymousWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::ANONYMOUS));
    }

    public function itReturnsRegisteredWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsAuthenticated()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function itReturnsRegisteredWhenPlatformIsAllowedToAnonymousProjectIsPublicAndUGroupIsRegistered()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }





    public function itReturnsRegisteredWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function itReturnsRegisteredWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsRegistered()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->project)->isPublic()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::REGISTERED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::REGISTERED));
    }

    public function itReturnsAuthenticatedWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->allowsRestricted()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::ANONYMOUS));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itReturnsAuthenticatedWhenPlatformIsRestrictedProjectIsPublicAndUGroupIsAuthenticated()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->allowsRestricted()->returns(true);
        $this->stubAuthorizedUgroups(array('ugroup_id' => ProjectUGroup::AUTHENTICATED));

        $this->assertAuthorizedUGroupIdsForProjectEqual(array(ProjectUGroup::AUTHENTICATED));
    }



    private function stubAuthorizedUgroups($groups)
    {
        stub($this->permissions_manager)->getAuthorizedUgroups($this->object_id, $this->permission_type, false)->returnsDar($groups);
    }

    private function assertAuthorizedUGroupIdsForProjectEqual($groups)
    {
        $this->assertEqual($this->permissions_manager->getAuthorizedUGroupIdsForProject($this->project, $this->object_id, $this->permission_type), $groups);
    }
}

abstract class PermissionsManager_savePermissionsTest extends TuleapTestCase
{
    protected $permissions_manager;
    protected $project;
    protected $permission_type;
    protected $object_id;
    protected $permissions_dao;
    protected $project_id;

    public function setUp()
    {
        parent::setUp();
        $this->project_id          = 404;
        $this->project             = stub('Project')->getId()->returns($this->project_id);
        $this->permissions_dao     = mock('PermissionsDao');
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = new PermissionsManager($this->permissions_dao);
        $GLOBALS['Response']       = mock('Response');
        ForgeConfig::store();
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($GLOBALS['Response']);
        ForgeConfig::restore();
    }

    protected function expectPermissionsOnce($ugroup)
    {
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, $ugroup)->once();
    }

    protected function savePermissions($ugroups)
    {
        $this->permissions_manager->savePermissions($this->project, $this->object_id, $this->permission_type, $ugroups);
    }
}

class PermissionsManager_savePermissions_CommonTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
    }

    public function itSavesTheValueForStaticUGroupId()
    {
        expect($this->permissions_dao)->clearPermission($this->permission_type, $this->object_id)->once();
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->once();
        expect($this->permissions_dao)->addHistory($this->project_id, $this->permission_type, $this->object_id)->once();

        $this->savePermissions(array(104));
    }

    public function itSavesTheValueForSeveralStaticUGroupIds()
    {
        expect($this->permissions_dao)->clearPermission($this->permission_type, $this->object_id)->once();
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 201)->at(1);
        expect($this->permissions_dao)->addHistory($this->project_id, $this->permission_type, $this->object_id)->once();

        $this->savePermissions(array(104, 201));
    }

    public function itSavesOnlyOneInstanceOfGroups()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 201)->at(1);

        $this->savePermissions(array(104, 201, 104));
    }
}

class PermissionsManager_savePermissions_PlatformForAnonymousProjectPrivateTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->project)->isPublic()->returns(false);
    }

    public function itSavesProjectMembersWhenSelectedAnonymous()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS));
    }

    public function itSavesProjectMembersWhenSelectedAuthenticated()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itSavesProjectMembersSelectedRegistered()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::REGISTERED));
    }

    public function itSavesProjectMembersSelectedProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itSavesProjectMembersAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_MEMBERS)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::REGISTERED, ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_ADMIN, 104));
    }
}

class PermissionsManager_savePermissions_PlatformForAnonymousProjectPublicTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->project)->isPublic()->returns(true);
    }

    public function itSavesAnonymousSelectedAnonymous()
    {
        $this->expectPermissionsOnce(ProjectUGroup::ANONYMOUS);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS));
    }

    public function itSavesRegisteredWhenSelectedAuthenticated()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itSavesRegisteredWhenSelectedRegistered()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED));
    }

    public function itSavesProjectMembersWhenSelectedProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itSavesOnlyAnonymousWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::ANONYMOUS);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesOnlyRegisteredWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesOnlyRegisteredWhenPresentWithAuthenticatedProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesMembersAndStaticWhenPresentWithMembersProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_MEMBERS)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesAdminsAndStaticWhenPresentWithProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_ADMIN)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesSVNAdminWikiAdminAndStatic()
    {
        expect($this->permissions_dao)->addPermission()->count(3);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::SVN_ADMIN)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::WIKI_ADMIN)->at(1);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(2);

        $this->savePermissions(array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, 104));
    }

    public function itSavesProjectMembersWhenSVNAdminWikiAdminAndProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, ProjectUGroup::PROJECT_MEMBERS));
    }
}

class PermissionsManager_savePermissions_PlatformRegularProjectPrivateTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->project)->isPublic()->returns(false);
    }

    public function itSavesProjectMembersWhenSelectedAnonymous()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS));
    }

    public function itSavesProjectMembersWhenSelectedAuthenticated()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itSavesProjectMembersSelectedRegistered()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::REGISTERED));
    }

    public function itSavesProjectMembersSelectedProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itSavesProjectMembersProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_MEMBERS)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::REGISTERED, ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_ADMIN, 104));
    }
}

class PermissionsManager_savePermissions_PlatformForRegularProjectPublicTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->project)->isPublic()->returns(true);
    }

    public function itSavesAnonymousSelectedAnonymous()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS));
    }

    public function itSavesRegisteredWhenSelectedAuthenticated()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itSavesRegisteredWhenSelectedRegistered()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED));
    }

    public function itSavesProjectMembersWhenSelectedProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS));
    }
}

class PermissionsManager_savePermissions_PlatformForRestrictedProjectUnrestrictedTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->allowsRestricted()->returns(true);
    }

    public function itSavesAuthenticatedSelectedAnonymous()
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS));
    }

    public function itSavesAuthenticatedWhenSelectedAuthenticated()
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itSavesRegisteredWhenSelectedRegistered()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED));
    }

    public function itSavesProjectMembersWhenSelectedProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itSavesOnlyAuthenticatedWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesOnlyRegisteredWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesOnlyAuthenticatedWhenPresentWithAuthenticatedProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesMembersAndStaticWhenPresentWithMembersProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_MEMBERS)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesAdminsAndStaticWhenPresentWithProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_ADMIN)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesSVNAdminWikiAdminAndStatic()
    {
        expect($this->permissions_dao)->addPermission()->count(3);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::SVN_ADMIN)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::WIKI_ADMIN)->at(1);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(2);

        $this->savePermissions(array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, 104));
    }

    public function itSavesProjectMembersWhenSVNAdminWikiAdminAndProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itSavesAuthenticatedWhenAuthenticatedAndRegisteredAndProjectMembersAreSelected()
    {
        $this->expectPermissionsOnce(ProjectUGroup::AUTHENTICATED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED, ProjectUGroup::AUTHENTICATED, ProjectUGroup::PROJECT_MEMBERS));
    }
}

class PermissionsManager_savePermissions_PlatformForRestrictedProjectPublicTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->allowsRestricted()->returns(false);
    }

    public function itSavesRegisteredSelectedAnonymous()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS));
    }

    public function itSavesRegisteredWhenSelectedAuthenticated()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED));
    }

    public function itSavesRegisteredWhenSelectedRegistered()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::REGISTERED));
    }

    public function itSavesProjectMembersWhenSelectedProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS));
    }

    public function itSavesOnlyRegisteredWhenPresentWithOtherProjectMembersProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesOnlyRegisteredWhenPresentWithAuthenticatedProjectAdminsAndStaticGroup()
    {
        $this->expectPermissionsOnce(ProjectUGroup::REGISTERED);

        $this->savePermissions(array(ProjectUGroup::AUTHENTICATED, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesMembersAndStaticWhenPresentWithMembersProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_MEMBERS)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesAdminsAndStaticWhenPresentWithProjectAdminsAndStaticGroup()
    {
        expect($this->permissions_dao)->addPermission()->count(2);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::PROJECT_ADMIN)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(1);

        $this->savePermissions(array(ProjectUGroup::PROJECT_ADMIN, 104));
    }

    public function itSavesSVNAdminWikiAdminAndStatic()
    {
        expect($this->permissions_dao)->addPermission()->count(3);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::SVN_ADMIN)->at(0);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, ProjectUGroup::WIKI_ADMIN)->at(1);
        expect($this->permissions_dao)->addPermission($this->permission_type, $this->object_id, 104)->at(2);

        $this->savePermissions(array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, 104));
    }

    public function itSavesProjectMembersWhenSVNAdminWikiAdminAndProjectMembers()
    {
        $this->expectPermissionsOnce(ProjectUGroup::PROJECT_MEMBERS);

        $this->savePermissions(array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, ProjectUGroup::PROJECT_MEMBERS));
    }
}

class PermissionsManager_savePermissions_SaveDaoTest extends PermissionsManager_savePermissionsTest
{

    public function setUp()
    {
        parent::setUp();
        stub($this->permissions_dao)->getDa()->returns(mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class));
    }

    public function itThrowsExceptionWhenClearFailed()
    {
        stub($this->permissions_dao)->clearPermission()->returns(false);

        $this->expectException('PermissionDaoException');

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS, 104));
    }

    public function itThrowsExceptionWhenAddFailed()
    {
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returnsAt(1, false);

        $this->expectException('PermissionDaoException');

        $this->savePermissions(array(ProjectUGroup::PROJECT_MEMBERS, 104));
    }
}

class PermissionsManager_savePermissions_FeebackOverlapingTest extends PermissionsManager_savePermissionsTest
{

    private $normalizer;

    public function setUp()
    {
        parent::setUp();
        $this->normalizer = new PermissionsNormalizer();
        stub($this->permissions_dao)->clearPermission()->returns(true);
        stub($this->permissions_dao)->addPermission()->returns(true);
    }

    public function itInformsThatProjectMembersIsSavedWhenSVNAdminWikiAdminAndProjectMembers()
    {
        $override_collection = new PermissionsNormalizerOverrideCollection();

        $this->normalizer->getNormalizedUGroupIds(
            $this->project,
            array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN, ProjectUGroup::PROJECT_MEMBERS),
            $override_collection
        );

        $this->assertEqual($override_collection->getOverrideBy(ProjectUGroup::PROJECT_MEMBERS), array(ProjectUGroup::SVN_ADMIN, ProjectUGroup::WIKI_ADMIN));
    }

    public function itInformsThatAnonymousOverlapProjectMembers()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->project)->isPublic()->returns(true);

        $override_collection = new PermissionsNormalizerOverrideCollection();

        $this->normalizer->getNormalizedUGroupIds(
            $this->project,
            array(ProjectUGroup::ANONYMOUS, ProjectUGroup::PROJECT_MEMBERS),
            $override_collection
        );

        $this->assertEqual($override_collection->getOverrideBy(ProjectUGroup::ANONYMOUS), array(ProjectUGroup::PROJECT_MEMBERS));
    }
}
