<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    

session_require(array('isloggedin'=>1));

db_query("UPDATE user SET "
	. "mail_siteupdates=" . ($form_mail_site?"1":"0") . ","
	. "mail_va=" . ($form_mail_va?"1":"0") . ","
	. "fontsize=" . $user_fontsize . ","
	. "theme='" . $user_theme . "',"
	. "sticky_login=" . ($form_sticky_login?"1":"0") . " WHERE "
	. "user_id=" . user_getid());

setcookie("SF_THEME", sprintf("%06d%s",user_getid(),$user_theme), time() + 60*60*24*365, "/");
setcookie("SF_FONTSIZE", sprintf("%06d%d",user_getid(),$user_fontsize), time() + 60*60*24*365, "/");

session_redirect("/account/");

?>
