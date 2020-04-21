<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use PHPUnit\Framework\TestCase;

class PermissionsCacheTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItUsesCacheWhenPossible(): void
    {
        $artifact = Mockery::mock(\Tracker_Artifact::class);
        $artifact->shouldReceive('getId')->andReturn(102);

        $user = Mockery::mock(\PFUser::class);

        $permission_checker = Mockery::mock(\Tracker_Permission_PermissionChecker::class);
        $user->shouldReceive('getId')->andReturn(1);

        $permission_checker->shouldReceive('userCanView')->withArgs([$user, $artifact])->once()->andReturn(true);

        PermissionsCache::userCanView($artifact, $user, $permission_checker);
        PermissionsCache::userCanView($artifact, $user, $permission_checker);
    }
}
