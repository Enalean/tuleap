<?php
/*
 *  gitutil.read_info_ref.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read info on a ref
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('defs.commands.php');

function read_info_ref($project, $type = "")
{
	global $gitphp_conf;
	$refs = array();
	$showrefs = shell_exec("env GIT_DIR=" . $project . " " . $gitphp_conf['gitbin'] . GIT_SHOW_REF . " --dereference");
	$lines = explode("\n",$showrefs);
	foreach ($lines as $no => $line) {
		if (ereg("^([0-9a-fA-F]{40}) .*" . $type . "/([^\^]+)",$line,$regs)) {
			if (isset($refs[$regs[1]]))
				$refs[$regs[1]] .= " / " . $regs[2];
			else
				$refs[$regs[1]] = $regs[2];
		}
	}
	return $refs;
}

?>
