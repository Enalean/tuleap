<?php

/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonPluginDescriptor
 */
require_once('common/plugin/PluginDescriptor.class.php');

class hudsonPluginDescriptor extends PluginDescriptor {
    
    function hudsonPluginDescriptor() {
        $this->PluginDescriptor($GLOBALS['Language']->getText('plugin_hudson', 'descriptor_name'), '1.0', $GLOBALS['Language']->getText('plugin_hudson', 'descriptor_description'));
    }
}
?>