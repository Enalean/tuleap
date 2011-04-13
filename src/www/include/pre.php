<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

if (version_compare(phpversion(), '5.1.6', '<')) {
    die('Codendi must be run on a PHP 5.1.6 (or greater) engine');
}

// Defines all of the Codendi settings first (hosts, databases, etc.)
require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);

define('TTF_DIR',isset($GLOBALS['ttf_font_dir']) ? $GLOBALS['ttf_font_dir'] : '/usr/share/fonts/');

require_once('common/include/CookieManager.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/include/SimpleSanitizer.class.php');
require_once('common/include/URL.class.php');

/**
 * Method called when a class is not defined.
 *
 * Used to load Zend classes on the fly
 *
 * @param String $className
 *
 * @return void
 */
function __autoload($className) {
    global $Language;
    if (strpos($className, 'Zend') === 0 && !class_exists($className)) {
        if (isset($GLOBALS['zend_path'])) {
            ini_set('include_path', $GLOBALS['zend_path'].':'.ini_get('include_path'));
            $path = str_replace('_', '/', $className);
            require_once $path.'.php';
        } else {
            exit_error($Language->getText('global','error'),$Language->getText('include_pre','zend_path_not_set',$GLOBALS['sys_email_admin']));
        }
    }
}

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
if (!ini_get('variables_order')) {
        $_REQUEST = array_merge($_GET, $_POST);
} else {
    $g_pos = strpos(strtolower(ini_get('variables_order')), 'g');
    $p_pos = strpos(strtolower(ini_get('variables_order')), 'p');
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
}
//Cast group_id as int.
foreach(array(
        'group_id', 
        'atid', 
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

if (!IS_SCRIPT) {
    $cookie_manager =& new CookieManager();
    $GLOBALS['session_hash'] = $cookie_manager->isCookie('session_hash') ? $cookie_manager->getCookie('session_hash') : false;
}
//}}}

// Create cache directory if needed
if (!file_exists($GLOBALS['codendi_cache_dir'])) {
      // This directory must be world reachable, but writable only by the web-server
      mkdir($GLOBALS['codendi_cache_dir'], 0755);
}

// Instantiate System Event listener
require_once('common/system_event/SystemEventManager.class.php');
$system_event_manager = SystemEventManager::instance();

//Load plugins
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$plugin_manager->loadPlugins();

$feedback=''; // Initialize global var

//library to determine browser settings
if(!IS_SCRIPT) {
    require_once('browser.php');
    require_once('common/valid/ValidFactory.class.php');
}

//Language
if (!$GLOBALS['sys_lang']) {
    $GLOBALS['sys_lang']="en_US";
}
require('common/language/BaseLanguage.class.php');
$Language = new BaseLanguage($GLOBALS['sys_supported_languages'], $GLOBALS['sys_lang']);

//various html utilities
require_once('utils.php');

//database abstraction
require_once('database.php');
db_connect();

//security library
require_once('session.php');

//user functions like get_name, logged_in, etc
require_once('user.php');
require_once('common/user/User.class.php');
$current_user = UserManager::instance()->getCurrentUser();

//group functions like get_name, etc
require_once('common/project/Group.class.php');

//library to set up context help
require_once('help.php');

//exit_error library
require_once('exit.php');

//various html libs like button bar, themable
require_once('html.php');

//left-hand nav library, themable
require_once('menu.php');



//insert this page view into the database
if(!IS_SCRIPT) {
    require_once('logger.php');
}

/*

	Timezone must come after logger to prevent messups


*/
//set up the user's timezone if they are logged in
if (user_isloggedin()) {
    putenv('TZ='.$current_user->getTimezone());
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
require_once('common/project/Project.class.php');

// If the Codendi Software license was declined by the site admin
// so stop all accesses to the site. Use exlicit path to avoid
// loading the license.php file in the register directory when
// invoking project/register.php
if(!IS_SCRIPT) {
require_once(dirname(__FILE__).'/license.php');
if (license_already_declined()) {
    exit_error($Language->getText('global','error'),$Language->getText('include_pre','site_admin_declines_license',$GLOBALS['sys_email_admin']));
}
}
// Check if anonymous user is allowed to browse the site
// Bypass the test for:
// a) all scripts where you are not logged in by definition
// b) if it is a local access from localhost 

/*
print "<p>DBG: SERVER_NAME = ".$_SERVER['SERVER_NAME'];
print "<p>DBG: sys_allow_anon= ".$GLOBALS['sys_allow_anon'];
print "<p>DBG: user_isloggedin= ".user_isloggedin();
print "<p>DBG: SCRIPT_NAME = ".$_SERVER['SCRIPT_NAME']";
*/

// Check URL for valid hostname and valid protocol

if (!IS_SCRIPT) {
    require_once('common/include/URLVerificationFactory.class.php');
    $urlVerifFactory = new URLVerificationFactory();
    $urlVerif = $urlVerifFactory->getURLVerification($_SERVER);
    $urlVerif->assertValidUrl($_SERVER);
}

require_once('common/include/URL.class.php');
$request =& HTTPRequest::instance();
//Do nothing if we are not in a distributed architecture
if (!IS_SCRIPT &&
    isset($GLOBALS['sys_server_id']) && $GLOBALS['sys_server_id']) {
    require_once('common/project/Project.class.php');
    $redirect_to_master_if_needed = true;
    $sf      =& new ServerFactory();
    if ($_SERVER['SCRIPT_NAME'] == '/file/download.php') { //There is no group_id for /file/download.php
        $components = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($components[3])) {
            $pm = ProjectManager::instance();
            $p = $pm->getProject($components[3]);
        }
    } else if ($_SERVER['SCRIPT_NAME'] == '/svn/viewvc.php' && $request->get('roottype') == 'svn' && $request->exist('root')) { //There is no group_id for viewvc
        $res_grp=db_query("SELECT group_id FROM groups WHERE unix_group_name='". db_es($request->get('root')) ."'");
        if (db_numrows($res_grp) < 1) {
            //group was not found
            echo db_error();
            exit_error("Invalid Group","That group does not exist.");
        } else {
            $pm = ProjectManager::instance();
            $p = $pm->getProject(db_result($res_grp,0,'group_id'));
        }
    } else if ($request->exist('group_id')) {
        $pm = ProjectManager::instance();
        $p = $pm->getProject($request->get('group_id'));
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
    $e = 'You tried to upload a file that is larger than the Codendi post_max_size setting.';
    exit_error('Error', $e);
}

?>
