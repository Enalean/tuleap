<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

class User_ForgeUserGroupFactory_BaseTest extends TuleapTestCase
{

    /**
     * @var UserGroupDao
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
        $this->dao     = \Mockery::spy(\UserGroupDao::class);
        $this->factory = new User_ForgeUserGroupFactory($this->dao);
    }
}

class User_ForgeUserGroupFactory_GetAllTest extends User_ForgeUserGroupFactory_BaseTest
{

    public function itReturnsEmptyArrayIfNoResultsInDb()
    {
        $this->dao->shouldReceive('getAllForgeUGroups')->andReturns(false);
        $all = $this->factory->getAllForgeUserGroups();

        $this->assertEqual(0, count($all));
    }

    public function itReturnsArrayOfUserGroups()
    {
        $data = array(
            array(
                'ugroup_id'     => 10,
                'name'          => 'thom thumbs',
                'description'   => 'whatever',
            ),
             array(
                'ugroup_id'     => 12,
                'name'          => 'wild rover',
                'description'   => 'whatttttt',
            ),
            array(
                'ugroup_id'     => 15,
                'name'          => 'nb,er',
                'description'   => 'whatever gbgf',
            ),
        );

        $this->dao->shouldReceive('getAllForgeUGroups')->andReturns($data);
        $all = $this->factory->getAllForgeUserGroups();

        $this->assertEqual(3, count($all));

        $first  = $all[0];
        $second = $all[1];
        $third  = $all[2];

        $this->assertIsA($first, 'User_ForgeUGroup');
        $this->assertIsA($second, 'User_ForgeUGroup');
        $this->assertIsA($third, 'User_ForgeUGroup');

        $this->assertEqual($first->getId(), 10);
        $this->assertEqual($second->getId(), 12);
        $this->assertEqual($third->getId(), 15);

        $this->assertEqual($first->getName(), 'thom thumbs');
        $this->assertEqual($second->getName(), 'wild rover');
        $this->assertEqual($third->getName(), 'nb,er');

        $this->assertEqual($first->getDescription(), 'whatever');
        $this->assertEqual($second->getDescription(), 'whatttttt');
        $this->assertEqual($third->getDescription(), 'whatever gbgf');
    }
}

class User_ForgeUserGroupFactory_GetUGroupTest extends User_ForgeUserGroupFactory_BaseTest
{

    public function itGetsForgeUGroup()
    {
        $user_group_id = 105;
        $row = array(
            'ugroup_id'   => 105,
            'name'        => 'my name',
            'description' => 'user group'
        );

        $this->dao->shouldReceive('getForgeUGroup')->with($user_group_id)->andReturns($row);

        $ugroup = $this->factory->getForgeUserGroupById($user_group_id);

        $this->assertEqual($ugroup->getId(), 105);
        $this->assertEqual($ugroup->getName(), 'my name');
        $this->assertEqual($ugroup->getDescription(), 'user group');
    }

    public function itThrowsExceptionIfUGroupNotFound()
    {
        $this->expectException('User_UserGroupNotFoundException');

        $user_group_id = 105;

        $this->dao->shouldReceive('getForgeUGroup')->with($user_group_id)->andReturns(false);

        $this->factory->getForgeUserGroupById($user_group_id);
    }
}

class User_ForgeUserGroupFactory_cCeateForgeUGroupTest extends User_ForgeUserGroupFactory_BaseTest
{

    public function itCreatesForgeUGroup()
    {
        $name        = 'my group';
        $description = 'my desc';
        $id          = 102;

        $this->dao->shouldReceive('createForgeUGroup')->with($name, $description)->andReturns($id);

        $ugroup = $this->factory->createForgeUGroup($name, $description);

        $this->assertEqual($ugroup->getId(), 102);
        $this->assertEqual($ugroup->getName(), 'my group');
        $this->assertEqual($ugroup->getDescription(), 'my desc');
    }

    public function itThrowsExceptionIfUGroupNameExists()
    {
        $this->expectException('User_UserGroupNameInvalidException');

        $name        = 'my group';
        $description = 'my desc';

        $this->dao->shouldReceive('createForgeUGroup')->with($name, $description)->andThrows(new User_UserGroupNameInvalidException());

        $this->factory->createForgeUGroup($name, $description);
    }
}
