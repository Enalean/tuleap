<?php
/*
 *  gitutil.git_read_hash.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read a hash
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function git_read_hash($path)
{
	return file_get_contents($path);
}

?>
