<?php
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
abstract class ForkCommands {
	public function fork($repos, User $user) {
		$forked = false;
		foreach($repos as $repo) {
			if ($repo && $repo->userCanRead($user)) {
				$this->dofork($repo, $user);
				$forked = true;
			}
		}
		return $forked;

	}
	public abstract function dofork($repo, User $user);
}
?>