<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/plugin/Plugin.class.php';

class fulltextsearchPlugin extends Plugin {
    
    private $actions;
    
    public function fulltextsearchPlugin($id) {
        $this->Plugin($id);
                
        $this->_addHook('javascript_file', 'jsFile', false);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('site_admin_external_tool_hook', 'site_admin_external_tool_hook', false);
        $this->_addHook('site_admin_external_tool_selection_hook', 'site_admin_external_tool_selection_hook', false);
        
        // docman
        $this->_addHook('plugin_docman_after_new_document', 'plugin_docman_after_new_document', false);
        $this->_addHook('plugin_docman_event_del', 'plugin_docman_event_del', false);
    }
    
    protected function getActions() {
        if (!isset($this->actions) && ($search_client = $this->getSearchClient())) {
            require_once dirname(__FILE__).'/FullTextSearchActions.class.php';
            $this->actions = new FullTextSearchActions($search_client);
        }
        return $this->actions;
    }
    
    public function plugin_docman_after_new_document($params) {
        $this->getActions()->indexNewDocument($params);
    }
    
    public function plugin_docman_event_del($params) {
        $this->getActions()->delete($params);
    }
    
    private function getSearchClient() {
        $client_path = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        if (!file_exists($client_path)) {
            $GLOBALS['Response']->addFeedback('error', "PHP Client Library not found");
            $GLOBALS['HTML']->redirect('/docman/?group_id='.$this->getId());
            return null;
        }
        
        $client_host = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $client_port = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        
        require_once($client_path.'/ElasticSearchClient.php');
        
        $transport   = new ElasticSearchTransportHTTP($client_host, $client_port);
        return new ElasticSearchClient($transport, 'tuleap', 'docman');
    }
    
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'FulltextsearchPluginInfo')) {
            require_once('FulltextsearchPluginInfo.class.php');
            $this->pluginInfo = new FulltextsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    
    public function cssFile($params) {
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
    
    public function jsFile($params) {
        // Only include the js files if we're actually in the IM pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
        }
    }
    
    public function site_admin_external_tool_hook($params) {
       global $Language;
       echo '<li><a href="externaltools.php?tool=solr">' .
            $GLOBALS['Language']->getText('plugin_fulltextsearch','link_fulltextsearch_admin_tool') .
            '</a></li>';
    }
        
    public function site_admin_external_tool_selection_hook($params) {
        /*if ($params['tool'] == 'solr') {
            $solr_server = $this->getPluginInfo()->getPropVal('solr_server');
            $solr_port   = $this->getPluginInfo()->getPropVal('solr_port');
            $solr_path   = $this->getPluginInfo()->getPropVal('solr_path');
            $params['title'] = "Full text Search  Administration";
            $params['src']   = 'http://' . $solr_server . ':' . $solr_port . $solr_path . '/admin/';
        }*/
    }
    
    public function process() {
        require_once('FullTextSearch.class.php');
        $controller = new FullTextSearch($this->actions);
        $controller->process();
    }
}

?>
