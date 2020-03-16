<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git\Permissions;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class FineGrainedPermissionSorterTest extends TestCase
{
    public function testItSortsPermissions()
    {
        $permission_01 = new FineGrainedPermission(
            0,
            1,
            'refs/heads/*',
            array(),
            array()
        );

        $permission_02 = new FineGrainedPermission(
            1,
            1,
            'refs/heads/application',
            array(),
            array()
        );

        $permission_03 = new FineGrainedPermission(
            2,
            1,
            'refs/heads/master',
            array(),
            array()
        );

        $permission_04 = new FineGrainedPermission(
            3,
            1,
            'refs/heads/application/*',
            array(),
            array()
        );

        $permission_05 = new FineGrainedPermission(
            4,
            1,
            'refs/heads/application/dev/*',
            array(),
            array()
        );

        $permissions = array(
            $permission_01,
            $permission_02,
            $permission_03,
            $permission_04,
            $permission_05,
        );

        $expected = array(
            2 => $permission_03,
            4 => $permission_05,
            3 => $permission_04,
            1 => $permission_02,
            0 => $permission_01,
        );

        $sorter = new FineGrainedPermissionSorter();

        $this->assertEquals($expected, $sorter->sort($permissions));
    }
}
