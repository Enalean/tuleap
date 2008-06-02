<?php
/*
 *  util.ownercmp.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - project owner comparison function
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_project_owner.php');

function ownercmp($a,$b)
{
	global $gitphp_conf;
	return strcmp(git_project_owner($gitphp_conf['projectroot'],$a),git_project_owner($gitphp_conf['projectroot'],$b));
}

?>
