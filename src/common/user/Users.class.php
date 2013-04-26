<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * First class collection of users
 */
class Users {

    /** @var DataAccessResult */
    private $dar;

    public function __construct(DataAccessResult $dar = null) {
        $this->dar = $dar;
    }

    public function getDar() {
        return $this->dar;
    }

    public function reify() {
        $result = array();
        foreach ($this->dar as $row) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * 
     * @return array
     */
    public function getNames() {
        $result = array();
        foreach ($this->dar as $user) {
            $result[] = $user->getUserName();
        }
        return $result;
    }

    public function getLdapIds() {
        $result = array();
        foreach ($this->dar as $user) {
            $result[] = $user->getLdapId();
        }
        return $result;
    }

    /**
     *
     * @return Array string : Only the LDAP Ids non empty
     */
    public function getNonEmptyLdapIds() {
        return array_filter($this->getLdapIds());
    }

    /**
     *
     * @return Array string : Only the LDAP Logins non empty
     */
    public function getNonEmptyLdapLogins() {
        return array_filter($this->getLdapLogins());
    }

    public function getLdapLogins() {
        $ldap_logins = array();
        foreach ($this->dar as $user) {
            $login = '';
            $params = array('login' => &$login, 'user' => $user);
            EventManager::instance()->processEvent(Event::GET_LDAP_LOGIN_NAME_FOR_USER, $params);
            if ($login) {
                $ldap_logins[] = $login;
            }
        }
        return $ldap_logins;
    }
}
?>
