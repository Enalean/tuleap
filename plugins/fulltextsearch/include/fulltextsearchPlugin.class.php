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

    public function __construct($id) {
        parent::__construct($id);

        // docman
        $this->_addHook('plugin_docman_after_new_document', 'plugin_docman_after_new_document', false);
        $this->_addHook('plugin_docman_event_del', 'plugin_docman_event_del', false);
        $this->_addHook('plugin_docman_event_update', 'plugin_docman_event_update', false);
        
        // site admin
        $this->_addHook('site_admin_option_hook',   'site_admin_option_hook', false);
        
        // style
        $this->_addHook('cssfile', 'cssfile', false);
    }

    private function getActions() {
        if (!isset($this->actions) && ($search_client = $this->getSearchClient())) {
            require_once 'FullTextSearchActions.class.php';
            $this->actions = new FullTextSearchActions($search_client, new Docman_PermissionsItemManager());
        }
        return $this->actions;
    }

    /**
     * Event triggered when a document is updated
     *
     * @param array $params
     */
    public function plugin_docman_event_update($params) {
        $this->getActions()->updateDocument($params);
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

    /**
     * Event to display something in siteadmin interface
     * 
     * @param array $params 
     */
    public function site_admin_option_hook($params) {
        echo '<li><a href="'.$this->getPluginPath().'/">Full Text Search</a></li>';
    }
    
    /**
     * Event to load css stylesheet
     * 
     * @param array $params 
     */
    public function cssfile($params) {
        // Only show the stylesheet if we're actually in the FullTextSearch pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
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
        // Grant access only to site admin
        if (!UserManager::instance()->getCurrentUser()->isSuperUser()) {
            header('Location: ' . get_server_url());
        }

        include_once 'FullTextSearch/SearchController.class.php';
        $request         = HTTPRequest::instance();
        $project_manager = ProjectManager::instance();
        $controller      = new FullTextSearch_SearchController($request, $this->getSearchClient(), $project_manager);
        $controller->search();
    }
}

?>
