<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/*
	redirect to proper hostname to get around certificate problem on IE 5
*/

// Defines all of the CodeX settings first (hosts, databases, etc.)
require(getenv('CODEX_LOCAL_INC')?getenv('CODEX_LOCAL_INC'):'/etc/codex/conf/local.inc');
require($GLOBALS['db_config_file']);
require_once('common/include/CookieManager.class.php');

// Detect whether this file is called by a script running in cli mode, or in normal web mode
if (array_key_exists('HTTP_HOST', $_SERVER) == true) {
    define('IS_SCRIPT', false); ;
} else {
    define('IS_SCRIPT', true); 
}

//{{{ Sanitize $_REQUEST : remove cookies
while(count($_REQUEST)) {
    array_pop($_REQUEST);
}
$g_pos = strpos(strtolower(ini_get('gpc_order')), 'g');
$p_pos = strpos(strtolower(ini_get('gpc_order')), 'p');
if ($g_pos === FALSE) {
    if ($p_pos !== FALSE) {
        $_REQUEST = $_POST;
    }
} else {
    if ($p_pos === FALSE) {
        $_REQUEST = $_GET;
    } else {
        if ($g_pos < $p_pos) {
            $first = '_GET';
            $second = '_POST';
        } else {
            $first = '_POST';
            $second = '_GET';
        }
        $_REQUEST = array_merge($$first, $$second);
    }
}

//Cast group_id as int.
foreach(array(
        'group_id', 
        'atid', 
        'forum_id',
        'pv',
    ) as $variable) {
    if (isset($_REQUEST[$variable])) {
        $$variable = $_REQUEST[$variable] = $_GET[$variable] = $_POST[$variable] = (int)$_REQUEST[$variable];
    }
}
//}}}

//{{{ define undefined variables
if (!isset($GLOBALS['feedback'])) {
    $GLOBALS['feedback'] = "";  //By default the feedbak is empty
}
$location = "";
if (!IS_SCRIPT) {
    $cookie_manager =& new CookieManager();
    $GLOBALS['session_hash'] = $cookie_manager->isCookie('session_hash') ? $cookie_manager->getCookie('session_hash') : false;
}
//}}}

// Check URL for valid hostname and valid protocol
if (!IS_SCRIPT &&
    ($HTTP_HOST != $GLOBALS['sys_default_domain'])
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

if (!IS_SCRIPT && isset($location) && $location) {
    header($location);
    exit;
}

//Load plugins
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$plugin_manager->loadPlugins();

$sys_datefmt = "Y-M-d H:i";
$sys_datefmt_short = "Y-M-d";
$feedback=''; // Initialize global var

//library to determine browser settings
if(!IS_SCRIPT) {
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
require_once('common/include/User.class.php');

//group functions like get_name, etc
require_once('Group.class.php');

//library to set up context help
require_once('help.php');

//exit_error library
require_once('exit.php');

//various html libs like button bar, themable
require_once('html.php');

//left-hand nav library, themable
require_once('menu.php');

// #### Connect to db

db_connect();

if (!$conn) {
	print "Could Not Connect to Database".db_error();
	exit;
}

if(!IS_SCRIPT) {
    //determine if they're logged in
    session_set();
}

/*

	Now figure out what language file to instantiate

*/

require('BaseLanguage.class.php');

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

//insert this page view into the database
if(!IS_SCRIPT) {
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

//Set up the vars and theme functions 
if(!IS_SCRIPT) {
    require_once('theme.php');


    // HTML layout class, may be overriden by the Theme class

    if ($GLOBALS['sys_is_theme_custom']) {
        $GLOBALS['path_to_theme'] = $GLOBALS['sys_custom_themeroot'].'/'.$GLOBALS['sys_user_theme'];
    } else {
        $GLOBALS['path_to_theme'] = $GLOBALS['sys_themeroot'].'/'.$GLOBALS['sys_user_theme'];
    }
    $name_of_theme_class = $GLOBALS['sys_user_theme'].'_Theme';
    
    if (!file_exists($GLOBALS['path_to_theme'].'/'.$name_of_theme_class.'.class.php')) {
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
    require_once($GLOBALS['path_to_theme'].'/'.$name_of_theme_class.'.class.php');
    $root_for_theme = ($GLOBALS['sys_is_theme_custom']?'/custom/':'/themes/').$GLOBALS['sys_user_theme'];
    $HTML = new $name_of_theme_class($root_for_theme);
    $GLOBALS['Response'] =& $HTML;
}

//Project extends Group and includes preference accessors
require_once('Project.class.php');

// If the CodeX Software license was declined by the site admin
// so stop all accesses to the site. Use exlicit path to avoid
// loading the license.php file in the register directory when
// invoking project/register.php
if(!IS_SCRIPT) {
    require_once($DOCUMENT_ROOT.'/include/license.php');
    if (license_already_declined()) {
        exit_error($Language->getText('global','error'),$Language->getText('include_pre','site_admin_declines_license',$GLOBALS['sys_email_admin']));
    }
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

if (!IS_SCRIPT &&
    $SERVER_NAME != 'localhost' && 
    $GLOBALS['sys_allow_anon'] == 0 && !user_isloggedin() &&
    $SCRIPT_NAME != '/current_css.php'  && 
    $SCRIPT_NAME != '/account/login.php'  && 
    $SCRIPT_NAME != '/account/register.php'&& 
    $SCRIPT_NAME != '/account/change_pw.php'&& 
    $SCRIPT_NAME != '/include/check_pw.php'&& 
    $SCRIPT_NAME != '/account/lostpw.php' &&
    $SCRIPT_NAME != '/account/lostlogin.php' &&
    $SCRIPT_NAME != '/account/lostpw-confirm.php' &&
    $SCRIPT_NAME != '/account/pending-resend.php' &&
    $SCRIPT_NAME != '/account/verify.php' &&
    $SCRIPT_NAME != '/scripts/check_pw.js.php' &&
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

if (!IS_SCRIPT &&
    user_isrestricted()) {
    if (!util_check_restricted_access($REQUEST_URI,$SCRIPT_NAME)) {
        exit_restricted_user_permission_denied();
    }
}
require_once('common/include/HTTPRequest.class.php');
require_once('common/include/URL.class.php');
$request =& HTTPRequest::instance();
//Do nothing if we are not in a distributed architecture
if (!IS_SCRIPT &&
    isset($GLOBALS['sys_server_id']) && $GLOBALS['sys_server_id']) {
    require_once('Project.class.php');
    $redirect_to_master_if_needed = true;
    $sf      =& new ServerFactory();
    if ($_SERVER['SCRIPT_NAME'] == '/file/download.php') { //There is no group_id for /file/download.php
        $components = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($components[3])) {
            $p =& project_get_object($components[3]);
        }
    } else if ($_SERVER['SCRIPT_NAME'] == '/svn/viewvc.php' && $request->get('roottype') == 'svn' && $request->exist('root')) { //There is no group_id for viewvc
        $res_grp=db_query("SELECT * FROM groups WHERE unix_group_name='". $request->get('root') ."'");
        if (db_numrows($res_grp) < 1) {
            //group was not found
            echo db_error();
            exit_error("Invalid Group","That group does not exist.");
        } else {
            $p =& project_get_object(db_result($res_grp,0,'group_id'));
        }
    } else if ($request->exist('group_id')) {
        $p =& project_get_object($request->get('group_id'));
    }
    if (isset($p)) {
        //get service from url
        $url = explode('/', $_SERVER['SCRIPT_NAME']);
        if (isset($url[1])) {
            $service_name = $url[1];
            if ($service_name == 'plugins' && isset($url[2])) {
                $service_name = $url[2];
            }
            if ($p->usesService($service_name)) {
                $redirect_to_master_if_needed = false;
                //If we request a page wich IS NOT distributed...
                if (!$p->services[$service_name]->isRequestedPageDistributed($request)) {
                    //...and we are not on the master...
                    if ($master =& $sf->getMasterServer() && $master->getId() != $GLOBALS['sys_server_id']) {
                        //...then go to the master.
                        $goto = $master->getUrl(session_issecure()) . $_SERVER['REQUEST_URI'];
                        if ($_SERVER['HTTP_HOST'] == URL::getHost($goto)) {
                            $GLOBALS['Response']->addFeedback('error', 'Error with configuration of distributed architecture. Wrong sys_server_id? Please contact your administrator.');
                        }
                        $GLOBALS['Response']->redirect($goto);
                    }
                } else { //If we request a page wich is distributed...
                    //...and we are not on the good server...
                    if ($p->services[$service_name]->getServerId() != $GLOBALS['sys_server_id']) {
                        if ($s =& $sf->getServerById($p->services[$service_name]->getServerId())) {
                            //...then go to the server
                            $goto = $s->getUrl(session_issecure()) . $_SERVER['REQUEST_URI'];
                            if ($_SERVER['HTTP_HOST'] == URL::getHost($goto)) {
                                $GLOBALS['Response']->addFeedback('error', 'Error with configuration of distributed architecture. Wrong sys_server_id? Please contact your administrator.');
                            }
                            $GLOBALS['Response']->redirect($goto);
                        }
                    }
                }
            }
        }
    }
    if ($redirect_to_master_if_needed) {
        if (!in_array($_SERVER['SCRIPT_NAME'], array(
            '/api/reference/extract',
            '/current_css.php',
            '/account/login.php',
            )
        )) {
            if ($master =& $sf->getMasterServer() && $master->getId() != $GLOBALS['sys_server_id']) {
                $goto = $master->getUrl(session_issecure()) . $_SERVER['REQUEST_URI'];
                if ($_SERVER['HTTP_HOST'] == URL::getHost($goto)) {
                    $GLOBALS['Response']->addFeedback('error', 'Error with configuration of distributed architecture. Wrong sys_server_id? Please contact your administrator.');
                }
                $GLOBALS['Response']->redirect($goto);
            }
        }
    }
}

//Check post max size
if ($request->exist('postExpected') && !$request->exist('postReceived')) {
    $e = 'You tried to upload a file that is larger than the CodeX post_max_size setting.';
    exit_error('Error', $e);
}
?>
