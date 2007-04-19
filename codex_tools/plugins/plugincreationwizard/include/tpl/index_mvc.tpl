
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * front-end to plugin <?=$class_name?>
 */
 
require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('<?=$short_name?>');
if ($p && $plugin_manager->isPluginAvailable($p)) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}


