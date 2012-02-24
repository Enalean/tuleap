<?php
require_once('ForkCommands.class.php');
class ForkExternalCommand extends ForkCommands {
	public function __construct($to_project) {
		$this->to_project = $to_project;
	}

	public function dofork(GitRepository $repo, User $user) {
        $repo->fork($user, '', GitRepository::REPO_SCOPE_PROJECT, $this->to_project);
	}
}
?>