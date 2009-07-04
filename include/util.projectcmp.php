<?php
/*
 *  util.projectcmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project name comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function projectcmp($a,$b)
{
	return strcmp($a["project"],$b["project"]);
}

?>
