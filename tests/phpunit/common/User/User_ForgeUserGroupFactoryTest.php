<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_ForgeUserGroupFactoryTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var UserGroupDao
     */
    protected $dao;

    /**
     * @var UserGroupDao
     */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao     = \Mockery::spy(\UserGroupDao::class);
        $this->factory = new User_ForgeUserGroupFactory($this->dao);
    }

    public function testItReturnsEmptyArrayIfNoResultsInDb(): void
    {
        $this->dao->shouldReceive('getAllForgeUGroups')->andReturns(false);
        $all = $this->factory->getAllForgeUserGroups();

        $this->assertCount(0, $all);
    }

    public function testItReturnsArrayOfUserGroups(): void
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

        $this->assertCount(3, $all);

        $first  = $all[0];
        $second = $all[1];
        $third  = $all[2];

        $this->assertInstanceOf(\User_ForgeUGroup::class, $first);
        $this->assertInstanceOf(\User_ForgeUGroup::class, $second);
        $this->assertInstanceOf(\User_ForgeUGroup::class, $third);

        $this->assertEquals(10, $first->getId());
        $this->assertEquals(12, $second->getId());
        $this->assertEquals(15, $third->getId());

        $this->assertEquals('thom thumbs', $first->getName());
        $this->assertEquals('wild rover', $second->getName());
        $this->assertEquals('nb,er', $third->getName());

        $this->assertEquals('whatever', $first->getDescription());
        $this->assertEquals('whatttttt', $second->getDescription());
        $this->assertEquals('whatever gbgf', $third->getDescription());
    }

    public function testItGetsForgeUGroup(): void
    {
        $user_group_id = 105;
        $row = array(
            'ugroup_id'   => 105,
            'name'        => 'my name',
            'description' => 'user group'
        );

        $this->dao->shouldReceive('getForgeUGroup')->with($user_group_id)->andReturns($row);

        $ugroup = $this->factory->getForgeUserGroupById($user_group_id);

        $this->assertEquals(105, $ugroup->getId());
        $this->assertEquals('my name', $ugroup->getName());
        $this->assertEquals('user group', $ugroup->getDescription());
    }

    public function testItThrowsExceptionIfUGroupNotFound(): void
    {
        $this->expectException(\User_UserGroupNotFoundException::class);

        $user_group_id = 105;

        $this->dao->shouldReceive('getForgeUGroup')->once()->with($user_group_id)->andReturns(false);

        $this->factory->getForgeUserGroupById($user_group_id);
    }

    public function testItCreatesForgeUGroup(): void
    {
        $name        = 'my group';
        $description = 'my desc';
        $id          = 102;

        $this->dao->shouldReceive('createForgeUGroup')->with($name, $description)->andReturns($id);

        $ugroup = $this->factory->createForgeUGroup($name, $description);

        $this->assertEquals(102, $ugroup->getId());
        $this->assertEquals('my group', $ugroup->getName());
        $this->assertEquals('my desc', $ugroup->getDescription());
    }

    public function testItThrowsExceptionIfUGroupNameExists(): void
    {
        $name        = 'my group';
        $description = 'my desc';

        $this->dao->shouldReceive('createForgeUGroup')->with($name, $description)->andThrows(new User_UserGroupNameInvalidException());

        $this->expectException(\User_UserGroupNameInvalidException::class);
        $this->factory->createForgeUGroup($name, $description);
    }
}
