<?php
/**
 * Created on Mar 13, 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * front-end to plugins administration
 */

require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('IM');
if ($p) {
	$p->process();
} else {
    header('Location: '.get_server_url());
}

?>