<?php
require_once('ForkCommands.class.php');
class ForkExternalCommand extends ForkCommands {
	public function __construct($to_project) {
		$this->to_project = $to_project;
	}

	public function dofork(GitRepository $repo, User $user) {
		$repo->forkCrossProject($this->to_project, $user);
	}
}
?>