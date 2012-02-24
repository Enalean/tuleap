<?php
require_once('GitForkCommands.class.php');
class GitForkIndividualCommand extends GitForkCommands {
	public function __construct($path) {
		$this->path = $path;
	}
	public function dofork(GitRepository $repo, User $user) {
        $repo->fork($user, $this->path, GitRepository::REPO_SCOPE_INDIVIDUAL, $repo->getProject());
	}
}
?>