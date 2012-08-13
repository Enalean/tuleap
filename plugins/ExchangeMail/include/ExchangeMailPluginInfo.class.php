<?php
/**
 * Copyright (c) JTekt SAS, 2012. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2012
 *
 */
require_once 'common/plugin/PluginFileInfo.class.php';
require_once 'ExchangeMailPluginDescriptor.class.php';


/**
 * ExchangeMailPluginInfo
 */
class ExchangeMailPluginInfo extends PluginFileInfo {

    function __construct($plugin) 
    {
        parent::__construct($plugin, 'ExchangeMail');
        $this->setPluginDescriptor(new ExchangeMailPluginDescriptor());
    }
}
?>
