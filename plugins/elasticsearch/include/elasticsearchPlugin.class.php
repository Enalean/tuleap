<?php

require_once('common/plugin/Plugin.class.php');

class elasticsearchPlugin extends Plugin {
    
    private $elasticSearchClient;
    
    function elasticsearchPlugin($id) {
        $this->Plugin($id);
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('site_admin_external_tool_hook', 'site_admin_external_tool_hook', false);
        $this->_addHook('site_admin_external_tool_selection_hook', 'site_admin_external_tool_selection_hook', false);
        
        // docman
//        $this->_addHook('plugin_docman_event_add', 'addDocman', false);
        $this->_addHook('plugin_docman_after_new_document', 'addDocman', false);
    }
    
    function addDocman($params) {
        $this->getElasticSearchClient()->index(
            array(
                'title'       => $params['item']->getTitle(),
                'description' => $params['item']->getDescription(),
                'file'        => base64_encode(file_get_contents($params['version']->getPath()))
            ),
            $params['item']->getId()
        );
    }
    
    private function getElasticSearchClient() {
        if ($this->elasticSearchClient == null) {
            $elasticsearch_path = $this->getPluginInfo()->getPropertyValueForName('elasticsearch_path');
            $elasticsearch_host = $this->getPluginInfo()->getPropertyValueForName('elasticsearch_host');
            $elasticsearch_port = $this->getPluginInfo()->getPropertyValueForName('elasticsearch_port');
            
            require_once($elasticsearch_path.'/ElasticSearchClient.php');
            
            $transport                 = new ElasticSearchTransportHTTP("localhost", 9200);
            $this->elasticSearchClient = new ElasticSearchClient($transport, "tuleap", "docman");
        }
        return $this->elasticSearchClient;
    }
    
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'ElasticsearchPluginInfo')) {
            require_once('ElasticsearchPluginInfo.class.php');
            $this->pluginInfo =& new ElasticsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the fulltextsearch pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/my/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/projects/') === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }
    
    function jsFile($params) {
        // Only include the js files if we're actually in the IM pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
        }
    }
    
    function site_admin_external_tool_hook($params) {
       global $Language;
        /*echo '<li><a href="externaltools.php?tool=solr">' .
            $GLOBALS['Language']->getText('plugin_fulltextsearch','link_solr_admin_tool') .
            '</a></li>';*/
    }
        
    function site_admin_external_tool_selection_hook($params) {
        if ($params['tool'] == 'solr') {
            $solr_server = $this->getPluginInfo()->getPropVal('solr_server');
            $solr_port   = $this->getPluginInfo()->getPropVal('solr_port');
            $solr_path   = $this->getPluginInfo()->getPropVal('solr_path');
            $params['title'] = "Solr Administration";
            $params['src']   = 'http://' . $solr_server . ':' . $solr_port . $solr_path . '/admin/';
        }
    }
    
    function process() {
        require_once('fulltextsearch.class.php');
        $controler =& new fulltextsearch();
        $controler->process();
    }
}

?>
