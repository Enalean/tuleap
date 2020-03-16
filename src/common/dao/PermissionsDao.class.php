<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
 *  Data Access Object for Permissions
 */
class PermissionsDao extends DataAccessObject implements IPermissionsNGDao
{

    public const DUPLICATE_NEW_PROJECT   = 1;
    public const DUPLICATE_SAME_PROJECT  = 2;
    public const DUPLICATE_OTHER_PROJECT = 3;

    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function searchAll()
    {
        $sql = "SELECT * FROM permissions";
        return $this->retrieve($sql);
    }

    /**
     * Searches Ugroups Ids (and names if required) from ObjectId and Permission type
     *
     * @param String  $objectId       Id of object
     * @param String  $permissionType Permission type
     * @param bool $withName Whether to include the group name or not
     *
     * @return DataAccessResult
     */
    public function searchUgroupByObjectIdAndPermissionType($objectId, $permissionType, $withName = true)
    {
        $fields = '';
        $joins  = '';
        if ($withName) {
            $fields = ' ug.name, ';
            $joins  = ' JOIN ugroup AS ug USING(ugroup_id) ';
        }
        $sql = 'SELECT ' . $fields . ' p.ugroup_id, p.permission_type' .
               ' FROM permissions p ' . $joins .
               ' WHERE p.object_id = ' . $this->da->quoteSmart($objectId, array('force_string' => true)) .
               ' AND p.permission_type LIKE ' . $this->da->quoteSmart($permissionType) .
               ' ORDER BY ugroup_id';
        return $this->retrieve($sql);
    }

    public function getUgroupsByObjectIdAndPermissionType($object_id, $permission_type)
    {
        $object_id       = $this->da->quoteSmart($object_id, array('force_string' => true));
        $permission_type = $this->da->quoteSmart($permission_type);

        $sql = "SELECT ugroup.*
               FROM permissions p
                JOIN ugroup ON ugroup.ugroup_id = p.ugroup_id
               WHERE p.object_id = $object_id
               AND p.permission_type = $permission_type
               ORDER BY p.ugroup_id";

        return $this->retrieve($sql);
    }

    /**
     * Return the list of the default ugroup_ids authorized to access the given permission_type
     *
     * @param String  $permissionType Permission type
     * @param bool $withName Whether to include the group name or not
     *
     * @return DataAccessResult
     */
    public function searchDefaults($permissionType, $withName = true)
    {
        $fields = '';
        $joins  = '';
        if ($withName) {
            $fields = ' ug.*, ';
            $joins  = ' JOIN ugroup AS ug USING(ugroup_id) ';
        }
        $sql = 'SELECT ' . $fields . ' pv.ugroup_id, pv.permission_type' .
               ' FROM permissions_values pv ' . $joins .
               ' WHERE pv.permission_type=' . $this->da->quoteSmart($permissionType) .
               ' AND pv.is_default=1' .
               ' ORDER BY pv.ugroup_id';
        return $this->retrieve($sql);
    }

    /**
    * Searches Permissions by ObjectId and Ugroups
    * @return DataAccessResult
    */
    public function searchPermissionsByObjectId($object_id, $ptype = null)
    {
        if (is_array($object_id)) {
            $object_ids_where = $this->da->quoteSmartImplode(',', $object_id, array('force_string' => true));
            $_where_clause    = " object_id IN ($object_ids_where)";
        } else {
            $object_id_where = $this->da->quoteSmart($object_id, array('force_string' => true));
            $_where_clause   = " object_id = $object_id_where";
        }
        if ($ptype !== null) {
            $ptype_where    = $this->da->quoteSmartImplode(',', $ptype);
            $_where_clause .= " AND permission_type IN ($ptype_where)";
        }

        $sql = sprintf("SELECT * FROM permissions WHERE " . $_where_clause);

        return $this->retrieve($sql);
    }

    /**
    * Searches Permissions by TrackerId and Ugroups
    * @return DataAccessResult
    */
    public function searchPermissionsByArtifactFieldId($object_id)
    {
        $object_id = $this->da->escapeInt($object_id);
        $sql       = "SELECT * FROM permissions WHERE object_id LIKE '$object_id#%'";
        return $this->retrieve($sql);
    }

   /**
    * Clone docman permissions
    *
    * @param int $source
    * @param int $target
    * @param $perms
    * @param $toGroupId
    *
    * @return bool
    */
    public function clonePermissions($source, $target, $perms, $toGroupId = 0)
    {
        $sql = sprintf(
            "DELETE FROM permissions " .
                        " WHERE object_id = %s " .
                        "   AND permission_type IN (%s) ",
            $this->da->quoteSmart($target, array('force_string' => true)),
            $this->da->quoteSmartImplode(',', $perms)
        );
        $this->update($sql);
        $sql = sprintf(
            "INSERT INTO permissions (object_id, permission_type, ugroup_id) " .
                        " SELECT %s, permission_type, IFNULL(dst_ugroup_id, permissions.ugroup_id) AS ugid " .
                        " FROM permissions LEFT JOIN ugroup_mapping ON (to_group_id=%d  and src_ugroup_id = permissions.ugroup_id)" .
                        " WHERE object_id = %s " .
                        "   AND permission_type IN (%s) ",
            $this->da->quoteSmart($target, array('force_string' => true)),
            $this->da->escapeInt($toGroupId),
            $this->da->quoteSmart($source, array('force_string' => true)),
            $this->da->quoteSmartImplode(',', $perms)
        );
        return $this->update($sql);
    }

   /**
    * Duplicate permissions
    *
    * Manage the 3 types of duplications:
    * - On project creation: there is a ugroup_mapping so we should a straight copy the dynamics groups and a translated copy of the static groups
    * - On copy within the same project: no need to translate, we just do a straight copy of the existing permissions (both static and dynamic groups)
    * - On copy from another project: there is no ugroup_mapping so we can only straight copy dynamic groups. Static groups are left).
    *
    * @param int    $from
    * @param int    $to
    * @param Array $permission_type
    * @param int    $duplicate_type
    * @param Array  $ugroup_mapping, an array of static ugroups
    *
    * @return bool
    */
    public function duplicatePermissions($from, $to, array $permission_type, $duplicate_type, $ugroup_mapping = false)
    {
        $from            = $this->da->quoteSmart($from, array('force_string' => true));
        $to              = $this->da->quoteSmart($to, array('force_string' => true));
        $permission_type = '(' . $this->da->quoteSmartImplode(',', $permission_type) . ')';

        //Duplicate static perms
        if ($ugroup_mapping !== false) {
            foreach ($ugroup_mapping as $template_ugroup => $new_ugroup) {
                $template_ugroup = $this->da->escapeInt($template_ugroup);
                $new_ugroup = $this->da->escapeInt($new_ugroup);
                $sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id)
                            SELECT permission_type, ' . $to . ',' . $new_ugroup . '
                            FROM permissions
                            WHERE object_id = ' . $from . '
                                AND ugroup_id = ' . $template_ugroup . '
                                AND permission_type IN ' . $permission_type;
                $this->update($sql);
            }
        }

        $and = '';
        if ($duplicate_type == self::DUPLICATE_NEW_PROJECT || $duplicate_type == self::DUPLICATE_OTHER_PROJECT) {
            $and = ' AND ugroup_id <= 100';
        }
        //Duplicate dynamic perms
        $sql = 'INSERT INTO permissions (permission_type, object_id, ugroup_id)
                    SELECT permission_type, ' . $to . ', ugroup_id
                    FROM permissions
                    WHERE object_id=' . $from . '
                        AND permission_type IN ' . $permission_type
                        . $and;
        return $this->update($sql);
    }

    public function addPermission($permission_type, $object_id, $ugroup_id)
    {
        $permission_type = $this->da->quoteSmart($permission_type);
        $object_id       = $this->da->quoteSmart($object_id, array('force_string' => true));
        $ugroup_id       = $this->da->escapeInt($ugroup_id);
        $sql = "INSERT INTO permissions (object_id, permission_type, ugroup_id)
                VALUES ($object_id, $permission_type, $ugroup_id)";
        return $this->update($sql);
    }

    public function removePermission($permission_type, $object_id, $ugroup_id)
    {
        $permission_type = $this->da->quoteSmart($permission_type);
        $object_id       = $this->da->quoteSmart($object_id, array('force_string' => true));
        $ugroup_id       = $this->da->escapeInt($ugroup_id);
        $sql = "DELETE FROM permissions
                WHERE permission_type = $permission_type
                AND object_id = $object_id
                AND ugroup_id = $ugroup_id";
        return $this->update($sql);
    }

    /**
     * Removes a given permission to a given object
     *
     * @param String $permissionType Permission
     * @param String $objectId       Affected object's id
     *
     * @return bool
     */
    public function clearPermission($permissionType, $objectId)
    {
        $sql = ' DELETE FROM permissions ' .
               ' WHERE object_id = ' . $this->da->quoteSmart($objectId, array('force_string' => true)) .
               ' AND permission_type = ' . $this->da->quoteSmart($permissionType);
        return $this->update($sql);
    }

    public function isThereAnExplicitWikiServicePermission($ugroup_id)
    {
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql =
           "SELECT * FROM permissions
            WHERE ugroup_id = $ugroup_id
                AND permission_type LIKE 'WIKI%'
            LIMIT 1
            ";

        return $this->retrieveFirstRow($sql);
    }

    public function doAllWikiServiceItemsHaveExplicitPermissions($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql =
          "SELECT * FROM wiki_page
                LEFT JOIN permissions ON permissions.object_id = CAST(wiki_page.id as CHAR CHARACTER SET utf8)
            WHERE wiki_page.group_id = $project_id
                AND permission_type IS NULL
            LIMIT 1
            ";

        $results = (bool) $this->retrieveFirstRow($sql);

        return ! $results;
    }

    public function isThereADefaultWikiServicePermissionThatUsesUgroup($ugroup_id)
    {
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql =
           "SELECT permissions_values.* FROM permissions_values
            WHERE ugroup_id = $ugroup_id
                AND permission_type LIKE 'WIKI%'
            LIMIT 1
            ";

        return (bool) $this->retrieveFirstRow($sql);
    }

    public function disableRestrictedAccess()
    {
        $public_ugroup_id       = $this->da->escapeInt(ProjectUGroup::REGISTERED);
        $unrestricted_ugroup_id = $this->da->escapeInt(ProjectUGroup::AUTHENTICATED);

        $sql =
           "UPDATE permissions
               SET ugroup_id = $public_ugroup_id
            WHERE ugroup_id  = $unrestricted_ugroup_id";

        return $this->update($sql);
    }

    public function disableRestrictedAccessForObjectId(array $permission_type, $object_id)
    {
        $public_ugroup_id       = $this->da->escapeInt(ProjectUGroup::REGISTERED);
        $unrestricted_ugroup_id = $this->da->escapeInt(ProjectUGroup::AUTHENTICATED);
        $object_id              = $this->da->quoteSmart($object_id, array('force_string' => true));
        $permission_type        = $this->da->quoteSmartImplode(',', $permission_type);

        $sql =
           "UPDATE permissions
               SET ugroup_id = $public_ugroup_id
            WHERE ugroup_id  = $unrestricted_ugroup_id
            AND    object_id = $object_id
            AND    permission_type IN ($permission_type)";

        return $this->update($sql);
    }

    public function addHistory($group_id, $permission_type, $object_id)
    {
        permission_add_history($group_id, $permission_type, $object_id);
    }
}
