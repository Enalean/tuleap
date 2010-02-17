<?php
/*
 *  gitutil.read_info_ref.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read info on a ref
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once(GITPHP_INCLUDEDIR . 'git/GitExe.class.php');

function read_info_ref($type = "")
{
	global $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	$exe = new GitPHP_GitExe($gitphp_current_project);
	
	$showrefs = $exe->Execute(GIT_SHOW_REF, array('--dereference'));

	$lines = explode("\n",$showrefs);
	$refs = array();
	foreach ($lines as $no => $line) {
		if (preg_match("`^([0-9a-fA-F]{40}) .*" . $type . "/([^\^]+)`",$line,$regs)) {
			if (isset($refs[$regs[1]]))
				$refs[$regs[1]] .= " / " . $regs[2];
			else
				$refs[$regs[1]] = $regs[2];
		}
	}
	return $refs;
}

?>
