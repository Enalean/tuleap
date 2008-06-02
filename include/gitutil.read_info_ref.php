<?php
/*
 *  gitutil.read_info_ref.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read info on a ref
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function read_info_ref($project, $type = "")
{
	$refs = array();
	$lines = file($project);
	foreach ($lines as $no => $line) {
		if (ereg("^([0-9a-fA-F]{40})\t.*" . $type . "/([^\^]+)",$line,$regs)) {
			if ($isset($refs[$regs[1]]))
				$refs[$regs[1]] .= " / " . $regs[2];
			else
				$refs[$regs[1]] = $regs[2];
		}
	}
	return $refs;
}

?>
