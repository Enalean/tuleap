<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class PermissionsManager implements IPermissionsManagerNG
{
    /**
     * @var PermissionsDao
     */
    public $_permission_dao;
    public $_permissions;
    public $_ugroups_for_user;

    private static $_permissionmanager_instance;

    public function __construct($permission_dao)
    {
        $this->_permission_dao   = $permission_dao;
        $this->_permissions      = array();
        $this->_ugroups_for_user = array();
    }

    /**
     * The manager is a singleton
     *
     * @return PermissionsManager
     */
    public static function instance()
    {
        if (!self::$_permissionmanager_instance) {
            self::$_permissionmanager_instance = new PermissionsManager(new PermissionsDao(CodendiDataAccess::instance()));
        }
        return self::$_permissionmanager_instance;
    }

    public static function setInstance($instance)
    {
        self::$_permissionmanager_instance = $instance;
    }

    public static function clearInstance()
    {
        self::$_permissionmanager_instance = null;
    }

    /**
    * Returns if one of the user's ugroups has permission to access the object
    *
    * WARNING: THIS METHOD DOESN'T TAKE 'DEFAULT' PERMISSIONS !
    *
    * @access public
    *
    * @param  int     $object_id       The id of the object
    * @param  string  $permission_type The type of permission asked
    * @param  array   $ugroups         The user's ugroups
    * @return bool
    */
    public function userHasPermission($object_id, $permission_type, array $ugroups)
    {
        if (!isset($this->_permissions[$object_id])) {
            $this->_permissions[$object_id] = array();
        }

        if (count(array_diff($ugroups, array_keys($this->_permissions[$object_id]))) > 0) {
            $this->retrievePermissions($object_id, $ugroups);
        }
        //now we search for $permission_type
        foreach ($ugroups as $ugroup) {
            if (
                isset($this->_permissions[$object_id][$ugroup]) &&
                in_array($permission_type, $this->_permissions[$object_id][$ugroup])
            ) {
                return true;
            }
        }
        return false;
    }

    /**
    * Returns all permissions for ugroups for a given object
    * WARNING: since object_ids are not unique, some permissions returned
    * might not be relevant for the given object
    *
    * @access public
    *
    * @param  int     $object_id  The id of the object
    *
    * @return array
    */
    public function getPermissionsAndUgroupsByObjectid($object_id)
    {
        $this->retrievePermissions($object_id);
        $perms = array();
        if (isset($this->_permissions[$object_id])) {
            foreach ($this->_permissions[$object_id] as $ugroup_id => $permissions) {
                foreach ($permissions as $perm) {
                    if (!isset($perms[$perm])) {
                        $perms[$perm] = array();
                    }
                    $perms[$perm][] = $ugroup_id;
                }
            }
        }
        return $perms;
    }

    /**
     * Returns all ugroup name for a given object_id and permission_type
     * @param  int     $object_id       The id of the object
     * @param  string  $permission_type The type of permission asked
     */
    public function getUgroupNameByObjectIdAndPermissionType($object_id, $permission_type)
    {
        $dar =& $this->_permission_dao->searchUgroupByObjectIdAndPermissionType($object_id, $permission_type);
        if ($dar->isError()) {
            return;
        }

        if (!$dar->valid()) {
            return;
        }

        $ugroups_name = array ();
        while ($dar->valid()) {
            $ugroup = $dar->current();
            $new_name = $ugroup['name'];
            if (strpos($new_name, "ugroup_") === 0 && strpos($new_name, "_name_key") + strlen("_name_key") === strlen($new_name)) {
                $new_name = $GLOBALS['Language']->getText('project_ugroup', $new_name);
            }
            $ugroups_name[] = $new_name;
            $dar->next();
        }
        return $ugroups_name;
    }

    /**
     * Returns all ugroup id for a given object_id and permission_type
     *
     * @deprecated Use getAuthorizedUgroups instead (that takes default permissions into account)
     *
     * @param  int     $object_id       The id of the object
     * @param  string  $permission_type The type of permission asked
     */
    public function getUgroupIdByObjectIdAndPermissionType($object_id, $permission_type)
    {
        $dar = $this->_permission_dao->searchUgroupByObjectIdAndPermissionType($object_id, $permission_type, false);
        if ($dar->isError()) {
            return;
        } else {
            return $dar;
        }
    }

     /**
      * Return the list of the default ugroup_ids authorized to access the given permission_type
      *
      * @see permission_db_get_defaults
      *
      * @param String $permissionType
      *
      * @return DataAccessResult
      */
    public function getDefaults($permissionType, $withName = true)
    {
        return $this->_permission_dao->searchDefaults($permissionType, $withName);
    }

     /**
      * Return the list of ugroups authorized to access the given object with the given permission_type
      *
      * If no specific permissions set, returns the defaults.
      *
      * @param int $objectId
      * @param String  $permissionType
      *
      * @return DataAccessResult
      */
    public function getAuthorizedUgroups($objectId, $permissionType, $withName = true)
    {
        $dar = $this->_permission_dao->searchUgroupByObjectIdAndPermissionType((string) $objectId, $permissionType, $withName);
        if ($dar && $dar->rowCount() > 0) {
            return $dar;
        } else {
            return $this->getDefaults($permissionType, $withName);
        }
    }

     /**
      * Return the list of ugroup ids authorized to access the given object with the given permission_type
      *
      * If no specific permissions set, returns the defaults.
      *
      * @param int $objectId
      * @param String  $permissionType
      *
      * @return array
      */
    public function getAuthorizedUgroupIds($objectId, $permissionType, $withName = true)
    {
        $dar = $this->getAuthorizedUgroups($objectId, $permissionType, $withName);
        if (!$dar || $dar->isError()) {
            return array();
        }

        $ugroups = array();
        foreach ($dar as $row) {
            $ugroups[] = $row['ugroup_id'];
        }
        return $ugroups;
    }

    public function getAuthorizedUGroupIdsForProject(Project $project, $object_id, $permission_type)
    {
        $ugroups = array();
        $dar = $this->getAuthorizedUgroups($object_id, $permission_type, false);
        if ($dar && ! $dar->isError()) {
            $normalizer = new PermissionsUGroupMapper($project);
            foreach ($dar as $row) {
                $ugroups[] = $normalizer->getUGroupIdAccordingToMapping($row['ugroup_id']);
            }
        }
        return $ugroups;
    }

    public function getAuthorizedUGroupIdsForProjectWithoutDefaultValues(Project $project, $object_id, $permission_type)
    {
        $dar = $this->_permission_dao->searchUgroupByObjectIdAndPermissionType((string) $object_id, $permission_type, false);
        if (! $dar || $dar->isError()) {
            return [];
        }
        $ugroup_ids = [];
        $normalizer = new PermissionsUGroupMapper($project);

        foreach ($dar as $row) {
            $ugroup_ids[] = $normalizer->getUGroupIdAccordingToMapping($row['ugroup_id']);
        }

        return $ugroup_ids;
    }

    /**
     * @param type $object_id
     * @param type $permission_type
     * @param array $ugroup_ids
     *
     * @return PermissionsNormalizerOverrideCollection
     * @throws PermissionDaoException
     */
    public function savePermissions(Project $project, $object_id, $permission_type, array $ugroup_ids)
    {
        return $this->doSavePermissions($project, $object_id, $permission_type, $ugroup_ids, true);
    }

    public function savePermissionsWithoutHistory(Project $project, $object_id, $permission_type, array $ugroup_ids)
    {
        return $this->doSavePermissions($project, $object_id, $permission_type, $ugroup_ids, false);
    }

    private function doSavePermissions(Project $project, $object_id, $permission_type, array $ugroup_ids, $add_history)
    {
        $normalizer            = new PermissionsNormalizer();
        $override_collection   = new PermissionsNormalizerOverrideCollection();
        $normalized_ugroup_ids = $normalizer->getNormalizedUGroupIds($project, $ugroup_ids, $override_collection);
        $cleared = $this->_permission_dao->clearPermission($permission_type, $object_id);
        if (! $cleared) {
            throw new PermissionDaoException("Database issue while clearPermission $permission_type, $object_id: " . $this->_permission_dao->getDa()->getErrorMessage());
        }
        foreach ($normalized_ugroup_ids as $ugroup_id) {
            $added = $this->_permission_dao->addPermission($permission_type, $object_id, $ugroup_id);
            if (! $added) {
                throw new PermissionDaoException("Database issue while addPermission $permission_type, $object_id, $ugroup_id: " . $this->_permission_dao->getDa()->getErrorMessage());
            }
        }

        if ($add_history) {
            $this->_permission_dao->addHistory($project->getID(), $permission_type, $object_id);
        }

        return $override_collection;
    }

    protected function buildPermissionsCache(&$dar, &$ugroups)
    {
        while ($row = $dar->getRow()) {
            if (!isset($this->_permissions[$row['object_id']])) {
                $this->_permissions[$row['object_id']] = array();
            }
            foreach ($ugroups as $ugroup) {
                if (!isset($this->_permissions[$row['object_id']][$ugroup])) {
                    $this->_permissions[$row['object_id']][$ugroup] = array();
                }
            }
            if (!isset($this->_permissions[$row['object_id']][$row['ugroup_id']])) {
                $this->_permissions[$row['object_id']][$row['ugroup_id']] = array();
            }
            if (!in_array($row['permission_type'], $this->_permissions[$row['object_id']][$row['ugroup_id']])) {
                $this->_permissions[$row['object_id']][$row['ugroup_id']][] = $row['permission_type'];
            }
        }
    }

    /**
    * Store internally (in _permissions) all permissions for an object
    *
    * @access protected
    *
    * @param  int     $object_id  The id of the object
    * @param  array   $ugroups    A list of ugroups we want to see in permissions
    */
    protected function retrievePermissions($object_id, $ugroups = array())
    {
        $tracker_field_id = explode('#', $object_id); //An artifact field ?
        if (count($tracker_field_id) > 1) {
            $dar = $this->_permission_dao->searchPermissionsByArtifactFieldId($tracker_field_id[0]);
        } else {
            $dar = $this->_permission_dao->searchPermissionsByObjectId($object_id);
        }
        $this->buildPermissionsCache($dar, $ugroups);
    }

    public function clonePermissions($source, $target, $perms, $toGroupId = 0)
    {
        return $this->_permission_dao->clonePermissions($source, $target, $perms, $toGroupId);
    }

   /**
    * Duplicate permissions
    *
    * @param int    $source
    * @param int    $target
    * @param array  $permission_types
    * @param array  $ugroup_mapping, an array of ugroups
    * @param int    $duplicate_type What kind of duplication is going on
    *
    * @deprecated Use one of duplicateWithStatic, duplicateWithStaticMapping, duplicateWithoutStatic below
    *
    * @return bool
    */
    public function duplicatePermissions($source, $target, array $permission_types, $ugroup_mapping, $duplicate_type)
    {
        return $this->_permission_dao->duplicatePermissions($source, $target, $permission_types, $duplicate_type, $ugroup_mapping);
    }

    /**
     * Duplicate permission within the same project (copy perms for both dynamic and static groups)
     *
     * @param int    $source
     * @param int    $target
     * @param array  $permission_types
     *
     * @return bool
     */
    public function duplicateWithStatic($source, $target, array $permission_types)
    {
        return $this->_permission_dao->duplicatePermissions($source, $target, $permission_types, PermissionsDao::DUPLICATE_SAME_PROJECT, false);
    }

    /**
     * Duplicate permission on project creation (straight copy perms for dynamic and translate static groups with ugroup_mapping)
     *
     * @param int    $source
     * @param int    $target
     * @param array  $permission_types
     * @param array  $ugroup_mapping
     *
     * @return bool
     */
    public function duplicateWithStaticMapping($source, $target, array $permission_types, $ugroup_mapping)
    {
        return $this->_permission_dao->duplicatePermissions($source, $target, $permission_types, PermissionsDao::DUPLICATE_NEW_PROJECT, $ugroup_mapping);
    }

    /**
     * Duplicate permission from one project to another (straight copy perms for dynamic do not copy static groups)
     *
     * @param int    $source
     * @param int    $target
     * @param array $permission_types
     *
     * @return bool
     */
    public function duplicateWithoutStatic($source, $target, array $permission_types)
    {
        return $this->_permission_dao->duplicatePermissions($source, $target, $permission_types, PermissionsDao::DUPLICATE_OTHER_PROJECT, false);
    }

    public function isPermissionExist($object_id, $ptype)
    {
        $dar = $this->_permission_dao->searchPermissionsByObjectId($object_id, array($ptype));
        return $dar->valid();
    }

    public function addPermission($permission_type, $object_id, $ugroup_id)
    {
        return $this->_permission_dao->addPermission($permission_type, $object_id, $ugroup_id);
    }

    public function revokePermissionForUGroup($permission_type, $object_id, $ugroup_id)
    {
        return $this->_permission_dao->removePermission($permission_type, $object_id, $ugroup_id);
    }

    public function addHistory($permission_type, $object_id, $group_id)
    {
        permission_add_history($group_id, $permission_type, $object_id);
    }

    /**
     * Clears permission for a given object
     *
     * @param String $permissionType Permission
     * @param String $objectId       Affected object's id
     *
     * @return bool
     */
    public function clearPermission($permissionType, $objectId)
    {
        return $this->_permission_dao->clearPermission($permissionType, $objectId);
    }

    public function isUgroupUsedByWikiService($ugroup_id, $project_id)
    {
        if ($this->_permission_dao->isThereAnExplicitWikiServicePermission($ugroup_id)) {
            return true;
        }

        if ($this->_permission_dao->doAllWikiServiceItemsHaveExplicitPermissions($project_id)) {
            return false;
        }

        return $this->_permission_dao->isThereADefaultWikiServicePermissionThatUsesUgroup($ugroup_id);
    }

    public function disableRestrictedAccess()
    {
        $this->_permission_dao->disableRestrictedAccess();
    }

    public function disableRestrictedAccessForObjectId(array $permission_type, $object_id)
    {
        $this->_permission_dao->disableRestrictedAccessForObjectId($permission_type, $object_id);
    }
}
