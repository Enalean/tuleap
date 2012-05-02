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
 * Interact with plugin_ldap_user table in database
 *
 */
class LDAP_UserDao
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
     *
     * @param $userIds
     *
     * @return DataAccessResult
     */
    function searchLdapLoginFromUserIds(array $userIds) {
        $sql = 'SELECT ldap_uid'.
               ' FROM plugin_ldap_user '.
               ' WHERE user_id IN ('.implode(',', $userIds).')';
        return $this->retrieve($sql);
    }

    /**
     * Check if user has already logged in on Web platform
     *
     * @param Integer $userId Id of the user
     * 
     * @return Boolean
     */
    function alreadyLoggedInOnce($userId) 
    {
        $sql = 'SELECT NULL'.
            ' FROM plugin_ldap_user ldap_u'.
            '   INNER JOIN user u USING (user_id)'.
            ' WHERE u.user_id = '.$this->da->escapeInt($userId).
            ' AND u.ldap_id != ""'.
            ' AND u.ldap_id IS NOT NULL'.
            ' AND login_confirmation_date = 0';
        
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Create new entry for LDAP user.
     *
     * @param Integer $userId Id of the user
     * @param Integer $date   Date of creation (timestamp)
     * 
     * @return Boolean
     */
    function createLdapUser($userId, $date=0, $ldap_uid="") 
    {
        $sql = 'INSERT INTO plugin_ldap_user'.
            '(user_id, login_confirmation_date, ldap_uid)'.
            ' VALUES '.
            '('.db_ei($userId).','.db_ei($date).',"'.db_es($ldap_uid).'")';
        return $this->update($sql);
    }
    
    /**
     * Record when user log on Codendi
     *
     * @param Integer $userId Id of the user
     * @param Integer $date   Date of login (timestamp)
     * 
     * @return Boolean
     */
    function setLoginDate($userId, $date)
    {
        $sql = 'UPDATE plugin_ldap_user'.
            ' SET login_confirmation_date = '.db_ei($date).
            ' WHERE user_id = '.db_ei($userId);
        $updated = $this->update($sql);
        if (!$updated) {
            // Try to insert
            $updated = $this->createLdapUser($userId, $date);
        }
        return $updated;
    }
    
    /**
     * Check if a given name is not already a user name or a project name
     * 
     * This should be in UserManager
     * 
     * @param String $name Name to test
     * 
     * @return Boolean
     */
    function userNameIsAvailable($name) 
    {
        $sql = 'SELECT user_name'.
            ' FROM user'.
            ' WHERE user_name LIKE '.$this->da->quoteSmart($name, array('force_string'));
        if ($this->retrieve($sql)->rowCount() === 0) {
            $sql = 'SELECT group_id'.
                ' FROM groups'.
                ' WHERE unix_group_name LIKE '.$this->da->quoteSmart($name, array('force_string'));
            if ($this->retrieve($sql)->rowCount() === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update LDAP login of given user
     * 
     * @param Integer $userId  User ID to update
     * @param Integer $ldapUid LDAP login of the user
     * 
     * @return Boolean
     */
    function updateLdapUid($userId, $ldapUid)
    {
        $sql = 'UPDATE plugin_ldap_user'.
               ' SET ldap_uid='.$this->da->quoteSmart($ldapUid, array('force_string')).
               ' WHERE user_id = '.$this->da->quoteSmart($userId);
        return $this->update($sql);
    }
}

?>
