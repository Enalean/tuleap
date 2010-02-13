<?php
/*
 *  display.git_opml.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - OPML feed
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.script_url.php');
 require_once('gitutil.git_read_projects.php');

function git_opml()
{
	global $tpl,$gitphp_appstring;

	$cachekey = sha1(serialize(GitPHP_ProjectList::GetInstance()->GetConfig()));

	if (!$tpl->is_cached('opml.tpl', $cachekey)) {
		header("Content-type: text/xml; charset=UTF-8");
		$projlist = git_read_projects();
		$tpl->assign("title", GitPHP_Config::GetInstance()->GetValue('title', $gitphp_appstring));
		$tpl->assign("self",script_url());
		$opmllist = array();
		foreach ($projlist as $cat => $plist) {
			if (is_array($plist)) {
				foreach ($plist as $i => $proj) {
					$opmllist[] = $proj;
				}
			} else {
				$opmllist[] = $plist;
			}
		}
		$tpl->assign("opmllist",$opmllist);
	}
	$tpl->display('opml.tpl', $cachekey);
}

?>
