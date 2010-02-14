<?php
/*
 *  display.git_rss.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - RSS feed
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('defs.constants.php');
 require_once('util.date_str.php');
 require_once('util.script_url.php');
 require_once('gitutil.git_read_revlist.php');
 require_once('gitutil.git_diff_tree.php');

function git_rss()
{
	global $tpl, $gitphp_current_project;

	if (!$gitphp_current_project)
		return;

	header("Content-type: text/xml; charset=UTF-8");

	$cachekey = sha1($gitphp_current_project->GetProject());

	if (!$tpl->is_cached('rss.tpl', $cachekey)) {
		$head = $gitphp_current_project->GetHeadCommit();;
		$revlist = git_read_revlist($head->GetHash(), GITPHP_RSS_ITEMS);
		$tpl->assign("self",script_url());

		$commitlines = array();
		$revlistcount = count($revlist);
		for ($i = 0; $i < $revlistcount; ++$i) {
			$commit = $revlist[$i];
			$co = $gitphp_current_project->GetCommit($commit);
			if (($i >= 20) && ((time() - $co->GetCommitterEpoch()) > 48*60*60))
				break;
			$cd = date_str($co->GetCommitterEpoch());
			$commitline = array();
			$commitline["cdmday"] = $cd['mday'];
			$commitline["cdmonth"] = $cd['month'];
			$commitline["cdhour"] = $cd['hour'];
			$commitline["cdminute"] = $cd['minute'];
			$commitline["title"] = $co->GetTitle();
			$commitline["author"] = $co->GetAuthor();
			$commitline["cdrfc2822"] = $cd['rfc2822'];
			$commitline["commit"] = $commit;
			$commitline["comment"] = $co->GetComment();

			$parent = $co->GetParent();
			if ($parent) {
				$difftree = array();
				$diffout = git_diff_tree($parent->GetHash() . " " . $co->GetHash());
				$tok = strtok($diffout,"\n");
				while ($tok !== false) {
					if (preg_match("/^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$/",$tok,$regs))
						$difftree[] = $regs[7];
					$tok = strtok("\n");
				}
				$commitline["difftree"] = $difftree;
			}

			$commitlines[] = $commitline;
			unset($co);
		}
		$tpl->assign("commitlines",$commitlines);
	}
	$tpl->display('rss.tpl', $cachekey);
}

?>
