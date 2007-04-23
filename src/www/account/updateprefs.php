<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: updateprefs.php 5240 2007-03-12 15:14:31 +0000 (Mon, 12 Mar 2007) nterray $

require_once('pre.php');    
require_once('common/include/CookieManager.class.php');
$cookie_manager =& new CookieManager();

session_require(array('isloggedin'=>1));

db_query("UPDATE user SET "
	. "mail_siteupdates=" . (isset($form_mail_site) && $form_mail_site?"1":"0") . ","
         . "mail_va=" . ((isset($form_mail_va) && $form_mail_va)?"1":"0") . ","
	. "fontsize=" . $user_fontsize . ","
	. "theme='" . $user_theme . "',"
         . "sticky_login=" . ((isset($form_sticky_login) && $form_sticky_login)?"1":"0") . ","
	. "language_id=" . $language_id . " WHERE "
	. "user_id=" . user_getid());

$cookie_manager->setCookie('THEME', sprintf("%06d%s",user_getid(),$user_theme), time() + 60*60*24*365);
$cookie_manager->setCookie('FONTSIZE', sprintf("%06d%s",user_getid(),$user_fontsize), time() + 60*60*24*365);

// we check if the given value is authorized
// $csv_separators is defined in src/www/include/utils.php
if (in_array($user_csv_separator, $csv_separators)) {
    user_set_preference("user_csv_separator", $user_csv_separator);
} else {
    // if not, we assign the default value
    user_set_preference("user_csv_separator", DEFAULT_CSV_SEPARATOR);
}

session_redirect("/account/preferences.php");

?>
