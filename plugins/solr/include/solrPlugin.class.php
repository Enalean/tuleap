<?php

require_once 'common/plugin/Plugin.class.php';

/**
 * SolrPlugin
 */
class SolrPlugin extends Plugin {

    /**
     * Plugin constructor
     */
    function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        $this->_addHook('plugin_docman_after_new_document', 'addDocman', false);
    }

    /**
     * @return SolrPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo) {
            include_once 'SolrPluginInfo.class.php';
            $this->pluginInfo = new SolrPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
}

?>