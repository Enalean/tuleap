<?php
/*
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
class SystemEvent_EDIT_SSH_KEYS extends SystemEvent {
    public function process() {
        $user_id = $this->getIdFromParam($this->parameters);
        if ($user = UserManager::instance()->getUserById($user_id)) {
            if (!BackendSystem::instance()->dumpSSHKeysForUser($user)) {
                $this->error("Could not dump ssh keys for user ". $user->getUserName());
                return false;
            }
        } else {
            $this->error("Could not create/initialize user object");
            return false;
        }
        $this->done();
        return true;
    }
}
?>
