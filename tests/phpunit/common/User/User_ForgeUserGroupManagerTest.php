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
final class User_ForgeUserGroupManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    private $dao;

    /**
     * @var User_ForgeUserGroupManager
     */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dao = \Mockery::spy(\UserGroupDao::class);
        $this->manager = new User_ForgeUserGroupManager(
            $this->dao,
            \Mockery::spy(SiteAdministratorPermissionChecker::class)
        );
    }

    public function testItThrowsExceptionIfUGroupNotFound(): void
    {
        $this->expectException(\User_UserGroupNotFoundException::class);

        $ugroup = new User_ForgeUGroup(45, 'people', 'to eat');

        $this->dao->shouldReceive('getForgeUGroup')->with(45)->andReturns(false);

        $this->manager->updateUserGroup($ugroup);
    }

    public function testItReturnsTrueIfThereAreNoModifications(): void
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

    public function testItUpdates(): void
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

    public function testItThrowsAnExceptionIfUGroupNameAlreadyExists(): void
    {
        $this->expectException(\User_UserGroupNameInvalidException::class);
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
