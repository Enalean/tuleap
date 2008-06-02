<?php
/*
 *  util.epochcmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - epoch comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function epochcmp($a,$b)
{
	if ($a['epoch'] == $b['epoch'])
		return 0;
	return ($a['epoch'] < $b['epoch']) ? 1 : -1;
}

?>
