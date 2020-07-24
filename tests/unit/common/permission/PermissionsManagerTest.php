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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PermissionsManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDuplicatePermissionsPassParamters(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];
        $ugroup_mapping   = [110 => 210,
                                  120 => 220];
        $duplicate_type  = PermissionsDao::DUPLICATE_SAME_PROJECT;

        $dao = Mockery::mock(PermissionsDao::class);
        $dao->shouldReceive('duplicatePermissions')->with($source, $target, $permission_types, $duplicate_type, $ugroup_mapping)->once();

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicatePermissions($source, $target, $permission_types, $ugroup_mapping, $duplicate_type);
    }

    public function testDuplicateSameProjectShouldNotHaveUgroupMapping(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];

        $dao = Mockery::mock(PermissionsDao::class);
        $dao->shouldReceive('duplicatePermissions')->with($source, $target, $permission_types, PermissionsDao::DUPLICATE_SAME_PROJECT, false)->once();

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithStatic($source, $target, $permission_types);
    }

    public function testDuplicateNewProjectShouldHaveUgroupMapping(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];
        $ugroup_mapping   = [110 => 210,
                                  120 => 220];

        $dao = Mockery::mock(PermissionsDao::class);
        $dao->shouldReceive('duplicatePermissions')->with($source, $target, $permission_types, PermissionsDao::DUPLICATE_NEW_PROJECT, $ugroup_mapping)->once();

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithStaticMapping($source, $target, $permission_types, $ugroup_mapping);
    }

    public function testDuplicateOtherProjectShouldNotHaveUgroupMapping(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];

        $dao = Mockery::mock(PermissionsDao::class);
        $dao->shouldReceive('duplicatePermissions')->with($source, $target, $permission_types, PermissionsDao::DUPLICATE_OTHER_PROJECT, false)->once();

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithoutStatic($source, $target, $permission_types);
    }
}
