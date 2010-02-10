<?php
/*
 * gitutil.git_read_blame.php
 * gitphp: A PHP git repository browser
 * Component: Git utility - get blame info
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 */

require_once('defs.commands.php');
require_once('gitutil.git_exec.php');

function git_read_blame($proj, $file, $rev = null)
{
	$cmd = GIT_BLAME . " -p";
	if ($rev)
		$cmd .= " " . $rev;
	return git_exec($proj, $cmd . " " . $file);
}

?>
