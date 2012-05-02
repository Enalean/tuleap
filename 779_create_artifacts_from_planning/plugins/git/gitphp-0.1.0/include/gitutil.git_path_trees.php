<?php
/*
 *  gitutil.git_path_trees.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - path trees
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_path_tree.php');
require_once('gitutil.git_get_hash_by_path.php');

function git_path_trees($project,$base,$filename)
{
	$paths = array();
	$path = git_path_tree($project,$base,$filename);
	if ($path != null)
		$paths[] = $path;
	$pos = strrpos($filename, "/");
	while ($pos !== false) {
		$filename = substr($filename,0,$pos);
		
		$path = git_path_tree($project, $base, $filename);
		if ($path != null)
			$paths[] = $path;

		$pos = strrpos($filename, "/");
	}
	return array_reverse($paths);
}

?>
