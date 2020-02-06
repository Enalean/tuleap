<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
namespace User\XML\Import;

use UserManager;
use Psr\Log\LoggerInterface;

class ReadyToBeImportedUsersCollection
{

    private $users_by_name    = array();
    private $users_by_id      = array();
    private $users_by_ldap_id = array();

    public function add(ReadyToBeImportedUser $user, $user_id, $username, $ldap_id)
    {
        $this->users_by_name[$username] = $user;
        $this->users_by_id[$user_id]    = $user;

        if ($ldap_id) {
            $this->users_by_ldap_id[$ldap_id] = $user;
        }
    }

    /** @return User\XML\Import\User */
    public function getUserByUserName($username)
    {
        if (! isset($this->users_by_name[$username])) {
            throw new UserNotFoundException();
        }

        return $this->users_by_name[$username];
    }

    /** @return User\XML\Import\User */
    public function getUserById($id)
    {
        if (! isset($this->users_by_id[$id])) {
            throw new UserNotFoundException();
        }

        return $this->users_by_id[$id];
    }

    /** @return User\XML\Import\User */
    public function getUserByLdapId($ldap_id)
    {
        if (! isset($this->users_by_ldap_id[$ldap_id])) {
            throw new UserNotFoundException();
        }

        return $this->users_by_ldap_id[$ldap_id];
    }

    public function process(UserManager $user_manager, LoggerInterface $logger)
    {
        foreach ($this->users_by_name as $user) {
            $user->process($user_manager, $logger);
        }
    }
}
