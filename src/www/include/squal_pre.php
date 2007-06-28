<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require(getenv('CODEX_LOCAL_INC')?getenv('CODEX_LOCAL_INC'):'/etc/codex/conf/local.inc');
require($GLOBALS['db_config_file']);
require_once('browser.php');
require_once('database.php');
require_once('session.php');
require_once('user.php');
require_once('utils.php');
require_once('theme.php');
require_once('BaseLanguage.class.php');
if (!$GLOBALS['sys_lang']) {
	$GLOBALS['sys_lang']="en_US";
}
if (user_isloggedin()) {
    $Language = new BaseLanguage();
    $Language->loadLanguageID(user_get_language());
} else {
    //if you aren't logged in, check your browser settings 
    //and see if we support that language
    //if we don't support it, just use system default
    if (isset($HTTP_ACCEPT_LANGUAGE)) {
	$res = language_code_to_result ($HTTP_ACCEPT_LANGUAGE);
	$lang_code=db_result($res,0,'language_code');
    }
    if (!isset($lang_code)) { $lang_code = $GLOBALS['sys_lang']; }
    $Language = new BaseLanguage();
    $Language->loadLanguage($lang_code);
}

setlocale (LC_TIME, $Language->getText('system','locale'));
$sys_strftimefmt = $Language->getText('system','strftimefmt');
$sys_datefmt = $Language->getText('system','datefmt');

$Language->loadLanguageMsg('include/include');


require_once('squal_exit.php');

$sys_datefmt = "m/d/y H:i";

// #### Connect to db

db_connect();

if (!$conn) {
	exit_error($Language->getText('include_squal_pre','not_connect_db'),db_error());
}

//require_once('logger.php');

// #### set session

session_set();

?>
