<?php
/*
 * Classe CloudStoragePluginInfo
 */

require_once('common/plugin/PluginFileInfo.class.php');
require_once('CloudStoragePluginDescriptor.class.php');

class CloudStoragePluginInfo extends PluginFileInfo {
    function __construct($plugin) {
        $this->PluginInfo($plugin);
        $this->setPluginDescriptor(new CloudStoragePluginDescriptor());
        $this->_conf_path = $plugin->getPluginEtcRoot() .'/cloudstorage.inc';
        $this->loadProperties();       
    }
}
?>
