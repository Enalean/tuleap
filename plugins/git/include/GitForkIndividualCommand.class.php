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

require_once('GitForkCommand.class.php');

class GitForkIndividualCommand extends GitForkCommand {
    /**
     * Constructor
     * 
     * @param String $path the destination path of the fork
     */
    public function __construct($path) {
        $this->path = $path;
    }
    /**
     * call fork with right paramters
     * 
     * @param GitRepository $repo Git Repository to fork  
     * @param User          $user User which ask for a fork
     *
     * @see GitForkCommand::dofork()
     * @return null
     */
    public function dofork(GitRepository $repo, User $user) {
        $this->forkRepo($repo, $user,  $this->path, GitRepository::REPO_SCOPE_INDIVIDUAL, $repo->getProject());
    }
}
?>