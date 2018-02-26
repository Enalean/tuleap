<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\FRS\PermissionsPerGroup;

use TuleapTestCase;

class PaneCollectorTest extends TuleapTestCase
{
    public function itDoesNotBuildPaneIfServiceNotUsed()
    {
        $service_builder = mock('Tuleap\FRS\PermissionsPerGroup\PermissionPerGroupFRSServicePresenterBuilder');
        $package_builder = mock('Tuleap\FRS\PermissionsPerGroup\PermissionPerGroupFRSPackagesPresenterBuilder');

        $builder = new PaneCollector(
            $service_builder,
            $package_builder
        );

        $project = aMockProject()->build();
        stub($project)->usesFile()->returns(false);

        $selected_ugroup_id = null;
        $user               = mock('PFUser');

        expect($service_builder)->getPanePresenter()->never();
        expect($package_builder)->getPanePresenter()->never();

        $builder->collectPane($project, $user, $selected_ugroup_id);
    }
}
