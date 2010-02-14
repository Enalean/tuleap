<?php
/*
 *  gitutil.git_get_type.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - get type
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_cat_file.php');

function git_get_type($hash)
{
	return trim(git_cat_file($hash,NULL,"-t"));
}

?>
