<?php

require_once 'common/plugin/PluginInfo.class.php';
require_once 'SolrPluginDescriptor.class.php';


/**
 * SolrPluginInfo
 */
class SolrPluginInfo extends PluginInfo {

    function __construct($plugin) {
        parent::__construct($plugin);
        $this->setPluginDescriptor(new SolrPluginDescriptor());
    }
}
?>