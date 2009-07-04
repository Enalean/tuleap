<?php
/*
 *  gitutil.git_diff.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - diff
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_cat_file.php');

function git_diff($proj,$from,$from_name,$to,$to_name)
{
	global $gitphp_conf;
	$from_tmp = "/dev/null";
	$to_tmp = "/dev/null";
	if (function_exists('posix_getpid'))
		$pid = posix_getpid();
	else
		$pid = rand();
	if (isset($from)) {
		$from_tmp = $gitphp_conf['gittmp'] . "gitphp_" . $pid . "_from";
		git_cat_file($proj,$from,$from_tmp);
	}
	if (isset($to)) {
		$to_tmp = $gitphp_conf['gittmp'] . "gitphp_" . $pid . "_to";
		git_cat_file($proj,$to,$to_tmp);
	}
	$out = shell_exec($gitphp_conf['diffbin'] . " -u -p -L '" . $from_name . "' -L '" . $to_name . "' " . $from_tmp . " " . $to_tmp);
	if (isset($from))
		unlink($from_tmp);
	if (isset($to))
		unlink($to_tmp);
	return $out;
}

?>
