<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once 'www/project/admin/permissions.php';

/**
 * This class manages permissions for the Git service
 */
class GitPermissionsManager {

    public function __construct() {
        $this->permissions_manager = PermissionsManager::instance();
    }

    public function userIsGitAdmin(PFUser $user, Project $project) {
        $database_result = $this->getCurrentGitAdminPermissionsForProject($project);

        if (db_numrows($database_result) < 1) {
            $database_result = $this->getDefaultGitAdminPermissions();
        }

        $has_permission = false;
        while (! $has_permission && ($row = db_fetch_array($database_result))) {
            $has_permission = ugroup_user_is_member($user->getId(), $row['ugroup_id'], $project->getID());
        }

        return $has_permission;
    }

    /**
     * @param Project $project
     * Return a DB list of ugroup_ids authorized to access the given object
     */
    private function getCurrentGitAdminPermissionsForProject(Project $project) {
        return permission_db_authorized_ugroups(Git::PERM_ADMIN, $project->getID());
    }

    private function getDefaultGitAdminPermissions() {
        return permission_db_get_defaults(Git::PERM_ADMIN);
    }

    public function getCurrentGitAdminUgroups($project_id) {
        return $this->permissions_manager->getAuthorizedUgroupIds($project_id, Git::PERM_ADMIN);
    }
}

?>
