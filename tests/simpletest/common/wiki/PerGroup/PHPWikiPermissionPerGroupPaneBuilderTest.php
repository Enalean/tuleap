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

namespace Tuleap\PHPWiki\PerGroup;

use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use TuleapTestCase;

class PHPWikiPermissionPerGroupPaneBuilderTest extends TuleapTestCase
{

    public function itDoesNotBuildPaneIfServiceNotUsed()
    {
        $wiki_permissions_manager = mock('Wiki_PermissionsManager');
        $formatter                = mock('Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter');
        $ugroup_manager           = mock('UGroupManager');

        $builder = new PHPWikiPermissionPerGroupPaneBuilder(
            $wiki_permissions_manager,
            $formatter,
            $ugroup_manager
        );

        $project = aMockProject()->build();
        stub($project)->usesWiki()->returns(false);

        $selected_ugroup_id = null;

        expect($formatter)->formatGroup()->never();
        expect($wiki_permissions_manager)->getWikiAdminsGroups()->never();

        $builder->getPaneContent($project, $selected_ugroup_id);
    }
}
