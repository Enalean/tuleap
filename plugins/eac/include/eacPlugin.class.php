<?php

require_once('common/plugin/Plugin.class.php');

class EacPlugin extends Plugin {
    
    function EacPlugin($id) {
        $this->Plugin($id);
    }
    
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'EacPluginInfo')) {
            require_once('EacPluginInfo.class.php');
            $this->pluginInfo =& new EacPluginInfo($this);
        }
        return $this->pluginInfo;
    }
}

?>
