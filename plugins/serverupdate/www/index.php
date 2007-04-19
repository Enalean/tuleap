<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * front-end to plugins administration
 */
 
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('serverupdate');
if ($p) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}

?>
