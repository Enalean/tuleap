<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/dao/include/DataAccessObject.class.php';

/**
 * Database access to ldap user group
 *
 */
class LDAP_UserGroupDao
extends DataAccessObject
{

    /**
     * Constructor
     *
     * @param DataAccess $da Data access details
     * 
     * @return LDAP_UserDao
     */
    function __construct(DataAccess $da) 
    {
        parent::__construct($da);
    }

    /**
     * Search one user group by id
     *
     * @param Integer $ugroupId User group id
     * 
     * @return DataAccessResult
     */
    function searchByGroupId($ugroupId) 
    {
        $sql = 'SELECT * FROM plugin_ldap_ugroup'.
            ' WHERE ugroup_id = '.db_ei($ugroupId);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return $dar->getRow();
        } else {
            return false;
        }
    }
    
    /**
     * Associate one Codendi user group to an LDAP group
     *
     * @param Integer $ugroupId    Codendi user group id 
     * @param String  $ldapGroupDn LDAP group distinguish name
     * 
     * @return Boolean
     */
    function linkGroupLdap($ugroupId, $ldapGroupDn) 
    {
        $sql = 'INSERT INTO plugin_ldap_ugroup (ugroup_id, ldap_group_dn)'.
            ' VALUES ('.db_ei($ugroupId).',"'.db_es($ldapGroupDn).'")';
        return $this->update($sql);
    }
    
    /**
     * Unlink one Codendi user group with it's LDAP group
     *
     * @param Integer $ugroupId Codendi user group id
     * 
     * @return Boolean
     */
    function unlinkGroupLdap($ugroupId) 
    {
        $sql = 'DELETE FROM plugin_ldap_ugroup'.
            ' WHERE ugroup_id = '.db_ei($ugroupId);
        return $this->update($sql);
    }

    /**
     * Object oriented wrapper for ugroup_add_user_to_ugroup
     *
     * @param Integer $ugroupId Codendi user group id
     * @param Integer $userId   Codendi user id
     * 
     * @return void
     */
    function addUserToGroup($ugroupId, $userId) 
    {
        $row = $this->_getUgroupRow($ugroupId);
        return ugroup_add_user_to_ugroup($row['group_id'], $ugroupId, $userId);
    }

    /**
     * Object oriented wrapper for ugroup_remove_user_from_ugroup
     *
     * @param Integer $ugroupId Codendi user group id
     * @param Integer $userId   Codendi user id
     *
     * @return void
     */
    function removeUserFromGroup($ugroupId, $userId)
    {
        $row = $this->_getUgroupRow($ugroupId);
        return ugroup_remove_user_from_ugroup($row['group_id'], $ugroupId, $userId);
    }

    /**
     * Object oriented wrapper for ugroup_db_get_ugroup
     *
     * @param Integer $ugroupId Codendi user group id
     * 
     * @return array
     */
    function _getUgroupRow($ugroupId) 
    {
        include_once 'www/project/admin/ugroup_utils.php';
        $Language = $GLOBALS['Language'];
        $res = ugroup_db_get_ugroup($ugroupId);
        return db_fetch_array($res);
    }
    
    function getMembersId($id)
    {
        include_once 'www/project/admin/ugroup_utils.php';
        $ret = array();
        $sql = ugroup_db_get_members($id);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            foreach($dar as $row) {
                $ret[$row['user_id']] = $row['user_id']; 
            }
        }
        return $ret;
    }
}

?>
