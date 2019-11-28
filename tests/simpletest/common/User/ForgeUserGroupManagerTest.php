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

use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermissionChecker;

class User_ForgeUserGroupFactory_UpdateUserGroupTest extends TuleapTestCase
{

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
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->dao = \Mockery::spy(\UserGroupDao::class);
        $this->manager = new User_ForgeUserGroupManager(
            $this->dao,
            mock(SiteAdministratorPermissionChecker::class)
        );
    }

    public function itThrowsExceptionIfUGroupNotFound()
    {
        $this->expectException('User_UserGroupNotFoundException');

        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->shouldReceive('getForgeUGroup')->with(45)->andReturns(false);

        $this->manager->updateUserGroup($ugroup);
    }

    public function itReturnsTrueIfThereAreNoModifications()
    {
        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->shouldReceive('getForgeUGroup')->with(45)->andReturns(array(
            'group_id'    => 45,
            'name'        => 'people',
            'description' => 'to eat'
        ));

        $update = $this->manager->updateUserGroup($ugroup);
        $this->assertTrue($update);
    }

    public function itUpdates()
    {
        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->shouldReceive('getForgeUGroup')->with(45)->andReturns(array(
            'group_id'    => 45,
            'name'        => 'fish',
            'description' => 'to talk to'
        ));

        $this->dao->shouldReceive('updateForgeUGroup')->with(45, 'people', 'to eat')->once()->andReturns(true);

        $update = $this->manager->updateUserGroup($ugroup);
        $this->assertTrue($update);
    }

    public function itThrowsAnExceptionIfUGroupNameAlreadyExists()
    {
        $this->expectException('User_UserGroupNameInvalidException');
        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->shouldReceive('getForgeUGroup')->with(45)->andReturns(array(
            'group_id'    => 45,
            'name'        => 'fish',
            'description' => 'to talk to'
        ));

        $this->dao->shouldReceive('updateForgeUGroup')->with(45, 'people', 'to eat')->once()->andThrows(new User_UserGroupNameInvalidException());

        $update = $this->manager->updateUserGroup($ugroup);
        $this->assertTrue($update);
    }
}
