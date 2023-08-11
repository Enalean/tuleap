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

use Project;
use Tuleap\Test\PHPUnit\TestCase;

class PaneCollectorTest extends TestCase
{
    public function testItDoesNotBuildPaneIfServiceNotUsed(): void
    {
        $service_builder = $this->createMock(PermissionPerGroupFRSServicePresenterBuilder::class);
        $package_builder = $this->createMock(PermissionPerGroupFRSPackagesPresenterBuilder::class);

        $builder = new PaneCollector(
            $service_builder,
            $package_builder
        );

        $project = $this->createConfiguredMock(Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $project->method('usesFile')->willReturn(false);

        $selected_ugroup_id = null;

        $service_builder->expects(self::never())->method('getPanePresenter');
        $package_builder->expects(self::never())->method('getPanePresenter');

        $builder->collectPane($project, $selected_ugroup_id);
    }
}
