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

use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermissionChecker;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_ForgeUserGroupManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var User_ForgeUserGroupPermissionsDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dao;
    private User_ForgeUserGroupManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao     = $this->createMock(\UserGroupDao::class);
        $this->manager = new User_ForgeUserGroupManager(
            $this->dao,
            $this->createMock(SiteAdministratorPermissionChecker::class)
        );
    }

    public function testItThrowsExceptionIfUGroupNotFound(): void
    {
        $this->expectException(\User_UserGroupNotFoundException::class);

        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->method('getForgeUGroup')->with(45)->willReturn(false);

        $this->manager->updateUserGroup($ugroup);
    }

    public function testItReturnsTrueIfThereAreNoModifications(): void
    {
        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->method('getForgeUGroup')->with(45)->willReturn([
            'group_id'    => 45,
            'name'        => 'people',
            'description' => 'to eat',
        ]);

        $update = $this->manager->updateUserGroup($ugroup);
        self::assertTrue($update);
    }

    public function testItUpdates(): void
    {
        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->method('getForgeUGroup')->with(45)->willReturn([
            'group_id'    => 45,
            'name'        => 'fish',
            'description' => 'to talk to',
        ]);

        $this->dao->expects(self::once())->method('updateForgeUGroup')->with(45, 'people', 'to eat')->willReturn(true);

        $update = $this->manager->updateUserGroup($ugroup);
        self::assertTrue($update);
    }

    public function testItThrowsAnExceptionIfUGroupNameAlreadyExists(): void
    {
        $this->expectException(\User_UserGroupNameInvalidException::class);
        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->method('getForgeUGroup')->with(45)->willReturn([
            'group_id'    => 45,
            'name'        => 'fish',
            'description' => 'to talk to',
        ]);

        $this->dao->expects(self::once())->method('updateForgeUGroup')->with(45, 'people', 'to eat')->willThrowException(
            new User_UserGroupNameInvalidException()
        );

        $update = $this->manager->updateUserGroup($ugroup);
        self::assertTrue($update);
    }
}
