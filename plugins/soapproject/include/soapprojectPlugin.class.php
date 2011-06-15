<?php

require_once 'common/plugin/Plugin.class.php';

/**
 * SoapProjectPlugin
 */
class SoapProjectPlugin extends Plugin {

    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
    }

    /**
     * @return SoapProjectPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'SoapProjectPluginInfo.class.php';
            $this->pluginInfo = new SoapProjectPluginInfo($this);
        }
        return $this->pluginInfo;
    }
}

?>