<?php
/*
 *  gitutil.git_read_revlist.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - get and format revision list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_rev_list.php');

function git_read_revlist($proj,$head,$count,$skip = NULL)
{
	$revs = trim(git_rev_list($proj,$head,$count, $skip));
	$revlist = explode("\n",$revs);
	return $revlist;
}

?>
