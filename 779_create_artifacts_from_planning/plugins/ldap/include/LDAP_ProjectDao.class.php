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
 * Access to LDAP project settings
 *
 */
class LDAP_ProjectDao
extends DataAccessObject
{

    /**
     * Constructor
     *
     * @param DataAccess $da Data access details
     * 
     * @return LDAP_ProjectDao
     */
    function __construct(DataAccess $da) 
    {
        parent::__construct($da);
    }

    /**
     * Check if given project has its svn repository with LDAP authentication
     *
     * @param Integer $groupId Project id
     * 
     * @return Boolean
     */
    function hasLdapSvn($groupId) 
    {
        $sql = 'SELECT NULL'.
            ' FROM plugin_ldap_svn_repository'.
            ' WHERE group_id = '.$this->da->escapeInt($groupId);
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Enable LDAP based authentication for given project
     *
     * @param Integer $groupId Project id
     */
    function activateLdapAuthForProject($groupId)
    {
        $sql = 'INSERT INTO plugin_ldap_svn_repository(group_id, ldap_auth)'.
            ' VALUES ('.$this->da->escapeInt($groupId).',1)';
        $this->update($sql);
    }

    function hasLdapAuthByName($groupName) {
        $sql = 'SELECT NULL'.
            ' FROM plugin_ldap_svn_repository'.
            ' JOIN groups USING (group_id)'.
            ' WHERE unix_group_name='.$this->da->quoteSmart($groupName);
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

}

?>
