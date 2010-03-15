<?php
/*
 *  util.ownercmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project owner comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function ownercmp($a,$b)
{
	return strcmp($a["owner"],$b["owner"]);
}

?>
