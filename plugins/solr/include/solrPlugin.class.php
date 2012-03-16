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
    
    function addDocman($params) {
        $ch = curl_init('http://localhost:8983/solr/update/extract?literal.id='.$params['item']->getId().'&commit=true');
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'title'       => $params['item']->getTitle(),
            'description' => $params['item']->getDescription(),
            'file'        => base64_encode(file_get_contents($params['version']->getPath()))
        ));
        curl_exec($ch);
        curl_close($ch);
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