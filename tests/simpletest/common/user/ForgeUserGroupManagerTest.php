<?php
/**
 * Copyright (c) Enalean, 2014 -2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class User_ForgeUserGroupFactory_UpdateUserGroupTest extends TuleapTestCase {

    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    protected $dao;

    /**
     * @var UserGroupDao
     */
    protected $factory;

    public function setUp()
    {
        $this->dao = mock('UserGroupDao');
        $this->manager = new User_ForgeUserGroupManager(
            $this->dao,
            mock('Tuleap\user\ForgeUserGroupPermission\SiteAdministratorPermissionChecker')
        );
    }

    public function itThrowsExceptionIfUGroupNotFound() {
        $this->expectException('User_UserGroupNotFoundException');

        $ugroup = new User_ForgeUgroup(45, 'people', 'to eat');

        stub($this->dao)->getForgeUGroup(45)->returns(false);

        $this->manager->updateUserGroup($ugroup);
    }

    public function itReturnsTrueIfThereAreNoModifications() {
        $ugroup = new User_ForgeUgroup(45, 'people', 'to eat');

        stub($this->dao)->getForgeUGroup(45)->returns(array(
            'group_id'    => 45,
            'name'        => 'people',
            'description' => 'to eat'
        ));

        $update = $this->manager->updateUserGroup($ugroup);
        $this->assertTrue($update);
    }

    public function itUpdates() {
        $ugroup = new User_ForgeUgroup(45, 'people', 'to eat');

        stub($this->dao)->getForgeUGroup(45)->returns(array(
            'group_id'    => 45,
            'name'        => 'fish',
            'description' => 'to talk to'
        ));

        stub($this->dao)->updateForgeUGroup(45, 'people', 'to eat')->once()->returns(true);

        $update = $this->manager->updateUserGroup($ugroup);
        $this->assertTrue($update);
    }

    public function itThrowsAnExceptionIfUGroupNameAlreadyExists() {
        $this->expectException('User_UserGroupNameInvalidException');
        $ugroup = new User_ForgeUgroup(45, 'people', 'to eat');

        stub($this->dao)->getForgeUGroup(45)->returns(array(
            'group_id'    => 45,
            'name'        => 'fish',
            'description' => 'to talk to'
        ));

        stub($this->dao)->updateForgeUGroup(45, 'people', 'to eat')->once()->throws(new User_UserGroupNameInvalidException());

        $update = $this->manager->updateUserGroup($ugroup);
        $this->assertTrue($update);
    }
}
