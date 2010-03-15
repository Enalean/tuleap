<?php
/*
 *  gitutil.git_read_revlist.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - get and format revision list
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_rev_list.php');
 require_once('gitutil.git_version.php');

function git_read_revlist($proj,$head,$count = NULL,$skip = NULL,$header = FALSE,$parents = FALSE,$greptype = NULL, $search = NULL)
{
	$passedskip = $skip;
	$passedcount = $count;
	$canskip = true;

	if (isset($skip) && ($skip > 0)) {
		$version = git_version();
		if (isset($version) && (strlen($version) > 0)) {
			$splitver = explode(".",$version);

			/* Skip only appears in git >= 1.5.0 */
			if (($splitver[0] < 1) || (($splitver[0] == 1) && ($splitver[1] < 5))) {
				$canskip = false;
				$passedskip = null;
				$passedcount += $skip;
			}
		}
	}

	$revs = trim(git_rev_list($proj,$head, $passedcount, $passedskip, $header, $parents, $greptype, $search));
	$revlist = explode("\n",$revs);

	if ((!$canskip) && ($skip > 0)) {
		$tmp = array();
		$revcount = count($revlist);
		for ($i = $skip; $i < $revcount; ++$i)
			$tmp[] = $revlist[$i];
		return $tmp;
	}

	return $revlist;
}

?>
