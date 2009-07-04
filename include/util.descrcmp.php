<?php
/*
 *  util.descrcmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project description comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function descrcmp($a,$b)
{
	return strcmp($a["descr"],$b["descr"]);
}

?>
