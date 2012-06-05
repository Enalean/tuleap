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
        include_once 'FullTextSearch/SearchPresenter.class.php';
        
        $user_manager = UserManager::instance();
        $request      = HTTPRequest::instance();
        
        // Grant access only to site admin
        if (!$user_manager->getCurrentUser()->isSuperUser()) {
            header('Location: ' . get_server_url());
        }

        $terms = $request->get('query');
        
        $client = $this->getSearchClient();
        $search_result = $client->search($terms);
        
        $query_presenter = new FullTextSearch_SearchPresenter($terms, $search_result);

        $title = 'Full text search';
        $GLOBALS['HTML']->header(array('title' => $title));
        $renderer = new MustacheRenderer('../templates');
        $renderer->render('query', $query_presenter);
        $GLOBALS['HTML']->footer(array());
    }
}

?>
