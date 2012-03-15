<?php

require_once 'common/plugin/PluginDescriptor.class.php';


class ElasticsearchPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct('elastic search', false, 'elastic search');
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>