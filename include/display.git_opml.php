<?php
/*
 *  display.git_opml.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - OPML feed
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.script_url.php');

function git_opml()
{
	global $tpl,$gitphp_appstring;

	$cachekey = sha1(serialize(GitPHP_ProjectList::GetInstance()->GetConfig()));

	if (!$tpl->is_cached('opml.tpl', $cachekey)) {
		header("Content-type: text/xml; charset=UTF-8");
		$tpl->assign("title", GitPHP_Config::GetInstance()->GetValue('title', $gitphp_appstring));
		$tpl->assign("self",script_url());
		$tpl->assign("opmllist", GitPHP_ProjectList::GetInstance());
	}
	$tpl->display('opml.tpl', $cachekey);
}

?>
