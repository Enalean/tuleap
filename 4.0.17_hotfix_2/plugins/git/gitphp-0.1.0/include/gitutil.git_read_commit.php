<?php
/*
 *  gitutil.git_read_commit.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - read a commit
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.constants.php');
 require_once('util.age_string.php');
 require_once('gitutil.git_read_revlist.php');

function git_read_commit($proj,$head)
{
        //Purify html output
        $purifier = Codendi_HTMLPurifier::instance();        
	$lines = git_read_revlist($proj,$head,1,NULL,TRUE,TRUE);
	if (!($lines[0]) || !preg_match("/^[0-9a-fA-F]{40}/",$lines[0]))
		return null;
	$commit = array();
	$tok = strtok($lines[0]," ");
	$commit['id'] = $tok;
	$tok = strtok(" ");
	$parents = array();
	while ($tok !== false) {
		$parents[] = $tok;
		$tok = strtok(" ");
	}
	$commit['parents'] = $parents;
	if (isset($parents[0]))
		$commit['parent'] = $parents[0];
	$comment = array();        
	foreach ($lines as $i => $line) {                
		if (preg_match("/^tree ([0-9a-fA-F]{40})$/",$line,$regs))
			$commit['tree'] = $regs[1];
		else if (preg_match("/^author (.*) ([0-9]+) (.*)$/",$line,$regs)) {
			$commit['author'] = $purifier->purify($regs[1], CODENDI_PURIFIER_BASIC_NOBR, $_REQUEST['group_id']);;
                        $commit['author_epoch'] = $regs[2];
			$commit['author_tz'] = $regs[3];
			if (preg_match("/^([^<]+) </",$commit['author'],$r))
				$commit['author_name'] = $r[1];
			else
				$commit['author_name'] = $commit['author'];
		} else if (preg_match("/^committer (.*) ([0-9]+) (.*)$/",$line,$regs)) {
			$commit['committer'] = $purifier->purify($regs[1], CODENDI_PURIFIER_BASIC_NOBR, $_REQUEST['group_id']);
			$commit['committer_epoch'] = $regs[2];
			$commit['committer_tz'] = $regs[3];
			$commit['committer_name'] = $commit['committer'];
			$commit['committer_name'] = preg_replace("/ <.*/","",$commit['committer_name']);
		} else {
			$trimmed = trim($line);
			if ((strlen($trimmed) > 0) && !preg_match("/^[0-9a-fA-F]{40}/",$trimmed) && !preg_match("/^parent [0-9a-fA-F]{40}/",$trimmed)) {
				if (!isset($commit['title'])) {                                        
					$commit['title'] = $trimmed;
					if (strlen($trimmed) > GITPHP_TRIM_LENGTH)
						$commit['title_short'] = substr($trimmed,0,GITPHP_TRIM_LENGTH) . "...";
					else
						$commit['title_short'] = $trimmed;
				}
                                $trimmed = $purifier->purify($trimmed, CODENDI_PURIFIER_BASIC_NOBR, $_REQUEST['group_id']);
				$comment[] = $trimmed;
			}
		}
	}        
	$commit['comment'] = $comment;
	$age = time() - $commit['committer_epoch'];
	$commit['age'] = $age;
	$commit['age_string'] = age_string($age);
	date_default_timezone_set("UTC");
	if ($age > 60*60*24*7*2) {
		$commit['age_string_date'] = date("Y-m-d",$commit['committer_epoch']);
		$commit['age_string_age'] = $commit['age_string'];
	} else {
		$commit['age_string_date'] = $commit['age_string'];
		$commit['age_string_age'] = date("Y-m-d",$commit['committer_epoch']);
        }        
	return $commit;
}

?>
