<?php

require_once('common/plugin/Plugin.class.php');

class elasticsearchPlugin extends Plugin {
        
    function elasticsearchPlugin($id) {
        $this->Plugin($id);
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('site_admin_external_tool_hook', 'site_admin_external_tool_hook', false);
        $this->_addHook('site_admin_external_tool_selection_hook', 'site_admin_external_tool_selection_hook', false);
        
        // docman
        $this->_addHook('plugin_docman_event_add', 'addDocman', false);
    }
    
    function addDocman($params) {
        $event = 'plugin_docman_event_add';
        /*$df = CodendiSolrDocumentFactory::instance();
        $document = $df->getCodendiSolrDocumentFromEventParams($event, $params);*/
        $this->_addDocumentInSolr($document);
    }
    
    /**
     * Add or Update a document in SolR
     *
     * @param Apache_Solr_Document $document the document to index in SolR
     *
     * @return void
     */
    private function _addDocumentInElasticSearch(ElasticSearch_Document $document) {
        /*$solr_server = $this->getPluginInfo()->getPropVal('solr_server');
        $solr_port   = $this->getPluginInfo()->getPropVal('solr_port');
        $solr_path   = $this->getPluginInfo()->getPropVal('solr_path');
        //$solr = new Celi_Apache_Solr_Service($solr_server, $solr_port, $solr_path);
        $solr = new Apache_Solr_Service($solr_server, $solr_port, $solr_path);
        if ( ! $solr->ping() ) {
            //$GLOBALS['Response']->addFeedback('error', 'SolR server server not found.');
        }
        $gus = $solr->_getUpdateServlet();
        //$gus ='update/celi';
        $solr->_setUpdateServlet($gus);
        $gus = $solr->_getUpdateServlet();
        
        try {
            if ($solr->addDocument($document)) {
                //$GLOBALS['Response']->addFeedback('info', 'Document indexed.');
            } else {
                //$GLOBALS['Response']->addFeedback('error', 'Impossible to index document');
                // Log into backend?
            }
            $solr->commit(); //commits to see the deletes and the document
            $solr->optimize(); //merges multiple segments into one
        } catch (Exception $e) {
            //$GLOBALS['Response']->addFeedback('error', $e->getMessage());
            // Log into backend?
        }*/
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
