<?php

require_once 'common/plugin/PluginDescriptor.class.php';


class FulltextsearchPluginDescriptor extends PluginDescriptor {
    
    function __construct() {
        parent::__construct('full text search', false, 'full text search');
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>