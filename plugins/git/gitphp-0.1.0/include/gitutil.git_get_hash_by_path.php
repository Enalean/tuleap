<?php
/*
 *  gitutil.git_get_hash_by_path.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - get hash from a path
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_ls_tree.php');

function git_get_hash_by_path($project,$base,$path,$type = null)
{
	$tree = $base;
	$parts = explode("/",$path);
	$partcount = count($parts);
	foreach ($parts as $i => $part) {
		$lsout = git_ls_tree($project, $tree);
		$entries = explode("\n",$lsout);
		foreach ($entries as $j => $line) {
			if (preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)$/",$line,$regs)) {
				if ($regs[4] == $part) {
					if ($i == ($partcount)-1)
						return $regs[3];
					if ($regs[2] == "tree")
						$tree = $regs[3];
					break;
				}
			}
		}
	}
}

?>
