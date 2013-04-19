<?php
/*
 * Classe CloudStoragePluginDescriptor
 */
 
require_once 'common/plugin/PluginDescriptor.class.php';

class CloudStoragePluginDescriptor extends PluginDescriptor {
    function __construct() {
        parent::__construct($GLOBALS['Language']->getText('plugin_cloudstorage', 'descriptor_name'), false, $GLOBALS['Language']->getText('plugin_cloudstorage', 'descriptor_description'));
        $this->setVersionFromFile(dirname(__FILE__).'/../VERSION');
    }
}
?>
