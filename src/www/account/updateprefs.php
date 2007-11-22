<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/include/CookieManager.class.php');
$cookie_manager =& new CookieManager();

//
// Validate params
//

session_require(array('isloggedin'=>1));

$request =& HTTPRequest::instance();

$form_mail_site = 0;
if($request->existAndNonEmpty('form_mail_site')) {
    $form_mail_site = (int) $request->get('form_mail_site');
}

$form_mail_va = 0;
if($request->existAndNonEmpty('form_mail_va')) {
    $form_mail_va = (int) $request->get('form_mail_va');
}

$user_fontsize = 2;
if($request->existAndNonEmpty('user_fontsize')) {
    $user_fontsize = (int) $request->get('user_fontsize');
}

$user_theme = $GLOBALS['sys_themedefault'];
if($request->existAndNonEmpty('user_theme')) {
    $user_theme = $request->get('user_theme');
}

$form_sticky_login = 0;
if($request->existAndNonEmpty('form_sticky_login')) {
    $form_sticky_login = (int) $request->get('form_sticky_login');
}

$language_id = 1;
if($request->existAndNonEmpty('language_id')) {
    $language_id= (int) $request->get('language_id');
}

// we check if the given value is authorized
// $csv_separators is defined in src/www/include/utils.php
$user_csv_separator = DEFAULT_CSV_SEPARATOR;
if($request->existAndNonEmpty('user_csv_separator') &&
   in_array($request->get('user_csv_separator'), $csv_separators)) {
    $user_csv_separator = $request->get('user_csv_separator');
}

$username_display = null;
if ($request->existAndNonEmpty('username_display')) {
    $username_display = $request->get('username_display');
}

//
// Perform the update
//

// User
db_query("UPDATE user SET "
         . "mail_siteupdates=" . $form_mail_site . ","
         . "mail_va=" . $form_mail_va . ","
         . "fontsize=" . $user_fontsize . ","
         . "theme='" . db_es($user_theme) . "',"
         . "sticky_login=" . $form_sticky_login . ","
         . "language_id=" . $language_id . " WHERE "
         . "user_id=" . user_getid());

// Preferences
user_set_preference("user_csv_separator", $user_csv_separator);
if($username_display !== null) {
    user_set_preference("username_display", $username_display);
}

//
// Output
//

session_redirect("/account/preferences.php");

?>
