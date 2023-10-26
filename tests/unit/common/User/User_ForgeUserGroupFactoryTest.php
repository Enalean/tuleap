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
final class User_ForgeUserGroupFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var UserGroupDao&\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dao;
    protected User_ForgeUserGroupFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao     = $this->createMock(\UserGroupDao::class);
        $this->factory = new User_ForgeUserGroupFactory($this->dao);
    }

    public function testItReturnsEmptyArrayIfNoResultsInDb(): void
    {
        $this->dao->method('getAllForgeUGroups')->willReturn(false);
        $all = $this->factory->getAllForgeUserGroups();

        self::assertCount(0, $all);
    }

    public function testItReturnsArrayOfUserGroups(): void
    {
        $data = [
            [
                'ugroup_id'     => 10,
                'name'          => 'thom thumbs',
                'description'   => 'whatever',
            ],
            [
                'ugroup_id'     => 12,
                'name'          => 'wild rover',
                'description'   => 'whatttttt',
            ],
            [
                'ugroup_id'     => 15,
                'name'          => 'nb,er',
                'description'   => 'whatever gbgf',
            ],
        ];

        $this->dao->method('getAllForgeUGroups')->willReturn($data);
        $all = $this->factory->getAllForgeUserGroups();

        self::assertCount(3, $all);

        $first  = $all[0];
        $second = $all[1];
        $third  = $all[2];

        self::assertInstanceOf(\User_ForgeUGroup::class, $first);
        self::assertInstanceOf(\User_ForgeUGroup::class, $second);
        self::assertInstanceOf(\User_ForgeUGroup::class, $third);

        self::assertEquals(10, $first->getId());
        self::assertEquals(12, $second->getId());
        self::assertEquals(15, $third->getId());

        self::assertEquals('thom thumbs', $first->getName());
        self::assertEquals('wild rover', $second->getName());
        self::assertEquals('nb,er', $third->getName());

        self::assertEquals('whatever', $first->getDescription());
        self::assertEquals('whatttttt', $second->getDescription());
        self::assertEquals('whatever gbgf', $third->getDescription());
    }

    public function testItGetsForgeUGroup(): void
    {
        $user_group_id = 105;
        $row           = [
            'ugroup_id'   => 105,
            'name'        => 'my name',
            'description' => 'user group',
        ];

        $this->dao->method('getForgeUGroup')->with($user_group_id)->willReturn($row);

        $ugroup = $this->factory->getForgeUserGroupById($user_group_id);

        self::assertEquals(105, $ugroup->getId());
        self::assertEquals('my name', $ugroup->getName());
        self::assertEquals('user group', $ugroup->getDescription());
    }

    public function testItThrowsExceptionIfUGroupNotFound(): void
    {
        $this->expectException(\User_UserGroupNotFoundException::class);

        $user_group_id = 105;

        $this->dao->expects(self::once())->method('getForgeUGroup')->with($user_group_id)->willReturn(false);

        $this->factory->getForgeUserGroupById($user_group_id);
    }

    public function testItCreatesForgeUGroup(): void
    {
        $name        = 'my group';
        $description = 'my desc';
        $id          = 102;

        $this->dao->method('createForgeUGroup')->with($name, $description)->willReturn($id);

        $ugroup = $this->factory->createForgeUGroup($name, $description);

        self::assertEquals(102, $ugroup->getId());
        self::assertEquals('my group', $ugroup->getName());
        self::assertEquals('my desc', $ugroup->getDescription());
    }

    public function testItThrowsExceptionIfUGroupNameExists(): void
    {
        $name        = 'my group';
        $description = 'my desc';

        $this->dao->method('createForgeUGroup')->with($name, $description)->willThrowException(
            new User_UserGroupNameInvalidException()
        );

        $this->expectException(\User_UserGroupNameInvalidException::class);
        $this->factory->createForgeUGroup($name, $description);
    }
}
