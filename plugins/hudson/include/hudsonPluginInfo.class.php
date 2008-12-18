<?php

/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonPluginInfo
 */

require_once('common/plugin/PluginInfo.class.php');
require_once('hudsonPluginDescriptor.class.php');

class hudsonPluginInfo extends PluginInfo {
    
    function hudsonPluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new hudsonPluginDescriptor());
    }
    
}
?>