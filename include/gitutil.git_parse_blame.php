<?php
/*
 * gitutil.git_parse_blame.php
 * gitphp: A PHP git repository browser
 * Component: Git utility - parse blame info
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_read_blame.php');
require_once('util.date_str.php');

function git_parse_blame($file, $rev = null)
{
	$lines = explode("\n", git_read_blame($file, $rev));

	if (count($lines) < 1)
		return null;
	
	$blamedata = array();
	$commitcache = array();
	
	$commitgroup = null;
	foreach ($lines as $i => $line) {
		/*
		 * Only parsing a handful of the blame info, see
		 * the git blame man page for all the data
		 */
		if (preg_match("/^([0-9a-fA-F]{40}) ([0-9]+) ([0-9]+) ([0-9]+)$/",$line,$regs)) {
			/* starting a new commit group */
			if ($commitgroup)
				$blamedata[] = $commitgroup;
			$commitgroup = array();
			$commitgroup['lines'] = array();
			$commitgroup['commit'] = $regs[1];
			if (isset($commitcache[$regs[1]])) {
				$commitgroup['commitdata'] = $commitcache[$regs[1]];
			} else {
				$commitgroup['commitdata'] = array();
				$commitcache[$regs[1]] = array();
			}
		} else if (preg_match("/^author (.*)$/",$line,$regs)) {
			$commitgroup['commitdata']['author'] = $regs[1];
			$commitcache[$commitgroup['commit']]['author'] = $regs[1];
		} else if (preg_match("/^author-mail (.*)$/",$line,$regs)) {
			$commitgroup['commitdata']['author-mail'] = $regs[1];
			$commitcache[$commitgroup['commit']]['author-mail'] = $regs[1];
		} else if (preg_match("/^author-time (.*)$/",$line,$regs)) {
			$commitgroup['commitdata']['author-time'] = $regs[1];
			$commitcache[$commitgroup['commit']]['author-time'] = $regs[1];
		} else if (preg_match("/^author-tz (.*)$/",$line,$regs)) {
			$commitgroup['commitdata']['author-tz'] = $regs[1];
			$commitcache[$commitgroup['commit']]['author-tz'] = $regs[1];
		} else if (preg_match("/^summary (.*)$/",$line,$regs)) {
			$commitgroup['commitdata']['summary'] = $regs[1];
			$commitcache[$commitgroup['commit']]['summary'] = $regs[1];
		} else if (preg_match("/^\t(.*)$/",$line,$regs)) {
			/* tab starts a file content line */
			$commitgroup['lines'][] = $regs[1];
		}
	}
	if ($commitgroup)
		$blamedata[] = $commitgroup;

	$len = count($blamedata);
	for ($i = 0; $i < $len; $i++) {
		if (isset($blamedata[$i]['commitdata']['author-time'])) {
			$authortime = $blamedata[$i]['commitdata']['author-time'];
			$authortz = "-0000";
			if (isset($blamedata[$i]['commitdata']['author-tz']))
				$authortz = $blamedata[$i]['commitdata']['author-tz'];
			$date = date_str($authortime, $authortz);
			$blamedata[$i]['commitdata']['authordate'] = $date['ymd-time'];
		}
		if (isset($blamedata[$i]['commitdata']['committer-time'])) {
			$committertime = $blamedata[$i]['commitdata']['committer-time'];
			$committertz = "-0000";
			if (isset($blamedata[$i]['commitdata']['committer-tz']))
				$committertz = $blamedata[$i]['commitdata']['committer-tz'];
			$date = date_str($committertime, $committertz);
			$blamedata[$i]['commitdata']['committerdate'] = $date['ymd-time'];
		}
	}

	return $blamedata;
}

?>
