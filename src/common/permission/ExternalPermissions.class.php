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

/**
 * Return groups of a user given by name to use them externally
 *
 * FIXME: Find a better name as it does not deal with external permissions 
 *        and the description is not coherent with the name.
 * FIXME: To not write purely static class. Use OOP. DDD Service or not.
 * FIXME: switch protected to private (there is no inheritance for now, yagni, toussa...)
 */
class ExternalPermissions {
    
    public static $status = array(
        User::STATUS_RESTRICTED => 'site_restricted',
        User::STATUS_ACTIVE     => 'site_active'
    );
    
    public static $ugroups_templates = array(
        UGroup::REGISTERED      => '@site_active @%s_project_members',
        UGroup::PROJECT_MEMBERS => '@%s_project_members',
        UGroup::PROJECT_ADMIN   => '@%s_project_admin'
    );
    /**
     * Return User groups
     * 
     * @param string $user_name
     * 
     * return array
     */
    public static function getUserGroups($user_name) {
        $user = self::getValidUserByName($user_name);
        if (!$user) {
            return array();
        }
        $groups = array(self::$status[$user->getStatus()]);
        $groups = self::appendDynamicUGroups($user, $groups);
        $groups = self::appendStaticUgroups($user, $groups);
        
        return $groups;
    }

    /**
     * Append project dynamic ugroups of user
     * 
     * @param User  $user
     * @param array $user_ugroups
     *
     * @return array the new array of user's ugroup
     */
    protected static function appendDynamicUGroups( User $user, array $user_ugroups = array()) {
        $user_projects = $user->getProjects(true);
        foreach ($user_projects as $user_project) {
            $project_name = strtolower($user_project['unix_group_name']);
            $group_id     = $user_project['group_id'];
            $user_ugroups[] = $project_name.'_project_members';
            if ($user->isMember($group_id, 'A')) {
                $user_ugroups[] = $project_name.'_project_admin';
            }
        }
        return $user_ugroups;
    }

    /**
     * Append project static ugroups of user
     * 
     * @param User  $user
     * @param array $user_ugroups
     * 
     * @return array the new array of user's ugroup
     */
    protected static function appendStaticUgroups( User $user, array $user_ugroups = array()) {
        $ugroups = $user->getAllUgroups();
        foreach ($ugroups as $row) {
            $user_ugroups[] = 'ug_'.$row['ugroup_id'];
        }
        return $user_ugroups;
    } 

    /**
     * return an user if it's active or restricted
     * 
     * @param string $user_name
     * 
     * @return User if exists false otherwise
     */
    protected static function getValidUserByName($user_name) {
        $user = UserManager::instance()->getUserByUserName($user_name);
        if ($user && isset(self::$status[$user->getStatus()])) {
            return $user;
        }
        return false;
    }
    
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
        $ugroup_ids   = PermissionsManager::instance()->getAuthorizedUgroupIds($object_id, $permission_type);
        $project_name = $project->getUnixName();
        array_walk($ugroup_ids, array('ExternalPermissions', 'ugroupIdToString'), $project_name);
        return array_filter($ugroup_ids);
    }
    
    /**
     * Convert given ugroup id to a format managed by ExternalPermissions
     *
     * @param String $ugroup UGroupId
     */
    protected static function ugroupIdToString(&$ugroup, $key, $project_name) {
        if ($ugroup > 100) {
            $ugroup = '@ug_'. $ugroup;
            return false;
        } 
        if (isset(self::$ugroups_templates[$ugroup])) {
            $ugroup = sprintf(self::$ugroups_templates[$ugroup], $project_name);
        } else {
            $ugroup = null;
        }
        return false;
    }

}
?>
