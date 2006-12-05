<?php
require_once('pre.php');
if (!$group_id || !$project) {
	exit_error("Invalid Project","Invalid Project");
} else {

	define('VIRTUAL_PATH', $_SERVER['SCRIPT_NAME'] . '/' . $project->getUnixName());
	define('PATH_INFO_PREFIX', '/' . $project->getUnixName() . '/');

	define('WIKI_NAME', $project->getUnixName());
	//define('ALLOW_HTTP_AUTH_LOGIN', 1);
	//define('ADMIN_USER', '');
	//define('ADMIN_PASSWD', '');
        define('AUTH_SESS_USER', 'user_id');
        define('AUTH_SESS_LEVEL', 2);
        $USER_AUTH_ORDER = "Session : PersonalPage";
        $USER_AUTH_POLICY = "stacked";
	
	// Override the default configuration for CONSTANTS before index.php
	//$LANG='de'; $LC_ALL='de_DE';
	define('THEME', 'gforge');
	//define('WIKI_NAME', "WikiDemo:$LANG:" . THEME);

	// Load the default configuration.
	include "index.php";

	error_log ("PATH_INFO_PREFIX " . PATH_INFO_PREFIX);

	// Override the default configuration for VARIABLES after index.php:
	// E.g. Use another DB:
	$DBParams['dbtype'] = 'SQL';
	$DBParams['dsn']    = 'pgsql://' . $sys_dbuser . ':' . 
                              $sys_dbpasswd . '@' . $sys_dbhost .'/' . $sys_dbname
. '_wiki';
	$DBParams['prefix'] = $project->getUnixName() ."_";

	// If the user is logged in, let the Wiki know
	if (session_loggedin()){
            // let php do it's session stuff too!
            //ini_set('session.save_handler', 'files');
            session_start();
            $_SESSION['user_id'] = user_getname();

	} else {
            // clear out the globals, just in case... 

	}
	// Start the wiki
	include "lib/main.php";
}
?>