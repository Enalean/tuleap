<?php
/*
 *  display.git_message.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - message
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

function git_message($message, $error = FALSE)
{
	global $tpl;
	$tpl->clear_all_assign();
	$tpl->assign("message",$message);
	if ($error)
		$tpl->assign("error", TRUE);
	$tpl->display("message.tpl");
}

?>
