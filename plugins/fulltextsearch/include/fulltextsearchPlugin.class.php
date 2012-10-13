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
require_once 'autoload.php';

class fulltextsearchPlugin extends Plugin {

    const SEARCH_TYPE = 'fulltext';

    private $actions;

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->allowed_for_project = array();

        // docman
        $this->_addHook('plugin_docman_after_new_document', 'plugin_docman_after_new_document', false);
        $this->_addHook('plugin_docman_event_del', 'plugin_docman_event_del', false);
        $this->_addHook('plugin_docman_event_update', 'plugin_docman_event_update', false);
        $this->_addHook('plugin_docman_event_perms_change', 'plugin_docman_event_perms_change', false);
        $this->_addHook('plugin_docman_event_new_version', 'plugin_docman_event_new_version', false);

        // site admin
        $this->_addHook('site_admin_option_hook',   'site_admin_option_hook', false);

        // assets
        $this->_addHook('cssfile', 'cssfile', false);
        $this->_addHook(Event::COMBINED_SCRIPTS, 'combined_scripts', false);

        // system events
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'get_system_event_class', false);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES, 'system_event_get_types', false);

        // Search
        $this->_addHook('search_type_entry', 'search_type_entry', false);
        $this->_addHook('search_type', 'search_type', false);
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    public function search_type_entry($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            $params['output'] .= '<option value="'. self::SEARCH_TYPE .'" ';
            if ($params['type_of_search'] == self::SEARCH_TYPE) {
                $params['output'] .= 'selected="selected"';
            }
            $params['output'] .= '>'. 'Fulltext';
            $params['output'] .= '</option>';
        }
    }

    public function search_type($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            if ($params['type_of_search'] === self::SEARCH_TYPE) {
                $params['search_type']        = true;
                $params['pagination_handled'] = true;

                $this->getSearchController()->search();
            }
        }
    }

    public function system_event_get_types($params) {
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_INDEX';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_DELETE';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE';
    }

    /**
     * This callback make SystemEvent manager knows about fulltext plugin System Events
     */
    public function get_system_event_class($params) {
        if (strpos($params['type'], 'FULLTEXTSEARCH_') !== false) {
            $params['class']        = 'SystemEvent_'. $params['type'];
            $params['dependencies'] = array($this->getActions(), new Docman_ItemFactory(), new Docman_VersionFactory());
            require_once $params['class'] .'.class.php';
        }
    }

    /**
     * Return true if given project has the right to use this plugin.
     *
     * @param string $group_id
     *
     * @return bool
     */
    function isAllowed($group_id) {
        if(!isset($this->allowedForProject[$group_id])) {
            $this->allowed_for_project[$group_id] = PluginManager::instance()->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowed_for_project[$group_id];
    }

    private function getActions() {
        if (!isset($this->actions) && ($search_client = $this->getIndexClient())) {
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
        $this->createSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA', SystemEvent::PRIORITY_MEDIUM, $params['item']);
    }

    /**
     * Event triggered when a document is created
     *
     * @param array $params
     */
    public function plugin_docman_after_new_document($params) {
        $this->createSystemEvent('FULLTEXTSEARCH_DOCMAN_INDEX', SystemEvent::PRIORITY_MEDIUM, $params['item'], $params['version']->getNumber());
    }

    /**
     * Event triggered when a document is deleted
     *
     * @param array $params
     */
    public function plugin_docman_event_del($params) {
        $this->createSystemEvent('FULLTEXTSEARCH_DOCMAN_DELETE', SystemEvent::PRIORITY_HIGH, $params['item']);
    }

    /**
     * Event triggered when the permissions on a document change
     */
    public function plugin_docman_event_perms_change($params) {
        $this->createSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS', SystemEvent::PRIORITY_HIGH, $params['item']);
    }

    /**
     * Event triggered when the permissions on a document version update
     */
    public function plugin_docman_event_new_version($params) {
        // will be done in plugin_docman_after_new_document since we
        // receive both event for a new document
        if ($params['version']->getNumber() > 1) {
            $this->createSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE', SystemEvent::PRIORITY_MEDIUM, $params['item'], $params['version']->getNumber());
        }
    }

    private function createSystemEvent($type, $priority, Docman_Item $item, $additional_params = '') {
        if ($this->isAllowed($item->getGroupId())) {

            $params = $item->getGroupId() . SystemEvent::PARAMETER_SEPARATOR . $item->getId();
            if ($additional_params) {
                $params .= SystemEvent::PARAMETER_SEPARATOR . $additional_params;
            }
            SystemEventManager::instance()->createEvent($type, $params, $priority);
        }
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
        if ($this->canIncludeAssets()) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    function combined_scripts($params) {
        $params['scripts'] = array_merge(
            $params['scripts'],
            array(
                $this->getPluginPath().'/script.js',
            )
        );
    }

    private function canIncludeAssets() {
        return strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/search/') === 0;
    }

    private function getIndexClient() {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildIndexClient($client_path, $server_host, $server_port, $server_user, $server_password);
    }

    private function getSearchClient() {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildSearchClient($client_path, $server_host, $server_port, $server_user, $server_password, $this->getProjectManager());
    }

    private function getSearchAdminClient() {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildSearchAdminClient($client_path, $server_host, $server_port, $server_user, $server_password, $this->getProjectManager());
    }

    private function getClientFactory() {
        return new ElasticSearch_ClientFactory();
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'FulltextsearchPluginInfo')) {
            $this->pluginInfo = new FulltextsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    private function getSearchController() {
        return new FullTextSearch_Controller_Search($this->getRequest(), $this->getSearchClient());
    }

    private function getAdminController() {
        return new FullTextSearch_Controller_Admin($this->getRequest(), $this->getSearchAdminClient());
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    private function getRequest() {
        return HTTPRequest::instance();
    }

    public function process() {
        $request = $this->getRequest();
        // Grant access only to site admin
        if (!$request->getCurrentUser()->isSuperUser()) {
            header('Location: ' . get_server_url());
        }

        $controller = $this->getAdminController();
        if ($request->get('words')) {
                $controller->search();
        } else {
                $controller->index();
        }
    }
}

?>
