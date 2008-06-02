<?php
/*
 *  gitutil.git_read_refs.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read refs
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('util.age_string.php');
 include_once('util.epochcmp.php');
 include_once('gitutil.git_get_type.php');
 include_once('gitutil.git_read_hash.php');
 include_once('gitutil.git_read_tag.php');
 include_once('gitutil.git_read_commit.php');

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
		$type = git_get_type($projectroot . $project, $ref_id);
		if ($type) {
			$ref_item = array();
			$ref_item['type'] = $type;
			$ref_item['id'] = $ref_id;
			$ref_item['epoch'] = 0;
			$ref_item['age'] = "unknown";

			if ($type == "tag") {
				$tag = git_read_tag($projectroot . $project, $ref_id);
				$ref_item['comment'] = $tag['comment'];
				if ($tag['type'] == "commit") {
					$co = git_read_commit($projectroot . $project, $tag['object']);
					$ref_item['epoch'] = $co['committer_epoch'];
					$ref_item['age'] = $co['age_string'];
				} else if (isset($tag['epoch'])) {
					$age = time() - $tag['epoch'];
					$ref_item['epoch'] = $tag['epoch'];
					$ref_item['age'] = age_string($age);
				}
				$ref_item['reftype'] = $tag['type'];
				$ref_item['name'] = $tag['name'];
				$ref_item['refid'] = $tag['object'];
			} else if ($type == "commit") {
				$co = git_read_commit($projectroot . $project, $ref_id);
				$ref_item['reftype'] = "commit";
				$ref_item['name'] = $ref_file;
				$ref_item['title'] = $co['title'];
				$ref_item['refid'] = $ref_id;
				$ref_item['epoch'] = $co['committer_epoch'];
				$ref_item['age'] = $co['age_string'];
			}
			$reflist[] = $ref_item;
		}
	}
	usort($reflist,"epochcmp");
	return $reflist;
}

?>
