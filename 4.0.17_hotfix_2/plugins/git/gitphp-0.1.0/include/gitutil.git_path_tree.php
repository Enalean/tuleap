<?php
/*
 *  gitutil.git_path_tree.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - path tree
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_get_hash_by_path.php');

function git_path_tree($project,$base,$filename)
{
	if (strlen($filename) < 1)
		return null;
	$path = array();
	$path['full'] = $filename;
	$spath = $filename;
	$spos = strrpos($spath, "/");
	if ($spos !== false)
		$spath = substr($spath, $spos+1);
	$path['short'] = $spath;
	$path['tree'] = git_get_hash_by_path($project, $base, $filename);
	return $path;
}

?>
