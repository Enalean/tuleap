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
 * Wrapper for ldap user
 */
class LDAP_User
{
    /** @var PFUser */
    private $user;

    /** @var LDAPResult */
    private $ldap_result;

    public function __construct(PFUser $user, LDAPResult $ldap_result)
    {
        $this->user        = $user;
        $this->ldap_result = $ldap_result;
    }

    /**
     * @return String
     */
    public function getUid()
    {
        return strtolower($this->ldap_result->getLogin());
    }

    public function getRealName()
    {
        return $this->user->getRealName();
    }

    public function getEmail()
    {
        return $this->user->getEmail();
    }
}
