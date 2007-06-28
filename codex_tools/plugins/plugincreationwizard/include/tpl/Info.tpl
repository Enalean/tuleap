

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * <?=$class_name?>PluginInfo
 */
require_once('common/plugin/PluginInfo.class.php');
require_once('<?=$class_name?>PluginDescriptor.class.php');

class <?=$class_name?>PluginInfo extends PluginInfo {
    
    function <?=$class_name?>PluginInfo(&$plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new <?=$class_name?>PluginDescriptor());
    }
    
}
