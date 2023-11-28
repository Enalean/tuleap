<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PermissionsManagerSavePermissionsCommonTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    protected PermissionsManager $permissions_manager;
    protected Project $project;
    protected string $permission_type;
    protected string $object_id;
    /**
     * @var PermissionsDao&MockObject
     */
    protected $permissions_dao;
    protected int $project_id;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project_id          = 404;
        $this->project             = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $this->permissions_dao     = $this->createMock(\PermissionsDao::class);
        $this->permission_type     = 'FOO';
        $this->object_id           = 'BAR';
        $this->permissions_manager = new PermissionsManager($this->permissions_dao);
    }

    protected function expectPermissionsOnce($ugroup): void
    {
        $this->permissions_dao
            ->expects(self::once())
            ->method('addPermission')
            ->with($this->permission_type, $this->object_id, $ugroup)
            ->willReturn(true);
    }

    protected function savePermissions($ugroups): void
    {
        $this->permissions_manager->savePermissions($this->project, $this->object_id, $this->permission_type, $ugroups);
    }

    public function testItSavesTheValueForStaticUGroupId(): void
    {
        $this->permissions_dao
            ->expects(self::once())
            ->method('clearPermission')
            ->with($this->permission_type, $this->object_id)
            ->willReturn(true);
        $this->permissions_dao
            ->expects(self::once())
            ->method('addPermission')
            ->with($this->permission_type, $this->object_id, 104)
            ->willReturn(true);
        $this->permissions_dao
            ->expects(self::once())
            ->method('addHistory')
            ->with($this->project_id, $this->permission_type, $this->object_id);

        $this->savePermissions([104]);
    }

    public function testItSavesTheValueForSeveralStaticUGroupIds(): void
    {
        $this->permissions_dao
            ->expects(self::once())
            ->method('clearPermission')
            ->with($this->permission_type, $this->object_id)
            ->willReturn(true);
        $this->permissions_dao
            ->expects(self::exactly(2))
            ->method('addPermission')
            ->withConsecutive(
                [$this->permission_type, $this->object_id, 104],
                [$this->permission_type, $this->object_id, 201]
            )
            ->willReturn(true);
        $this->permissions_dao
            ->expects(self::once())
            ->method('addHistory')
            ->with($this->project_id, $this->permission_type, $this->object_id)
            ->willReturn(true);

        $this->savePermissions([104, 201]);
    }

    public function testItSavesOnlyOneInstanceOfGroups(): void
    {
        $this->permissions_dao->method('clearPermission')->willReturn(true);

        $this->permissions_dao->method('addHistory');
        $this->permissions_dao
            ->expects(self::atLeast(2))
            ->method('addPermission')
            ->withConsecutive(
                [$this->permission_type, $this->object_id, 104],
                [$this->permission_type, $this->object_id, 201]
            )
            ->willReturn(true);

        $this->savePermissions([104, 201, 104]);
    }
}
