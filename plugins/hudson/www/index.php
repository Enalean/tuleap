<?php

/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * front-end to plugin hudson
 */

require_once('pre.php');
require_once('common/plugin/PluginManager.class.php');

$plugin_manager =& PluginManager::instance();
$p =& $plugin_manager->getPluginByName('hudson');

if ($p && $plugin_manager->isPluginAvailable($p)) {
    $p->process();
} else {
    header('Location: '.get_server_url());
}

?>