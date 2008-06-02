<?php
/*
 *  util.descrcmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project description comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_project_descr.php');

function descrcmp($a,$b)
{
	global $gitphp_conf;
	return strcmp(git_project_descr($gitphp_conf['projectroot'],$a),git_project_descr($gitphp_conf['projectroot'],$b));
}

?>
