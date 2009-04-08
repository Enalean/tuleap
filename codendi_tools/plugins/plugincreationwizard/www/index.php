<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * front-end to plugin creation wizard
 */
 
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('plugincreationwizard');
if ($p) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}

?>
