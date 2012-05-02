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
class LDAP_ProjectGroupDao
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
     * @param Integer $groupId Project id
     *
     * @return DataAccessResult
     */
    function searchByGroupId($groupId)
    {
        $sql = 'SELECT * FROM plugin_ldap_project_group'.
            ' WHERE group_id = '.db_ei($groupId);
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
     * @param Integer $groupId Project id
     * @param String  $ldapDn  LDAP group distinguish name
     * 
     * @return Boolean
     */
    function linkGroupLdap($groupId, $ldapDn) 
    {
        $sql = 'INSERT INTO plugin_ldap_project_group (group_id, ldap_group_dn)'.
            ' VALUES ('.db_ei($groupId).',"'.db_es($ldapDn).'")';
        return $this->update($sql);
    }
    
    /**
     * Remove link between project members and a LDAP group
     *
     * @param Integer $groupId Project id
     * 
     * @return Boolean
     */
    function unlinkGroupLdap($groupId) 
    {
        $sql = 'DELETE FROM plugin_ldap_project_group'.
            ' WHERE group_id = '.db_ei($groupId);
        return $this->update($sql);
    }
    
    /**
     * Object oriented wrapper for account_add_user_to_group
     *
     * @param Integer $groupId Project id
     * @param String  $name    User unix name
     * 
     * @return Boolean
     */
    function addUserToGroup($groupId, $name)
    {
        include_once 'account.php';
        return account_add_user_to_group($groupId, $name);
    }

    /**
     * Object oriented wrapper for account_remove_user_from_group
     *
     * @param Integer $groupId Project id
     * @param Integer $userId  User id
     * 
     * @return Boolean
     */
    function removeUserFromGroup($groupId, $userId)
    {
        include_once 'account.php';
        return account_remove_user_from_group($groupId, $userId);
    }
}

?>
