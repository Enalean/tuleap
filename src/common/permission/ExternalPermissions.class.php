<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'common/user/UserManager.class.php';
require_once 'common/permission/PermissionsManager.class.php';
require_once 'common/project/UGroup.class.php';
require_once 'common/project/UGroupManager.class.php';

/**
 * Return groups of a user given by name to use them externally
 *
 * FIXME: Find a better name as it does not deal with external permissions 
 *        and the description is not coherent with the name.
 * FIXME: To not write purely static class. Use OOP. DDD Service or not.
 * FIXME: switch protected to private (there is no inheritance for now, yagni, toussa...)
 */
class ExternalPermissions {
    
    public static $ugroups_templates = array(
        UGroup::REGISTERED      => '@site_active @%s_project_members',
        UGroup::PROJECT_MEMBERS => '@%s_project_members',
        UGroup::PROJECT_ADMIN   => '@%s_project_admin'
    );
    
    /**
     * Return a list of groups with permissions of type $permissions_type
     * for the given object of a given project 
     * 
     * @param Project $project
     * @param integer $object_id
     * @param string  $permission_type
     * 
     * @return array of groups converted to string
     */
    public static function getProjectObjectGroups(Project $project, $object_id, $permission_type) {
        $ugroup_ids     = PermissionsManager::instance()->getAuthorizedUgroupIds($object_id, $permission_type);
        $project_name   = $project->getUnixName();
        $ugroup_manager = new UGroupManager();
        foreach ($ugroup_ids as $key => $ugroup_id) {
            $ugroup_ids[$key] = $ugroup_manager->ugroupIdToString($ugroup_id, $project_name);
        }

        return array_filter($ugroup_ids);
    }

}
?>
