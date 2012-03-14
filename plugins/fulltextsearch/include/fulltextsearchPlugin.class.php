<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 *
 * FullTextSearchPlugin
 */

require_once('common/plugin/Plugin.class.php');
require_once('CodendiSolrDocumentFactory.class.php');
require_once(dirname(__FILE__) . '/../etc/solr/SolrPhpClient/Apache/Solr/CeliService.php' );

class fulltextsearchPlugin extends Plugin {
    
    function fulltextsearchPlugin($id) {
        $this->Plugin($id);
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('site_admin_external_tool_hook', 'site_admin_external_tool_hook', false);
        $this->_addHook('site_admin_external_tool_selection_hook', 'site_admin_external_tool_selection_hook', false);
        
        // docman
        $this->_addHook('plugin_docman_event_add', 'addDocman', false);
        $this->_addHook('plugin_docman_event_update', 'updateDocman', false);
        $this->_addHook('plugin_docman_event_new_version', 'newVersionDocman', false);
        $this->_addHook('plugin_docman_event_edit', 'edit', false); //if modif of link_url or wiki_page
        $this->_addHook('plugin_docman_event_metadata_update', 'metadata_update', false);
        $this->_addHook('plugin_docman_event_del', 'deleteDocman', false);
        
        // forum
        $this->_addHook('forum_event_add_message', 'addForumMessage', false);
    }
    
    function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'fulltextsearchPluginInfo')) {
            require_once('fulltextsearchPluginInfo.class.php');
            $this->pluginInfo = new fulltextsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    /**
     * Add or Update a document in SolR
     *
     * @param Apache_Solr_Document $document the document to index in SolR
     *
     * @return void
     */
    private function _addDocumentInSolr(Apache_Solr_Document $document) {
        var_dump('passe ici');
        $solr_server = $this->getPluginInfo()->getPropVal('solr_server');
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
        }
    }
    
    /**
     * Delete a document in SolR
     *
     * @param int $item_id the document Id
     *
     * @return void
     */
    private function _deleteDocumentInSolr($item_id) {
        $solr_server = $this->getPluginInfo()->getPropVal('solr_server');
        $solr_port   = $this->getPluginInfo()->getPropVal('solr_port');
        $solr_path   = $this->getPluginInfo()->getPropVal('solr_path');
        $solr = new Celi_Apache_Solr_Service($solr_server, $solr_port, $solr_path);
        if ( ! $solr->ping() ) {
            //$GLOBALS['Response']->addFeedback('error', 'SolR server server not found.');
        }
        $gus = $solr->_getUpdateServlet();
        $gus ='update/celi';
        $solr->_setUpdateServlet($gus);
        $gus = $solr->_getUpdateServlet();
        
        try {
            if ($solr->deleteById($item_id)) {
                //$GLOBALS['Response']->addFeedback('info', 'Document removed from Solr.');
            } else {
                //$GLOBALS['Response']->addFeedback('error', 'Impossible to removed document from Solr');
                // Log into backend?
            }
            $solr->commit(); //commits to see the deletes and the document
            $solr->optimize(); //merges multiple segments into one
        } catch (Exception $e) {
            //$GLOBALS['Response']->addFeedback('error', $e->getMessage());
            // Log into backend?
        }
    }
    
    function addDocman($params) {
        $event = 'plugin_docman_event_add';
        $df = CodendiSolrDocumentFactory::instance();
        $document = $df->getCodendiSolrDocumentFromEventParams($event, $params);
        $this->_addDocumentInSolr($document);
    }
    
    /**
     * Update title, description and metadata of the document (any kind of documents)
     * 
     * $params['group_id']    => (int) group_id of the document
     * $params['item']        => (Docman_Item) old item (before update)
     * $params['user']        => (User) user that updated the document
     * $params['metadata']    => array of metadata
     * $params['permissions'] => ...
     * $params['data']        => (Docman_Item) the new item (updated)
     * $params['file']        => array of ...
     *
     */
    function updateDocman($params) {
        $event = 'plugin_docman_event_update';
        $df = CodendiSolrDocumentFactory::instance();
        $document = $df->getCodendiSolrDocumentFromEventParams($event, $params);
        $this->_addDocumentInSolr($document);
    }
    
    /**
     * New version of document (File and EmbeddedFile)
     * Only document is updated in this function.
     * 
     * $params['group_id']    => (int) group_id of the document
     * $params['item']        => (Docman_Item) item
     * $params['user']        => (User) user that updated the document
     * $params['metadata']    => array of metadata '0' => (string) metadata1
     *                                             '1' => (string) metadata2
     * $params['new_version'] => array of 'item_id' => (int) item id
     *                                     'number' => (int) number of versions
     *                                     'user_id' => (int) user id that created a new version
     *                                     'label' => (string) label of the new version
     *                                     'changelog' => (string) change log for the new version
     *                                     'filename => (string) name of the new version file
     *                                     'filesize => (int) size of the new version file
     *                                     'filetype => (string) type of the new version file
     *                                     'path' => (string) path of the new version file
     *                                     'date => ...
     *
     */
    function newVersionDocman($params) {
        $event = 'plugin_docman_event_new_version';
        $df = CodendiSolrDocumentFactory::instance();
        $document = $df->getCodendiSolrDocumentFromEventParams($event, $params);
        $this->_addDocumentInSolr($document);
    }
    
    // TODO : does not work
    function edit($params) {
        $GLOBALS['Response']->addFeedback('info', 'edit');
        foreach($params as $key => $value) {
            $GLOBALS['Response']->addFeedback('warning', $key .' => '. $value);
        }
    }
    
    // TODO : does not work
    function metadata_update($params) {
        $GLOBALS['Response']->addFeedback('info', 'metadata_update');
        foreach($params as $key => $value) {
            $GLOBALS['Response']->addFeedback('warning', $key .' => '. $value);
        }
    }
    
    function deleteDocman($params) {
        $item = $params['item'];
        $this->_deleteDocumentInSolr(CodendiSolrDocumentFactory::DOCUMENT . '_' . $item->getId());
    }
    
    /**
     *
     */
    function addForumMessage($params) {
        $event = 'forum_event_add_message';
        $df = CodendiSolrDocumentFactory::instance();
        $forum = $df->getCodendiSolrDocumentFromEventParams($event, $params);
        $this->_addDocumentInSolr($forum);
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
        echo '<li><a href="externaltools.php?tool=solr">' .
            $GLOBALS['Language']->getText('plugin_fulltextsearch','link_solr_admin_tool') .
            '</a></li>';
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