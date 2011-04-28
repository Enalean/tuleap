<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * front-end to plugin salome */
 
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('salome');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}



?>
