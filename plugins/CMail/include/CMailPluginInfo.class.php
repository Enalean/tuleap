<?php
/**
 * Copyright (c) JTekt SAS, 2012. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2012
 *
 */
require_once 'common/plugin/PluginFileInfo.class.php';
require_once 'CMailPluginDescriptor.class.php';


/**
 * CMailPluginInfo
 */
class CMailPluginInfo extends PluginFileInfo {

    function __construct($plugin) 
    {
        parent::__construct($plugin, 'CMail');
        $this->setPluginDescriptor(new CMailPluginDescriptor());
    }
}
?>
