<?php
/*
 *  gitutil.git_read_refs.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read refs
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.epochcmp.php');
 require_once('gitutil.git_read_hash.php');
 require_once('gitutil.git_read_ref.php');
 require_once('gitutil.git_read_packed_refs.php');

function git_read_refs($projectroot,$project,$refdir)
{
	if (!is_dir($projectroot . $project . "/" . $refdir))
		return null;
	$refs = array();
	if ($dh = opendir($projectroot . $project . "/" . $refdir)) {
		while (($dir = readdir($dh)) !== false) {
			if (strpos($dir,'.') !== 0) {
				if (is_dir($projectroot . $project . "/" . $refdir . "/" . $dir)) {
					if ($dh2 = opendir($projectroot . $project . "/" . $refdir . "/" . $dir)) {
						while (($dir2 = readdir($dh2)) !== false) {
							if (strpos($dir2,'.') !== 0)
								$refs[] = $dir . "/" . $dir2;
						}
						closedir($dh2);
					}
				}
				$refs[] = $dir;
			}
		}
		closedir($dh);
	} else
		return null;
	$reflist = array();
	foreach ($refs as $i => $ref_file) {
		$ref_id = git_read_hash($projectroot . $project . "/" . $refdir . "/" . $ref_file);
		$refobj = git_read_ref($projectroot, $project, $ref_id, $ref_file);
		if (isset($refobj))
			$reflist[] = $refobj;
	}

	$packedrefs = git_read_packed_refs($projectroot, $project, $refdir);
	if (isset($packedrefs) && count($packedrefs) > 0) {
		foreach ($packedrefs as $packedref) {
			$found = false;
			foreach ($reflist as $ref) {
				if (strcmp($ref["name"], $packedref["name"]) === 0) {
					$found = true;
					break;
				}
			}
			if (!$found)
				$reflist[] = $packedref;
		}
	}

	usort($reflist,"epochcmp");
	return $reflist;
}

?>
