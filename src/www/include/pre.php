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
require(getenv('CODEX_LOCAL_INC')?getenv('CODEX_LOCAL_INC'):'/etc/codex/conf/local.inc');
require($GLOBALS['db_config_file']);

// Detect whether this file is called by a script running in cli mode, or in normal web mode
if (array_key_exists('HTTP_HOST', $_SERVER) == true) {
    define('IS_SCRIPT', false); ;
} else {
    define('IS_SCRIPT', true); 
}

//{{{ define undefined variables
if (!isset($GLOBALS['feedback'])) {
    $GLOBALS['feedback'] = "";  //By default the feedbak is empty
}
$location = "";
//}}}

if (! IS_SCRIPT) {
    // Check URL for valid hostname and valid protocol
    if (($HTTP_HOST != $GLOBALS['sys_default_domain'])
        && ($SERVER_NAME != 'localhost')
        && (strcmp(substr($SCRIPT_NAME,0,5),'/api/') !=0)
        && (strcmp(substr($SCRIPT_NAME,0,6),'/soap/') !=0)
        && (!isset($GLOBALS['sys_https_host'])||($HTTP_HOST != $GLOBALS['sys_https_host']))) {
        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || $GLOBALS['sys_force_ssl'] == 1) {
	    $location = "Location: https://".$GLOBALS['sys_https_host']."$REQUEST_URI";
        } else {
 	    $location = "Location: http://".$GLOBALS['sys_default_domain']."$REQUEST_URI";
        }
    }

// Force SSL mode if required except if request comes from localhost, or for api scripts
// HTTP needed by fopen calls (e.g.  in www/include/cache.php)

    if ((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') && $GLOBALS['sys_force_ssl'] == 1 && ($SERVER_NAME != 'localhost') && (strcmp(substr($SCRIPT_NAME,0,5),'/api/') !=0)) {
        $location = "Location: https://".$GLOBALS['sys_https_host']."$REQUEST_URI";
    }

    if (isset($location) && $location) {
        header($location);
        exit;
    }
}

//Load plugins
require_once('common/plugin/PluginManager.class');
$plugin_manager =& PluginManager::instance();
$plugin_manager->loadPlugins();

$sys_datefmt = "Y-M-d H:i";
$sys_datefmt_short = "Y-M-d";
$feedback=''; // Initialize global var

if (! IS_SCRIPT) {
    //library to determine browser settings
    require_once('browser.php');
}

//various html utilities
require_once('utils.php');

//PHP4-like functions - only if running php3
if (substr(phpversion(),0,1) == "3") {
    require_once('utils_php4.php');
}

//database abstraction
require_once('database.php');

//security library
require_once('session.php');

//user functions like get_name, logged_in, etc
require_once('user.php');
require_once('common/include/User.class');

//group functions like get_name, etc
require_once('Group.class');

//Project extends Group and includes preference accessors
require_once('Project.class');

//library to set up context help
require_once('help.php');

//exit_error library
require_once('exit.php');

if (! IS_SCRIPT) {
    //various html libs like button bar, themable
    require_once('html.php');
}

//left-hand nav library, themable
require_once('menu.php');

// #### Connect to db

db_connect();

if (!$conn) {
	print "Could Not Connect to Database".db_error();
	exit;
}

if (! IS_SCRIPT) {
    //determine if they're logged in
    session_set();
}    

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
$sys_datefmt_short = $Language->getText('system','datefmt_short');

$Language->loadLanguageMsg('include/include');

if (! IS_SCRIPT) {
    //insert this page view into the database
    require_once('logger.php');
}

/*

	Timezone must come after logger to prevent messups


*/
//set up the user's timezone if they are logged in
if (user_isloggedin()) {
	putenv('TZ='.user_get_timezone());
} else {
	//just use pacific time as always
}

if (! IS_SCRIPT) {
    //Set up the vars and theme functions 
    require_once('theme.php');


// HTML layout class, may be overriden by the Theme class

    if ($GLOBALS['sys_is_theme_custom']) {
        $GLOBALS['path_to_theme'] = $GLOBALS['sys_custom_themeroot'].'/'.$GLOBALS['sys_user_theme'];
    } else {
        $GLOBALS['path_to_theme'] = $GLOBALS['sys_themeroot'].'/'.$GLOBALS['sys_user_theme'];
    }
    $name_of_theme_class = $GLOBALS['sys_user_theme'].'_Theme';

    if (!file_exists($GLOBALS['path_to_theme'].'/'.$name_of_theme_class.'.class')) {
        //User wants a theme which doesn't exist
        //We're looking for default theme
        $GLOBALS['sys_user_theme'] = $GLOBALS['sys_themedefault'];
        $name_of_theme_class       = $GLOBALS['sys_user_theme'].'_Theme';
	if (is_dir($GLOBALS['sys_themeroot'].'/'.$GLOBALS['sys_user_theme'])) {
            $GLOBALS['sys_is_theme_custom'] = false;
            $GLOBALS['path_to_theme']       = $GLOBALS['sys_themeroot'].'/'.$GLOBALS['sys_user_theme'];
        } else {
            $GLOBALS['sys_is_theme_custom'] = true;
            $GLOBALS['path_to_theme']       = $GLOBALS['sys_custom_themeroot'].'/'.$GLOBALS['sys_user_theme'];
        }
    }
    require_once($GLOBALS['path_to_theme'].'/'.$name_of_theme_class.'.class');
    $root_for_theme = ($GLOBALS['sys_is_theme_custom']?'/custom/':'/themes/').$GLOBALS['sys_user_theme'];
    $HTML = new $name_of_theme_class($root_for_theme);

// If the CodeX Software license was declined by the site admin
// so stop all accesses to the site. Use exlicit path to avoid
// loading the license.php file in the register directory when
// invoking register/index.php
    require_once($DOCUMENT_ROOT.'/include/license.php');
    if (license_already_declined()) {
        exit_error($Language->getText('global','error'),$Language->getText('include_pre','site_admin_declines_license',$GLOBALS['sys_email_admin']));
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
        $SCRIPT_NAME != '/current_css.php'  && 
        $SCRIPT_NAME != '/account/login.php'  && 
        $SCRIPT_NAME != '/account/register.php'&& 
        $SCRIPT_NAME != '/account/lostpw.php' &&
        $SCRIPT_NAME != '/account/lostlogin.php' &&
        $SCRIPT_NAME != '/account/lostpw-confirm.php' &&
        $SCRIPT_NAME != '/account/pending-resend.php' &&
        $SCRIPT_NAME != '/account/verify.php' &&
        strcmp(substr($SCRIPT_NAME,0,6),'/soap/') !=0 &&
        strcmp(substr($SCRIPT_NAME,0,5),'/api/') !=0 ) {
    
        $return_to = urlencode((($REQUEST_URI === "/")?"/my/":$REQUEST_URI));

        if ($GLOBALS['sys_force_ssl'] == 1 || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
            header("Location: https://".$GLOBALS['sys_https_host']."/account/login.php?return_to=".$return_to);
        } else {
            header("Location: http://".$GLOBALS['sys_default_domain']."/account/login.php?return_to=".$return_to);
        }
        exit;
    }
}

if (user_isrestricted()) {
    if (!util_check_restricted_access($REQUEST_URI,$SCRIPT_NAME)) {
        exit_restricted_user_permission_denied();
    }
}
?>
