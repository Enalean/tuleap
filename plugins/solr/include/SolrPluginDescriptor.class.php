<?php

require_once 'common/plugin/PluginDescriptor.class.php';

/**
 * SolrPluginDescriptor
 */
class SolrPluginDescriptor extends PluginDescriptor {

    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_solr', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_solr', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>