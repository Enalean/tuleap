<?php
/*
 *  gitutil.read_info_ref.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read info on a ref
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.commands.php');
 require_once('gitutil.git_exec.php');

function read_info_ref($project, $type = "")
{
	$refs = array();
	$cmd = GIT_SHOW_REF . " --dereference";
	$showrefs = git_exec($project, $cmd);
	$lines = explode("\n",$showrefs);
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
