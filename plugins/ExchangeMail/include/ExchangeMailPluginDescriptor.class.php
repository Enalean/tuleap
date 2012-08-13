<?php
/**
 * Copyright (c) JTekt SAS, 2012. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2012
 *
 */
require_once 'common/plugin/PluginDescriptor.class.php';

/**
 * ExchangeMailPluginDescriptor
 */
class ExchangeMailPluginDescriptor extends PluginDescriptor {

    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_ExchangeMail', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_ExchangeMail', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>
