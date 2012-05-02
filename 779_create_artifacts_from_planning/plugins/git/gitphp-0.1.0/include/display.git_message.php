<?php
/*
 *  display.git_message.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - message
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

function git_message($message, $error = FALSE, $standalone = TRUE)
{
	global $tpl;

	$cachekey = sha1($message) . "|" . ($error ? "1" : "0") . "|" . ($standalone ? "1" : "0");

	if (!$tpl->is_cached('message.tpl', $cachekey)) {
		$tpl->assign("message",$message);
		if ($error)
			$tpl->assign("error", TRUE);
		if ($standalone)
			$tpl->assign("standalone", TRUE);
	}
	$tpl->display('message.tpl', $cachekey);
}

?>
