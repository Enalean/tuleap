<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

/** CORE APPLICATION CONTROLLER **/

// Retrieve values from configuration file
require_once 'sys/ConfigArray.php';
$configArray = readConfig();

// Try to set the locale to UTF-8, but fail back to the exact string from the config
// file if this doesn't work -- different systems may vary in their behavior here.
setlocale(LC_MONETARY, array($configArray['Site']['locale'] . ".UTF-8", 
    $configArray['Site']['locale']));
date_default_timezone_set($configArray['Site']['timezone']);

// Require System Libraries
require_once 'PEAR.php';
require_once 'sys/Interface.php';
require_once 'sys/Logger.php';
require_once 'sys/User.php';
require_once 'sys/Translator.php';
require_once 'sys/SearchObject/Factory.php';

// Set up autoloader (needed for YAML)
function vufind_autoloader($class) {
    require str_replace('_', '/', $class) . '.php';
}
spl_autoload_register('vufind_autoloader');

// Sets global error handler for PEAR errors
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, 'handlePEARError');

// Sets global error handler for PHP errors
//set_error_handler('handlePHPError');

if ($configArray['System']['debug']) {
    ini_set('display_errors', true);
    error_reporting(E_ALL);
}

// Start Interface
$interface = new UInterface();

// Check system availability
$mode = checkAvailabilityMode();
if ($mode['online'] === false) {
    // Why are we offline?
    switch ($mode['level']) {
      // Forced Downtime
      case "unavailable":
          // TODO : Variable reasons, and translated
          //$interface->assign('message', $mode['message']);
          $interface->display($mode['template']);
          break;

      // Should never execute. checkAvailabilityMode() would 
      //    need to know we are offline, but not why.
      default:
          // TODO : Variable reasons, and translated
          //$interface->assign('message', $mode['message']);
          $interface->display($mode['template']);
          break;
    }
    exit();
}

// Proxy server settings
if (isset($configArray['Proxy']['host'])) {
  if (isset($configArray['Proxy']['port'])) {
    $proxy_server = $configArray['Proxy']['host'].":".$configArray['Proxy']['port'];
  } else {
    $proxy_server = $configArray['Proxy']['host'];
  }
  $proxy = array('http' => array('proxy' => "tcp://$proxy_server", 'request_fulluri' => true));
  stream_context_get_default($proxy);
}

// Include Search Engine Class
require_once 'sys/' . $configArray['Index']['engine'] . '.php';

// Setup Translator
if (isset($_POST['mylang'])) {
    $language = $_POST['mylang'];
    setcookie('language', $language, null, '/');
} else {
    $language = (isset($_COOKIE['language'])) ? $_COOKIE['language'] :
                    $configArray['Site']['language'];
}
// Make sure language code is valid, reset to default if bad:
$validLanguages = array_keys($configArray['Languages']);
if (!in_array($language, $validLanguages)) {
    $language = $configArray['Site']['language'];
}
$translator = new I18N_Translator('lang', $language, $configArray['System']['debug']);
$interface->setLanguage($language);

// Setup Local Database Connection
define('DB_DATAOBJECT_NO_OVERLOAD', 0);
$options =& PEAR::getStaticProperty('DB_DataObject', 'options');
$options = $configArray['Database'];

// Initiate Session State
$session_type = $configArray['Session']['type'];
$session_lifetime = $configArray['Session']['lifetime'];
require_once 'sys/' . $session_type . '.php';
if (class_exists($session_type)) {
   $session = new $session_type();
   $session->init($session_lifetime);
}

// Determine Module and Action
$module = ($user = UserAccount::isLoggedIn()) ? 'MyResearch' : $configArray['Site']['defaultModule'];
$module = (isset($_GET['module'])) ? $_GET['module'] : $module;
$module = preg_replace('/[^\w]/', '', $module);
$interface->assign('module', $module);
$action = (isset($_GET['action'])) ? $_GET['action'] : 'Home';
$action = preg_replace('/[^\w]/', '', $action);
$interface->assign('action', $action);

// Process Authentication
if ($user) {
    $interface->assign('user', $user);
} else if (// Special case for Shibboleth:
    ($configArray['Authentication']['method'] == 'Shibboleth' && $module == 'MyResearch') ||
    // Default case for all other authentication methods:
    ((isset($_POST['username']) && isset($_POST['password'])) && ($_GET['action'] != 'Account'))) {
    $user = UserAccount::login();
    if (PEAR::isError($user)) {
        require_once 'services/MyResearch/Login.php';
        Login::launch($user->getMessage());
        exit();
    }
    $interface->assign('user', $user);
}

// Process Login Followup
if (isset($_REQUEST['followup'])) {
    processFollowup();
}

// Call Action
if (is_readable("services/$module/$action.php")) {
    require_once "services/$module/$action.php";
    if (class_exists($action)) {
        $service = new $action();
        $service->launch();
    } else {
        PEAR::raiseError(new PEAR_Error('Unknown Action'));
    }
} else {
    PEAR::RaiseError(new PEAR_Error('Cannot Load Action'));
}

function processFollowup()
{
    global $configArray;

    switch($_REQUEST['followup']) {
        case 'SaveRecord':
            $result = file_get_contents($configArray['Site']['url'] .
                    "/Record/AJAX?method=SaveRecord&id=" . urlencode($_REQUEST['id']));
            break;
        case 'SaveTag':
            $result = file_get_contents($configArray['Site']['url'] .
                    "/Record/AJAX?method=SaveTag&id=" . urlencode($_REQUEST['id']) .
                    "&tag=" . urlencode($_REQUEST['tag']));
            break;
        case 'SaveComment':
            $result = file_get_contents($configArray['Site']['url'] .
                    "/Record/AJAX?method=SaveComment&id=" . urlencode($_REQUEST['id']) .
                    "&comment=" . urlencode($_REQUEST['comment']));
            break;
        case 'SaveSearch':
            header("Location: {$configArray['Site']['url']}/".$_REQUEST['followupModule']."/".$_REQUEST['followupAction']."?".$_REQUEST['recordId']);
            die();
            break;
    }
}

// Process any errors that are thrown
function handlePEARError($error, $method = null)
{
    global $configArray;
    
    // It would be really bad if an error got raised from within the error handler;
    // we would go into an infinite loop and run out of memory.  To avoid this,
    // we'll set a static value to indicate that we're inside the error handler.
    // If the error handler gets called again from within itself, it will just
    // return without doing anything to avoid problems.  We know that the top-level
    // call will terminate execution anyway.
    static $errorAlreadyOccurred = false;
    if ($errorAlreadyOccurred) {
        return;
    } else {
        $errorAlreadyOccurred = true;
    }
    
    // Display an error screen to the user:
    $interface = new UInterface();

    $interface->assign('error', $error);
    $interface->assign('debug', $configArray['System']['debug']);
    
    $interface->display('error.tpl');

    // Exceptions we don't want to log
    $doLog = true;
    // Microsoft Web Discussions Toolbar polls the server for these two files
    //    it's not script kiddie hacking, just annoying in logs, ignore them.
    if (strpos($_SERVER['REQUEST_URI'], "cltreq.asp") !== false) $doLog = false;
    if (strpos($_SERVER['REQUEST_URI'], "owssvr.dll") !== false) $doLog = false;
    // If we found any exceptions, finish here
    if (!$doLog) exit();

    // Log the error for administrative purposes -- we need to build a variety
    // of pieces so we can supply information at five different verbosity levels:
    $baseError = $error->toString();
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'none';
    $basicServer = " (Server: IP = {$_SERVER['REMOTE_ADDR']}, " .
        "Referer = {$referer}, " .
        "User Agent = {$_SERVER['HTTP_USER_AGENT']}, " .
        "Request URI = {$_SERVER['REQUEST_URI']})";
    $detailedServer = "\nServer Context:\n" . print_r($_SERVER, true);
    $basicBacktrace = "\nBacktrace:\n";
    if (is_array($error->backtrace)) {
        foreach($error->backtrace as $line) {
            $basicBacktrace .= "{$line['file']} line {$line['line']} - " .
                "class = {$line['class']}, function = {$line['function']}\n";
        }
    }
    $detailedBacktrace = "\nBacktrace:\n" . print_r($error->backtrace, true);
    $errorDetails = array(
        1 => $baseError,
        2 => $baseError . $basicServer,
        3 => $baseError . $basicServer . $basicBacktrace,
        4 => $baseError . $detailedServer . $basicBacktrace,
        5 => $baseError . $detailedServer . $detailedBacktrace
        );

    $logger = new Logger();
    $logger->log($errorDetails, PEAR_LOG_ERR);
    
    exit();
}

// Check for the various stages of functionality
function checkAvailabilityMode() {
    global $configArray;
    $mode = array();

    // If the config file 'available' flag is
    //    set we are forcing downtime.
    if (!$configArray['System']['available']) {
        $mode['online']   = false;
        $mode['level']    = 'unavailable';
        // TODO : Variable reasons passed to template... and translated
        //$mode['message']  = $configArray['System']['available_reason'];
        $mode['template'] = 'unavailable.tpl';
        return $mode;
    }
    // TODO : Check if solr index is online
    // TODO : Check if ILMS database is online
    // TODO : More?

    // No problems? We are online then
    $mode['online'] = true;
    return $mode;
}
?>
