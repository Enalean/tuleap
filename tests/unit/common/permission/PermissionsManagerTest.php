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

use Tuleap\Project\Duplication\DuplicationUserGroupMapping;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class PermissionsManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testDuplicatePermissionsPassParameters(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];
        $ugroup_mapping   = [110 => 210,
            120 => 220,
        ];

        $duplication_mapping = DuplicationUserGroupMapping::fromSameProjectWithMapping($ugroup_mapping);

        $dao = $this->createMock(PermissionsDao::class);
        $dao->expects($this->atLeastOnce())->method('duplicatePermissions')->with($source, $target, $permission_types, $duplication_mapping);

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicatePermissions($source, $target, $permission_types, $duplication_mapping);
    }

    public function testDuplicateSameProjectShouldNotHaveUgroupMapping(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];

        $dao = $this->createMock(PermissionsDao::class);
        $dao->expects($this->atLeastOnce())->method('duplicatePermissions')->with($source, $target, $permission_types, DuplicationUserGroupMapping::fromSameProjectWithoutMapping());

        $permissionsManager = new PermissionsManager($dao);
        $permissionsManager->duplicateWithStatic($source, $target, $permission_types);
    }

    public function testDuplicateNewProjectShouldHaveUgroupMapping(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];
        $ugroup_mapping   = [110 => 210,
            120 => 220,
        ];

        $dao = $this->createMock(PermissionsDao::class);
        $dao->expects($this->atLeastOnce())->method('duplicatePermissions')->with($source, $target, $permission_types, DuplicationUserGroupMapping::fromNewProjectWithMapping($ugroup_mapping));

        $permissionsManager = new PermissionsManager($dao);
        $permissionsManager->duplicateWithStaticMapping($source, $target, $permission_types, $ugroup_mapping);
    }

    public function testDuplicateOtherProjectShouldNotHaveUgroupMapping(): void
    {
        $source           = 123;
        $target           = 234;
        $permission_types = ['STUFF_READ'];

        $dao = $this->createMock(PermissionsDao::class);
        $dao->expects($this->atLeastOnce())->method('duplicatePermissions')->with($source, $target, $permission_types, DuplicationUserGroupMapping::fromAnotherProjectWithoutMapping());

        $permissionsManager = new PermissionsManager($dao);

        $permissionsManager->duplicateWithoutStatic($source, $target, $permission_types);
    }
}
