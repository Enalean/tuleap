<?php
require_once('GitForkCommands.class.php');
class ForkIndividualCommand extends ForkCommands {
	public function __construct($path) {
		$this->path = $path;
	}
	public function dofork($repo, User $user) {
		$repo->forkIndividual($this->path, $user);
	}
}
?>