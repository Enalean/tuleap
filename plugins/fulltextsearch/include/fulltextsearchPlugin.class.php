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
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->allowed_for_project = array();

        // docman
        $this->_addHook('plugin_docman_after_new_document', 'plugin_docman_after_new_document', false);
        $this->_addHook('plugin_docman_event_del', 'plugin_docman_event_del', false);
        $this->_addHook('plugin_docman_event_update', 'plugin_docman_event_update', false);
        $this->_addHook('plugin_docman_event_perms_change', 'plugin_docman_event_perms_change', false);

        // site admin
        $this->_addHook('site_admin_option_hook',   'site_admin_option_hook', false);

        // style
        $this->_addHook('cssfile', 'cssfile', false);

        // system events
        $this->_addHook(Event::GET_SYSTEM_EVENT_CLASS, 'get_system_event_class', false);
        $this->_addHook(Event::SYSTEM_EVENT_GET_TYPES, 'system_event_get_types', false);
        $this->_addHook(Event::SYSTEM_EVENT_INSTANCIATED, 'system_event_instanciated', false);
    }

    public function system_event_instanciated($params) {
        switch ($params['sysevent']->getType()) {
        case 'FULLTEXTSEARCH_DOCMAN_INDEX':
        case 'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS':
        case 'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA':
        case 'FULLTEXTSEARCH_DOCMAN_DELETE':
            $params['sysevent']
                ->setFullTextSearchActions($this->getActions())
                ->setItemFactory(new Docman_ItemFactory())
                ->setVersionFactory(new Docman_VersionFactory());
            break;
        }
    }

    public function system_event_get_types($params) {
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_INDEX';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA';
        $params['types'][] = 'FULLTEXTSEARCH_DOCMAN_DELETE';
    }

    /**
     *This callback make SystemEvent manager knows about git plugin System Events
     * @param <type> $params
     */
    public function get_system_event_class($params) {
        switch($params['type']) {
            case 'FULLTEXTSEARCH_DOCMAN_INDEX':
                require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX.class.php';
                $params['class'] = 'SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX';
                break;
            case 'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS':
                require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS.class.php';
                $params['class'] = 'SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS';
                break;
            case 'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA':
                require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA.class.php';
                $params['class'] = 'SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA';
                break;
            case 'FULLTEXTSEARCH_DOCMAN_DELETE':
                require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE.class.php';
                $params['class'] = 'SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE';
                break;
            default:
                break;
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
        if ($this->isAllowed($params['item']->getGroupId())) {
            require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA.class.php';
            SystemEventManager::instance()->createEvent(
                'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA',
                $params['item']->getGroupId().SystemEvent::PARAMETER_SEPARATOR.
                    $params['item']->getId(),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    /**
     * Event triggered when a document is created
     *
     * @param array $params
     */
    public function plugin_docman_after_new_document($params) {
        if ($this->isAllowed($params['item']->getGroupId())) {
            require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX.class.php';
            SystemEventManager::instance()->createEvent(
                'FULLTEXTSEARCH_DOCMAN_INDEX',
                $params['item']->getGroupId().SystemEvent::PARAMETER_SEPARATOR.
                    $params['item']->getId().SystemEvent::PARAMETER_SEPARATOR.
                    $params['version']->getNumber(),
                SystemEvent::PRIORITY_MEDIUM
            );
        }
    }

    /**
     * Event triggered when a document is deleted
     *
     * @param array $params
     */
    public function plugin_docman_event_del($params) {
        if ($this->isAllowed($params['item']->getGroupId())) {
            require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE.class.php';
            SystemEventManager::instance()->createEvent(
                'FULLTEXTSEARCH_DOCMAN_DELETE',
                $params['item']->getGroupId().SystemEvent::PARAMETER_SEPARATOR.
                    $params['item']->getId(),
                SystemEvent::PRIORITY_HIGH
            );
        }
    }

    /**
     * Event triggered when the permissions on a document change
     */
    public function plugin_docman_event_perms_change($params) {
        if ($this->isAllowed($params['item']->getGroupId())) {
            require_once 'SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS.class.php';
            SystemEventManager::instance()->createEvent(
                'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS',
                $params['item']->getGroupId().SystemEvent::PARAMETER_SEPARATOR.
                    $params['item']->getId(),
                SystemEvent::PRIORITY_HIGH
            );
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
        // Only show the stylesheet if we're actually in the FullTextSearch pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    private function getIndexClient() {
        $factory     = $this->getClientFactory();
        $client_path = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        return $factory->buildIndexClient($client_path, $server_host, $server_port);
    }

    private function getSearchClient() {
        $factory     = $this->getClientFactory();
        $client_path = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        return $factory->buildSearchClient($client_path, $server_host, $server_port, ProjectManager::instance());
    }

    private function getClientFactory() {
        require_once 'ElasticSearch/ClientFactory.class.php';
        return new ElasticSearch_ClientFactory();
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

        include_once 'FullTextSearch/Controller/Search.class.php';

        $request    = HTTPRequest::instance();
        $controller = new FullTextSearch_Controller_Search($request, $this->getSearchClient());
        switch ($request->get('func')) {
            case 'search':
                $controller->search();
                break;
            default:
                $controller->index();
        }
    }
}

?>
