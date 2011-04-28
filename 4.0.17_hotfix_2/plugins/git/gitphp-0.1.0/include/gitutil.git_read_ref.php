<?php
/*
 *  gitutil.git_read_ref.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read single ref
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.age_string.php');
 require_once('gitutil.git_get_type.php');
 require_once('gitutil.git_read_tag.php');
 require_once('gitutil.git_read_commit.php');

 function git_read_ref($projectroot, $project, $ref_id, $ref_file)
 {
	$type = git_get_type($projectroot . $project, $ref_id);

	if (!$type)
		return null;

	$ref_item = array();
	$ref_item['type'] = $type;
	$ref_item['id'] = $ref_id;
	$ref_item['epoch'] = 0;
	$ref_item['age_string'] = "unknown";

	if ($type == "tag") {
		$tag = git_read_tag($projectroot . $project, $ref_id);
		$ref_item['comment'] = $tag['comment'];
		if ($tag['type'] == "commit") {
			$co = git_read_commit($projectroot . $project, $tag['object']);
			$ref_item['epoch'] = $co['committer_epoch'];
			$ref_item['age_string'] = $co['age_string'];
			$ref_item['age'] = $co['age'];
		} else if (isset($tag['epoch'])) {
			$age = time() - $tag['epoch'];
			$ref_item['epoch'] = $tag['epoch'];
			$ref_item['age_string'] = age_string($age);
			$ref_item['age'] = $age;
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
		$ref_item['age_string'] = $co['age_string'];
		$ref_item['age'] = $co['age'];
	}

	return $ref_item;
 }

?>
