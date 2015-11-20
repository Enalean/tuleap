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
use Logger;

class ReadyToBeImportedUsersCollection {

    private $users = array();

    public function add(ReadyToBeImportedUser $user) {
        $this->users[$user->getUserName()] = $user;
    }

    /** @return User\XML\Import\User */
    public function getUser($username) {
        if (! isset($this->users[$username])) {
            throw new UserNotFoundException();
        }

        return $this->users[$username];
    }

    public function process(UserManager $user_manager, Logger $logger) {
        foreach ($this->users as $user) {
            $user->process($user_manager, $logger);
        }
    }
}
