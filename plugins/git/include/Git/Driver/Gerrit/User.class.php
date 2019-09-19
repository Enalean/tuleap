<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013, 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * There is no type hinting on constructor to avoid having to load LDAP plugin
 * when usage of Git plugin without Gerrit
 */
class Git_Driver_Gerrit_User
{
    /**
     * @var LDAP_User
     */
    private $ldap_user;

    /**
     * @param LDAP_User $ldap_user
     */
    public function __construct(/*no type*/$ldap_user)
    {
        $this->ldap_user = $ldap_user;
    }

    /**
     * @return String
     */
    public function getSSHUserName()
    {
        return $this->ldap_user->getUid();
    }

    /**
     * @return String
     */
    public function getWebUserName()
    {
        return $this->ldap_user->getUid();
    }

    public function getRealName()
    {
        return $this->ldap_user->getRealName();
    }

    public function getEmail()
    {
        return $this->ldap_user->getEmail();
    }
}
