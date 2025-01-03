<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class Git_HTTP_CommandFactory
{
    public function getCommandForUser(Git_URL $url, ?PFO_User $user = null)
    {
        $command = $this->getGitHttpBackendCommand();
        if ($user !== null) {
            $command = new Git_HTTP_CommandGitolite3($user, $command);
        }
        $command->setPathInfo($url->getPathInfo());
        $command->setQueryString($url->getQueryString());
        return $command;
    }

    private function getGitHttpBackendCommand(): Git_HTTP_Command
    {
        if (Git_Exec::isGitTuleapInstalled()) {
            return new \Tuleap\Git\HTTP\CommandGitTuleapHttpBackend();
        }

        throw new RuntimeException('Cannot find a Git HTTP backend command');
    }
}
