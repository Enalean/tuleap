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
* Fork a bunch of repositories in a project for a given user
 *
* Repositories that the user cannot access won't be forked as well as
* those that don't belong to the project.
*
* @param int    $groupId    The project id
* @param array  $repos_ids  The array of id of repositories to fork
* @param string $to_project The path where the new repositories will live
* @param User   $user       The owner of those new repositories
* @param Layout $response   The response object
*
* @return bool false if no repository has been cloned
*/
abstract class GitForkCommands {
    
	public function fork($repos, User $user) {
		$forked = false;
 		$repos = $this->filterNullRepos($repos);
		foreach($repos as $repo) {
			if ($repo->userCanRead($user)) {
				$this->dofork($repo, $user);
				$forked = true;
			}
		}
		return $forked;
	}
	
	protected function filterNullRepos(array $repos) {
	    return array_diff($repos, array(null));
	}
	
	public abstract function dofork(GitRepository $repo, User $user);
}
?>