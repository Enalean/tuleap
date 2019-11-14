<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\FRS\PermissionsPerGroup;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class PaneCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDoesNotBuildPaneIfServiceNotUsed()
    {
        $service_builder = \Mockery::spy(\Tuleap\FRS\PermissionsPerGroup\PermissionPerGroupFRSServicePresenterBuilder::class);
        $package_builder = \Mockery::spy(\Tuleap\FRS\PermissionsPerGroup\PermissionPerGroupFRSPackagesPresenterBuilder::class);

        $builder = new PaneCollector(
            $service_builder,
            $package_builder
        );

        $project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $project->shouldReceive('usesFile')->andReturns(false);

        $selected_ugroup_id = null;

        $service_builder->shouldReceive('getPanePresenter')->never();
        $package_builder->shouldReceive('getPanePresenter')->never();

        $builder->collectPane($project, $selected_ugroup_id);
    }
}
