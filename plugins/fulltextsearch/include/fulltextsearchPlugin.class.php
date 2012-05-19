<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
    
    /**
     * Event triggered when a document is created
     * 
     * @param array $params
     */
    public function plugin_docman_after_new_document($params) {
        $this->getActions()->indexNewDocument($params);
    }
    
    /**
     * Event triggered when a document is deleted
     *
     * @param array $params
     */
    public function plugin_docman_event_del($params) {
        $this->getActions()->delete($params);
    }
    
    private function getSearchClient() {
        $client_path = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        
        require_once 'ElasticSearch/ClientFactory.class.php';
        $factory = new ElasticSearch_ClientFactory();
        return $factory->build($client_path, $server_host, $server_port);
    }
    
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'FulltextsearchPluginInfo')) {
            require_once('FulltextsearchPluginInfo.class.php');
            $this->pluginInfo = new FulltextsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    public function process() {
        // called by controller
    }
}

?>
