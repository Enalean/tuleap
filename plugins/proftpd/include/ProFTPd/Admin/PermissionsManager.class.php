<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

namespace Tuleap\ProFTPd\Admin;

use Project;
use UGroupManager;
use UGroup;

class PermissionsManager {
    const PERM_READ  = 'PLUGIN_PROFTPD_READ';
    const PERM_WRITE = 'PLUGIN_PROFTPD_WRITE';

    /** @var \PermissionsManager */
    private $permissions_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct(\PermissionsManager $permissions_manager, UGroupManager $ugroup_manager) {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    public function getSelectUGroupFor(Project $project, $permissions) {
        $ugroups = $this->permissions_manager->getAuthorizedUgroupIds($project->getID(), $permissions);
        if (count($ugroups) == 1) {
            return array_shift($ugroups);
        }
        return UGroup::NONE;
    }

    public function savePermission(Project $project, $permission, array $ugroups) {
        include_once 'www/project/admin/permissions.php';

        permission_process_selection_form(
            $project->getGroupId(),
            $permission,
            $project->getGroupId(),
            $ugroups
        );
    }

    public function getUGroups(Project $project) {
        return $this->ugroup_manager->getStaticUGroups($project);
    }
}
