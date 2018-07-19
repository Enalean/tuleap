<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

require_once('common/permission/PermissionsManager.class.php');

class Docman_PermissionsManagerDao extends DataAccessObject {

    var $groupId;
    function __construct($da, $groupId) {
        parent::__construct($da);
        $this->groupId = $groupId;
    }

    function retreivePermissionsForItems($itemsIds, $perms, $ugroupIds) {
        $sql = 'SELECT *'.
            ' FROM permissions'.
            " WHERE object_id IN ('".implode("','", $itemsIds)."')".
            ' AND permission_type IN ('.implode(',', $perms).')'.
            ' AND ugroup_id IN ('.implode(',', $ugroupIds).')';
        return $this->retrieve($sql);
    }

    function setDefaultPermissions($objectId, $perm, $force=false) {
        require_once('www/project/admin/permissions.php');
        $res = permission_db_get_defaults($perm);
        while($row = $this->getDa()->fetchArray($res)) {
            permission_add_ugroup($this->groupId, $perm, $objectId, $row['ugroup_id'], $force);
        }
    }

    function oneFolderIsWritable($group_id, $ugroupIds) {
        $sql = sprintf('SELECT i.item_id'.
                      ' FROM plugin_docman_item as i, permissions as p'.
                      ' WHERE i.group_id = %d '.
                      ' AND i.item_type = '.PLUGIN_DOCMAN_ITEM_TYPE_FOLDER.
                      ' AND p.permission_type IN (\'PLUGIN_DOCMAN_WRITE\', \'PLUGIN_DOCMAN_MANAGE\')'.
                      ' AND p.ugroup_id IN ('.implode(',', $ugroupIds).')'.
                      ' AND p.object_id = CAST(i.item_id as CHAR CHARACTER SET utf8)',
                      $group_id);
        $res = $this->retrieve($sql);
        if(!$res->isError() && $res->rowCount() > 0) {
            return true;
        }
        else {
            return false;
        }
    }
    

    /**
     * Returns project admin members for a given group
     * 
     * @param Project $project
     */
    function getProjectAdminMembers($project) {
        $sql = 'SELECT email, language_id FROM user u JOIN user_group ug USING(user_id) WHERE ug.admin_flags="A" AND u.status IN ("A", "R") AND ug.group_id = '.$this->da->escapeInt($project->getId());
        return $this->retrieve($sql); 
    }
    
    /**
     * Returns ugroup members of ugroups
     * 
     * @param Integer $ugroupId
     */
    function getUgroupMembers($ugroupId) {
        $sql = ' SELECT email, language_id FROM user u JOIN ugroup_user ug USING(user_id) WHERE u.status IN ("A", "R") AND ug.ugroup_id = '.$this->da->escapeInt($ugroupId);
        return $this->retrieve($sql);
    }

    /**
     * Returns docman admin ugroups
     * 
     * @param Project $project
     *
     * @return DataAccessResult
     */
    function getDocmanAdminUgroups(Project $project) {
        $sql="SELECT ugroup_id
              FROM permissions
              WHERE permission_type = 'PLUGIN_DOCMAN_ADMIN'
                AND object_id = ".$this->da->escapeInt($project->getGroupId())."
              ORDER BY ugroup_id";
        $res = $this->retrieve($sql);
        if ($res && !$res->isError()) {
            return $res;
        } else {
            return false;
        }
    }

    public function isThereAnExplicitPermission($ugroup_id, $project_id) {
        $ugroup_id  = $this->da->escapeInt($ugroup_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql =
            "SELECT * FROM permissions
                JOIN plugin_docman_item ON permissions.object_id = plugin_docman_item.item_id
            WHERE ugroup_id = $ugroup_id
                AND permission_type LIKE 'PLUGIN_DOCMAN%'
                AND group_id = $project_id
            LIMIT 1
            ";

        return $this->retrieveFirstRow($sql);
    }

    public function doAllItemsHaveExplicitPermissions($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql =
           "SELECT * FROM plugin_docman_item
                LEFT JOIN permissions ON permissions.object_id = CAST(plugin_docman_item.item_id as CHAR CHARACTER SET utf8)
            WHERE plugin_docman_item.group_id = $project_id
                AND permission_type IS NULL
            LIMIT 1
            ";

        $results = (bool) $this->retrieveFirstRow($sql);

        return ! $results;
    }

    public function isThereADefaultPermissionThatUsesUgroup($ugroup_id) {
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql =
           "SELECT permissions_values.* FROM permissions_values
            WHERE ugroup_id = $ugroup_id
                AND permission_type LIKE 'PLUGIN_DOCMAN%'
            LIMIT 1
            ";

        return (bool) $this->retrieveFirstRow($sql);
    }
}
