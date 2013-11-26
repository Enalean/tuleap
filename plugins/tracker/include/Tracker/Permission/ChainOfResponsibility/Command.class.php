<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * I am a command in a chain of responsability
 *
 * @see http://en.wikipedia.org/wiki/Chain-of-responsibility_pattern
 */
abstract class Tracker_Permission_Command {

    const PERMISSION_PREFIX = 'permissions_';

    const PERMISSION_ADMIN                  = 'ADMIN';
    const PERMISSION_FULL                   = 'FULL';
    const PERMISSION_ASSIGNEE               = 'ASSIGNEE';
    const PERMISSION_SUBMITTER              = 'SUBMITTER';
    const PERMISSION_ASSIGNEE_AND_SUBMITTER = 'SUBMITTER_N_ASSIGNEE';
    const PERMISSION_NONE                   = 'NONE';

    /** @var Tracker_Permission_Command */
    private $next_command;

    public function __construct() {
        $this->setNextCommand(new Tracker_Permission_ChainOfResponsibility_DoNothing());
    }

    public function setNextCommand(Tracker_Permission_Command $next_command) {
        $this->next_command = $next_command;
    }

    public function getNextCommand() {
        return $this->next_command;
    }

    public function executeNextCommand(Codendi_Request $request, Tracker_Permission_PermissionSetter $permissions_setter) {
        $this->next_command->execute($request, $permissions_setter);
    }

    abstract public function execute(Codendi_Request $request, Tracker_Permission_PermissionSetter $permissions_setter);
}
