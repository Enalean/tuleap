<?php
/*
 *  gitutil.git_read_tag.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read tag
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_cat_file.php');

function git_read_tag($project, $tag_id)
{
	$tag = array();
	$tagout = git_cat_file($project, $tag_id, NULL, "tag");
	$tag['id'] = $tag_id;
	$comment = array();
	$tok = strtok($tagout,"\n");
	while ($tok !== false) {
		if (preg_match("/^object ([0-9a-fA-F]{40})$/",$tok,$regs))
			$tag['object'] = $regs[1];
		else if (preg_match("/^type (.+)$/",$tok,$regs))
			$tag['type'] = $regs[1];
		else if (preg_match("/^tag (.+)$/",$tok,$regs))
			$tag['name'] = $regs[1];
		else if (preg_match("/^tagger (.*) ([0-9]+) (.*)$/",$tok,$regs)) {
			$tag['author'] = $regs[1];
			$tag['epoch'] = $regs[2];
			$tag['tz'] = $regs[3];
		} else {
			while ($tok !== false) {
				$comment[] = $tok;
				$tok = strtok("\n");
			}
			break;
		}
		$tok = strtok("\n");
	}
	$tag['comment'] = $comment;
	if (!isset($tag['name']))
		return null;
	return $tag;
}

?>
