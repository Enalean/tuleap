<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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

require_once 'LDAP_ProjectDao.class.php';

class LDAP_ProjectManager
{
    /**
     * Return true if project uses LDAP for SVN authentication
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function hasSVNLDAPAuth($groupId)
    {
        if (!isset($this->cacheSVNLDAP[$groupId])) {
            $this->cacheSVNLDAP[$groupId] = $this->getDao()->hasLdapSvn($groupId);
        }
        return $this->cacheSVNLDAP[$groupId];
    }

    /**
     * Return true if project uses LDAP for SVN authentication (based on unix name)
     *
     * @param String $groupName
     *
     * @return bool
     */
    public function hasSVNLDAPAuthByName($groupName)
    {
        return $this->getDao()->hasLdapAuthByName($groupName);
    }

    /**
     * Enable LDAP based authentication for project SVN repository
     *
     * @param int $groupId
     *
     * @return Void
     */
    public function setLDAPAuthForSVN($groupId)
    {
        $this->getDao()->activateLdapAuthForProject($groupId);
    }

    /**
     * Wrapper for LDAP_ProjectDao
     *
     * @return LDAP_ProjectDao
     */
    public function getDao()
    {
        return new LDAP_ProjectDao(CodendiDataAccess::instance());
    }
}
