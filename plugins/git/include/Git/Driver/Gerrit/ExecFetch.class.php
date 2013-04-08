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
 * Manage update of Gitolite repositories by fetching remotes
 *
 * http://stackoverflow.com/questions/5559321/fetch-all-in-a-git-bare-repository-doesnt-synchronize-local-branches-to-the/5559586#5559586
 * Alternative with gitolite v3: https://github.com/sitaramc/gitolite/blob/master/src/triggers/upstream
 */
class Git_Driver_Gerrit_ExecFetch extends Git_Exec {
    private $remote_name;

    public function __construct($path, $remote_name) {
        parent::__construct($path);
        $this->remote_name = $remote_name;
    }

    /**
     * List remotes' heads
     *
     * @return Array
     */
    public function lsRemoteHeads() {
        $remote_heads = array();
        $this->execInPath("git-ls-remote --heads $this->remote_name", $remote_heads);
        return $remote_heads;
    }

    /**
     * Fetch the remote silently
     *
     * @return Boolean
     */
    public function fetch() {
        return $this->gitCmd("git fetch $this->remote_name -q");
    }

    /**
     * Update a local branch from remote
     *
     * @param String $branch_name
     * @return type
     */
    public function updateRef($branch_name) {
        return $this->gitCmd("git update-ref refs/heads/$branch_name refs/remotes/$this->remote_name/$branch_name");
    }
}

?>
