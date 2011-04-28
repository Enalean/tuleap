<?php
/*
 *  util.agecmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project age comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function agecmp($a,$b)
{
	if ($a["age"] == $b["age"])
		return 0;
	return ($a["age"] < $b["age"] ? -1 : 1);
}

?>
