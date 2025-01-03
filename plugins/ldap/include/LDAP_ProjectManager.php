<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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

use Tuleap\LDAP\Project\UsesLDAPAuthProvider;

class LDAP_ProjectManager implements UsesLDAPAuthProvider
{
    /**
     * @var array<int, bool>
     */
    private array $cacheSVNLDAP = [];

    /**
     * Return true if project uses LDAP for SVN authentication
     */
    public function hasSVNLDAPAuth(int $project_id): bool
    {
        if (! isset($this->cacheSVNLDAP[$project_id])) {
            $this->cacheSVNLDAP[$project_id] = $this->getDao()->hasLdapSvn($project_id);
        }
        return $this->cacheSVNLDAP[$project_id];
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
