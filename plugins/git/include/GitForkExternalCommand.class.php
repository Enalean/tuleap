<?php
require_once('GitForkCommands.class.php');
class ForkExternalCommand extends ForkCommands {
	public function __construct($to_project) {
		$this->to_project = $to_project;
	}

	public function dofork($repo, User $user) {
		$repo->forkCrossProject($this->to_project, $user);
	}
}
?>