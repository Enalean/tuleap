<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: Sanitizer.class,v 1.1 2005/05/10 09:48:11 nterray Exp $
 *
 * front-end to plugins administration
 */
 
require_once('pre.php');
require_once('common/plugin/PluginManager.class');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('pluginsadministration');
if ($p) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}

?>
