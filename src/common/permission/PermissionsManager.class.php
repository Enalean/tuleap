<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/dao/PermissionsDao.class.php');
require_once('common/dao/CodendiDataAccess.class.php');

/**
* Manage permissions
*
*/
class PermissionsManager {
    /**
     * @var PermissionsDao
     */
    var $_permission_dao;
    var $_permissions;
    var $_ugroups_for_user;
    
    private static $_permissionmanager_instance;
    
    function PermissionsManager($permission_dao) {
        $this->_permission_dao   = $permission_dao;
        $this->_permissions      = array();
        $this->_ugroups_for_user = array();
    }

    /**
     * The manager is a singleton
     *
     * @return PermissionsManager
     */
    public static function instance() {
        if (!self::$_permissionmanager_instance) {
            self::$_permissionmanager_instance = new PermissionsManager(new PermissionsDAO(CodendiDataAccess::instance()));
        }
        return self::$_permissionmanager_instance;
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
    * @return boolean 
    */
    function userHasPermission($object_id, $permission_type, $ugroups) {
        if (!isset($this->_permissions[$object_id])) {
            $this->_permissions[$object_id] = array();
        }
        
        if (count(array_diff($ugroups, array_keys($this->_permissions[$object_id]))) > 0) {
            $this->_retrievePermissions($object_id, $ugroups);
        }
        //now we search for $permission_type
        $has_permission = false;
        reset($ugroups);
        while (!$has_permission && (list(,$ugroup) = each($ugroups))) {
		        	
            if (isset($this->_permissions[$object_id][$ugroup])) {
                $has_permission = in_array($permission_type, $this->_permissions[$object_id][$ugroup]);
            }
        }
        return $has_permission;
    }
    
    /**
    * Returns all permissions for ugroups for a given object
    * WARNING: since object_ids are not unique, some permissions returned 
    * might not be relevant for the given object
    * 
    * @access public
    * 
    * @param  int     $object_id  The id of the object
    * @param  array   $ugroups    A list of ugroups we want to see in permissions
    */
    function getPermissionsAndUgroupsByObjectid($object_id, $ugroups) {
        $this->_retrievePermissions($object_id); //Why don't we pass $ugroups ???
        $perms = array();
        if (isset($this->_permissions[$object_id])) {
            foreach($this->_permissions[$object_id] as $ugroup_id => $permissions) {
                foreach($permissions as $perm) {
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
     function getUgroupNameByObjectIdAndPermissionType($object_id, $permission_type){
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
            if (strpos($new_name, "ugroup_") === 0 && strpos($new_name, "_name_key")+strlen("_name_key") === strlen($new_name)) {
                $new_name = $GLOBALS['Language']->getText('project_ugroup', $new_name);
            }
            $ugroups_name[] = $new_name;
            $dar->next();
        }
        return $ugroups_name;
     }
     
    /**
     * Returns all ugroup id for a given object_id and permission_type
     * @param  int     $object_id       The id of the object
     * @param  string  $permission_type The type of permission asked
     */
     function getUgroupIdByObjectIdAndPermissionType($object_id, $permission_type){
         $dar = $this->_permission_dao->searchUgroupByObjectIdAndPermissionType($object_id, $permission_type, false);
         if ($dar->isError() || !$dar->valid()) {
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
     public function getDefaults($permissionType, $withName = true) {
         return $this->_permission_dao->searchDefaults($permissionType, $withName);
     }

     /**
      * Return the list of ugroups authorized to access the given object with the given permission_type
      *
      * If no specific permissions set, returns the defaults.
      *
      * @param Integer $objectId
      * @param String  $permissionType
      *
      * @return DataAccessResult
      */
     public function getAuthorizedUgroups($objectId, $permissionType, $withName = true) {
         $dar = $this->_permission_dao->searchUgroupByObjectIdAndPermissionType($objectId, $permissionType, $withName);
        if ($dar && $dar->rowCount() > 0) {
            return $dar;
        } else {
            return $this->getDefaults($permissionType, $withName);
        }
     }

    /**
    * Returns true if user has full permissions in all cases
    * 
    * @access protected
    * 
    * @param  int     $user_id  The id of the user
    */
    function _userHasFullPermission($user_id = 0) {
        return (user_isloggedin() && user_is_super_user());
    }
    
    function _buildPermissionsCache(&$dar, &$ugroups) {
        while ($row =& $dar->getRow()) {
            if (!isset($this->_permissions[$row['object_id']])) {
                $this->_permissions[$row['object_id']] = array();
            }
            foreach($ugroups as $ugroup) {
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
    function _retrievePermissions($object_id, $ugroups = array()) {
        $tracker_field_id = explode('#', $object_id); //An artifact field ?
        if (count($tracker_field_id) > 1) {
            $dar =& $this->_permission_dao->searchPermissionsByArtifactFieldId($tracker_field_id[0]);
        } else {
            $dar =& $this->_permission_dao->searchPermissionsByObjectId($object_id);
        }
        $this->_buildPermissionsCache($dar, $ugroups);
    }

    function _retrievePermissionsArray($object_id, $ptype, $ugroups = array()) {
        //$tracker_field_id = explode('#', $object_id); //An artifact field ?
        //if (count($tracker_field_id) > 1) {
        //    $dar =& $this->_permission_dao->searchPermissionsByArtifactFieldId($tracker_field_id[0]);
        //} else {
        $dar =& $this->_permission_dao->searchPermissionsByObjectId($object_id, $ptype);
            //}
        $this->_buildPermissionsCache($dar, $ugroups);
    }
    
    function clonePermissions($source, $target, $perms, $toGroupId=0) {
        return $this->_permission_dao->clonePermissions($source, $target, $perms, $toGroupId);
    }
    
    function isPermissionExist($object_id, $ptype){    	
    	$dar = $this->_permission_dao->searchPermissionsByObjectId($object_id, array($ptype));
    	return $dar->valid();
    }
    
    function addPermission($permission_type, $object_id, $ugroup_id){
        return $this->_permission_dao->addPermission($permission_type, $object_id, $ugroup_id);
    }

    /**
     * Clears permission for a given object
     * 
     * @param String $permissionType Permission
     * @param String $objectId       Affected object's id
     * 
     * @return Boolean
     */
    function clearPermission($permissionType, $objectId) {
        return $this->_permission_dao->clearPermission($permissionType, $objectId);
    }

}
?>