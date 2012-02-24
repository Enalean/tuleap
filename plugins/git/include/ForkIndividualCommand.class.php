<?php
require_once('ForkCommands.class.php');
class ForkIndividualCommand extends ForkCommands {
	public function __construct($path) {
		$this->path = $path;
	}
	public function dofork($repo, User $user) {
        $repo->fork($user, $this->path, GitRepository::REPO_SCOPE_INDIVIDUAL, $repo->getProject());
	}
}
?>