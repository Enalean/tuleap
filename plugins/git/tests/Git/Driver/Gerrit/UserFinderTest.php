<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../../../../include/constants.php';
require_once GIT_BASE_DIR.'/Git/Driver/Gerrit/UserFinder.class.php';

class Git_Driver_Gerrit_UserFinderTest extends TuleapTestCase {

    /** @var Git_Driver_Gerrit_UserFinder */
    protected $user_finder;

    /** @var PermissionsManager */
    protected $permissions_manager;

    /** @var UGroupManager */
    protected $ugroup_manager;

    /** @var GitRepository **/
    protected $repository;

    public function setUp() {
        parent::setUp();
        $this->permissions_manager = mock('PermissionsManager');
        $this->ugroup_manager      = mock('UGroupManager');
        $this->user_finder = new Git_Driver_Gerrit_UserFinder($this->permissions_manager, $this->ugroup_manager);
        $this->project_id = 666;
        $this->repository = mock('GitRepository');
        stub($this->repository)->getId()->returns(5);
        stub($this->repository)->getProjectId()->returns($this->project_id);

    }

    public function itReturnsNothingWhenNoGroupsHaveTheGivenPermission() {
        $permission_level = Git::PERM_WPLUS;


        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->returns(array());
        $this->assertArrayEmpty($this->user_finder->getUsersForPermission($permission_level, $this->repository));
    }

    public function itReturnsNothingWhenNoneOfTheGroupsHaveAnyMembers() {
        $permission_level = Git::PERM_WPLUS;


        $ugroup_id_list = array(99);
        $group1         = mock('Ugroup');

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(99)->returns($group1);
        stub($group1)->getUserLdapIds($this->project_id)->returns(array());
        $this->assertArrayEmpty($this->user_finder->getUsersForPermission($permission_level, $this->repository));
    }

    public function itReturnsMembersOfAGroup() {
        $permission_level = Git::PERM_WPLUS;


        $ugroup_id_dar = $this->ugroupIdDar(150);
        $group1        = mock('Ugroup');

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->once()->returns($ugroup_id_dar);
        stub($this->ugroup_manager)->getById(150)->returns($group1);

        $the_simpsons = array('Bart', 'Homer');
        stub($group1)->getUserLdapIds($this->project_id)->returns($the_simpsons);

        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, $the_simpsons);
    }

    public function itExcludesUsersThatDoNotHaveALdapId() {
        $permission_level = Git::PERM_WPLUS;

        $ugroup_id_dar = $this->ugroupIdDar(150);
        $group1        = mock('Ugroup');

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->once()->returns($ugroup_id_dar);
        stub($this->ugroup_manager)->getById(150)->returns($group1);

        $bart_ldap_id  = 'Bart';
        $homer_ldap_id = null;
        $the_simpsons  = array($bart_ldap_id, $homer_ldap_id);
        stub($group1)->getUserLdapIds($this->project_id)->returns($the_simpsons);

        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, array($bart_ldap_id));
    }

    public function itReturnsMembersOfAllGroups() {
        $permission_level = Git::PERM_WPLUS;


        $ugroup_id_list     = $this->ugroupIdDar(150, 152);
        $the_simpsons       = array('Bart', 'Homer');
        $the_mousqueteers   = array('Athos', 'Aramis');
        $group1             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($the_simpsons);
        $group2             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($the_mousqueteers);

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(152)->returns($group2);

        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, array_merge($the_mousqueteers, $the_simpsons));
    }

    public function itExcludesMembersOfRegisteredUsers_ToAvoidFloodingTheGerritConfig() {
        $permission_level = Git::PERM_WPLUS;


        $ugroup_id_list     = $this->ugroupIdDar(150, Ugroup::REGISTERED);
        $the_simpsons       = array('Bart', 'Homer');
        $registered_users   = array('Bart', 'Homer',
                                    'Athos', 'Aramis');
        $group1             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($the_simpsons);
        $group2             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($registered_users);

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(Ugroup::REGISTERED)->returns($group2);

        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, $the_simpsons);
    }

    public function itExcludesMembersOfAnonymousUsers_ToAvoidFloodingTheGerritConfig() {
        $permission_level = Git::PERM_WPLUS;

        $ugroup_id_list     = $this->ugroupIdDar(150, Ugroup::ANONYMOUS);
        $the_simpsons       = array('Bart', 'Homer');
        $anonymous_users    = array('Bart', 'Homer',
                                    'Athos', 'Aramis');
        $group1             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($the_simpsons);
        $group2             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($anonymous_users);

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(Ugroup::ANONYMOUS)->returns($group2);

        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, $the_simpsons);
    }

    public function itReturnsAUserOnlyOnceEvenIfHeExistInSeveralGroups() {
        $permission_level = Git::PERM_WPLUS;


        $ugroup_id_list     = $this->ugroupIdDar(150, 152);
        $superman           = array('ClarkKent');
        $comics_characters  = array('ClarkKent',
                                    'PeterParker');
        $group1             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($superman);
        $group2             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($comics_characters);

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->once()->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(150)->returns($group1);
        stub($this->ugroup_manager)->getById(152)->returns($group2);

        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, $comics_characters);
    }

    public function itDoesNotFailIfTheUGroupDoesNotExist() {
        $permission_level = Git::PERM_WPLUS;


        $ugroup_id_list = $this->ugroupIdDar(99);

        stub($this->permissions_manager)->getAuthorizedUgroups($this->repository->getId(), $permission_level)->returns($ugroup_id_list);
        stub($this->ugroup_manager)->getById(99)->returns(null);
        $this->assertArrayEmpty($this->user_finder->getUsersForPermission($permission_level, $this->repository));
    }

    public function itReturnsTheProjectAdministratorsWhenSpecialAdminPermission() {
        $permission_level = Git::SPECIAL_PERM_ADMIN;

        $superman           = array('ClarkKent');
        $group1             = stub('Ugroup')->getUserLdapIds($this->project_id)->returns($superman);

        stub($this->ugroup_manager)->getById()->returns($group1);
        $users = $this->user_finder->getUsersForPermission($permission_level, $this->repository);
        $this->assertEqual($users, $superman);
    }

    private function ugroupIdDar() {
        $ugroup_id_list = func_get_args();
        $result = array();
        foreach ($ugroup_id_list as $id) {
            $result[] = array('ugroup_id' => $id);
        }
        return TestHelper::argListToDar($result);

    }
}
?>
