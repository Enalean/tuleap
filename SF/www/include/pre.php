<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*
	redirect to proper hostname to get around certificate problem on IE 5
*/

// Defines all of the CodeX settings first (hosts, databases, etc.)
require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');

// Check URL for valid hostname and valid protocol
if (($HTTP_HOST != $GLOBALS['sys_default_domain']) && ($SERVER_NAME != 'localhost') && ($HTTP_HOST != $GLOBALS['sys_https_host'])) {
    if ($HTTPS == 'on'|| $GLOBALS['sys_force_ssl'] == 1) {
	$location = "Location: https://".$GLOBALS['sys_https_host']."$REQUEST_URI";
    } else {
	$location = "Location: http://".$GLOBALS['sys_default_domain']."$REQUEST_URI";
    }
}

// Force SSL mode if required except if request comes from localhost
// HTTP needed by fopen calls (e.g.  in www/include/cache.php)
if ($HTTPS != 'on' && $GLOBALS['sys_force_ssl'] == 1 && ($SERVER_NAME != 'localhost')) {
    $location = "Location: https://".$GLOBALS['sys_https_host']."$REQUEST_URI";
}

if ($location) {
    header($location);
    exit;
}   

$sys_datefmt = "Y-M-d H:i";

//library to determine browser settings
require($DOCUMENT_ROOT.'/include/browser.php');

//various html utilities
require($DOCUMENT_ROOT.'/include/utils.php');

include(util_get_content('layout/osdn_sites'));

// HTML layout class, may be overriden by the Theme class
require($DOCUMENT_ROOT.'/include/Layout.class');

$HTML = new Layout();

//PHP4-like functions - only if running php3
if (substr(phpversion(),0,1) == "3") {
    require($DOCUMENT_ROOT.'/include/utils_php4.php');
}

//database abstraction
require($DOCUMENT_ROOT.'/include/database.php');

//security library
require($DOCUMENT_ROOT.'/include/session.php');

//user functions like get_name, logged_in, etc
require($DOCUMENT_ROOT.'/include/user.php');

//group functions like get_name, etc
require($DOCUMENT_ROOT.'/include/Group.class');

//Project extends Group and includes preference accessors
require($DOCUMENT_ROOT.'/include/Project.class');

//library to set up context help
require($DOCUMENT_ROOT.'/include/help.php');

//exit_error library
require($DOCUMENT_ROOT.'/include/exit.php');

//various html libs like button bar, themable
require($DOCUMENT_ROOT.'/include/html.php');

//left-hand nav library, themable
require($DOCUMENT_ROOT.'/include/menu.php');

// #### Connect to db

db_connect();

if (!$conn) {
	print "Could Not Connect to Database".db_error();
	exit;
}

//determine if they're logged in
session_set();

// OSDN functions and defs
require($DOCUMENT_ROOT.'/include/osdn.php');

//insert this page view into the database
require($DOCUMENT_ROOT.'/include/logger.php');

/*

	Timezone must come after logger to prevent messups


*/
//set up the user's timezone if they are logged in
if (user_isloggedin()) {
	putenv('TZ='.user_get_timezone());
} else {
	//just use pacific time as always
}

//Set up the vars and theme functions 
require($DOCUMENT_ROOT.'/include/theme.php');


/*

	Now figure out what language file to instantiate

*/

require('BaseLanguage.class');

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
    if ($HTTP_ACCEPT_LANGUAGE) {
	$res = language_code_to_result ($HTTP_ACCEPT_LANGUAGE);
	$lang_code=db_result($res,0,'language_code');
    }
    if (!$lang_code) { $lang_code = $GLOBALS['sys_lang']; }
    $Language = new BaseLanguage();
    $Language->loadLanguage($lang_code);
}

setlocale (LC_TIME, $Language->getText('system','locale'));
$sys_strftimefmt = $Language->getText('system','strftimefmt');
$sys_datefmt = $Language->getText('system','datefmt');

$Language->loadLanguageMsg('include/include');

// If the CodeX Software license was declined by the site admin
// so stop all accesses to the site
require($DOCUMENT_ROOT.'/include/license.php');
if (license_already_declined()) {
   exit_error('ERROR','Your site administrator declined the CodeX Software License. 
The CodeX site has been shut down. For more information contact your  
<a href="mailto:'.$GLOBALS['sys_email_admin'].'">Site Administrator</a>.');
}

// Check if anonymous user is allowed to browse the site
// Bypass the test for:
// a) all scripts where you are not logged in by definition
// b) if it is a local access from localhost 

/*
print "<p>DBG: SERVER_NAME = ".$SERVER_NAME;
print "<p>DBG: sys_allow_anon= ".$GLOBALS['sys_allow_anon'];
print "<p>DBG: user_isloggedin= ".user_isloggedin();
print "<p>DBG: SCRIPT_NAME = ".$SCRIPT_NAME";
*/

if ($SERVER_NAME != 'localhost' && 
    $GLOBALS['sys_allow_anon'] == 0 && !user_isloggedin() &&
    $SCRIPT_NAME != '/account/login.php'  && 
    $SCRIPT_NAME != '/account/register.php'&& 
    $SCRIPT_NAME != '/account/lostpw.php' &&
    $SCRIPT_NAME != '/account/lostlogin.php' &&
    $SCRIPT_NAME != '/account/lostpw-confirm.php' &&
    $SCRIPT_NAME != '/account/pending-resend.php' &&
    $SCRIPT_NAME != '/account/verify.php' ) {
    if ($GLOBALS['sys_force_ssl'] == 1 || $HTTPS == 'on')
	header("Location: https://".$GLOBALS['sys_https_host']."/account/login.php");
    else
	header("Location: http://".$GLOBALS['sys_default_domain']."/account/login.php");
    exit;
}

if (user_isrestricted()) {
    if (!util_check_restricted_access($REQUEST_URI,$SCRIPT_NAME)) {
        exit_permission_denied();
    }
}

?>
