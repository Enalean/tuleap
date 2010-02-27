<?php
/**
 * GitPHP
 *
 * Index
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

/**
 * Define some paths
 */
define('GITPHP_BASEDIR', dirname(__FILE__) . '/');
define('GITPHP_CONFIGDIR', GITPHP_BASEDIR . 'config/');
define('GITPHP_INCLUDEDIR', GITPHP_BASEDIR . 'include/');
define('GITPHP_GITOBJECTDIR', GITPHP_INCLUDEDIR . 'git/');
define('GITPHP_CONTROLLERDIR', GITPHP_INCLUDEDIR . 'controller/');

/*
 * Version
 */
include_once(GITPHP_INCLUDEDIR . 'version.php');

/*
 * Constants
 */
require_once(GITPHP_INCLUDEDIR . 'defs.constants.php');

require_once(GITPHP_INCLUDEDIR . 'Config.class.php');

require_once(GITPHP_GITOBJECTDIR . 'ProjectList.class.php');

require_once(GITPHP_INCLUDEDIR . 'MessageException.class.php');
require_once(GITPHP_CONTROLLERDIR . 'Controller.class.php');

date_default_timezone_set('UTC');

try {

	/*
	 * Configuration
	 */
	try {
		GitPHP_Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php.example');
	} catch (Exception $e) {
	}
	GitPHP_Config::GetInstance()->LoadConfig(GITPHP_CONFIGDIR . 'gitphp.conf.php');

	/*
	 * Project list
	 */
	GitPHP_ProjectList::Instantiate(GITPHP_CONFIGDIR . 'gitphp.conf.php');

	if (!GitPHP_Config::GetInstance()->GetValue('projectroot', null)) {
		throw new GitPHP_MessageException('A projectroot must be set in the config', true);
	}

	$gitphp_current_project = null;

	if (isset($_GET['p'])) {
		$gitphp_current_project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
	}

	$controller = GitPHP_Controller::GetController((isset($_GET['a']) ? $_GET['a'] : null));
	if ($controller) {
		$controller->RenderHeaders();
		$controller->Render();
	}

} catch (Exception $e) {

	require_once(GITPHP_CONTROLLERDIR . 'Controller_Message.class.php');
	$controller = new GitPHP_Controller_Message();
	$controller->SetParam('message', $e->getMessage());
	if ($e instanceof GitPHP_MessageException) {
		$controller->SetParam('error', $e->Error);
	} else {
		$controller->SetParam('error', true);
	}
	$controller->Render();

}

?>
