<?php
/**
 * Copyright (c) Enalean 2016 - 2018. All rights reserved.
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

namespace Tuleap\SVN;

require_once __DIR__ . '/../../../../src/www/project/admin/permissions.php';

use Project;
use PFUser;
use PermissionsManager;

class SvnPermissionManager
{

    public const PERMISSION_ADMIN = 'PLUGIN_SVN_ADMIN';

    /**
     * @var PermissionsManager
     */
    private $permission_manager;

    public function __construct(PermissionsManager $permission_manager)
    {
        $this->permission_manager = $permission_manager;
    }

    /**
     * @return array of ProjectUGroup
     */
    public function getAdminUgroupIds(Project $project)
    {
        return $this->permission_manager->getAuthorizedUGroupIdsForProject($project, $project->getID(), self::PERMISSION_ADMIN);
    }

    public function save(Project $project, array $ugroup_ids)
    {
        $override_collection = $this->permission_manager->savePermissions($project, $project->getId(), self::PERMISSION_ADMIN, $ugroup_ids);
        $override_collection->emitFeedback("");
    }

    /**
     * @return bool
     */
    public function isAdmin(Project $project, PFUser $user)
    {
        if ($user->isAdmin($project->getId())) {
            return true;
        }

        $permissions = $this->permission_manager->getAuthorizedUgroups($project->getID(), self::PERMISSION_ADMIN);
        foreach ($permissions as $permission) {
            if (ugroup_user_is_member($user->getId(), $permission['ugroup_id'], $project->getID())) {
                return true;
            }
        }

        return false;
    }
}
