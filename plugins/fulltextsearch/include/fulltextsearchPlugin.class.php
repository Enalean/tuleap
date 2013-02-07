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

    const SEARCH_TYPE         = 'fulltext';
    const SEARCH_DOCMAN_TYPE  = 'docman';
    const SEARCH_TRACKER_TYPE = 'tracker';

    private $actions;
    private $trackerActions;

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

        // tracker
        $this->_addHook('tracker_report_followup_search', 'tracker_report_followup_search', false);
        $this->_addHook('tracker_report_followup_search_process', 'tracker_report_followup_search_process', false);
        $this->_addHook('tracker_followup_event_add', 'tracker_followup_event_add', false);
        $this->_addHook('tracker_followup_event_update', 'tracker_followup_event_update', false);
        $this->_addHook('tracker_report_followup_warning', 'tracker_report_followup_warning', false);

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
        $params['types'][] = 'FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD';
        $params['types'][] = 'FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE';
    }

    /**
     * This callback make SystemEvent manager knows about fulltext plugin System Events
     */
    public function get_system_event_class($params) {
        if (strpos($params['type'], 'FULLTEXTSEARCH_DOCMAN') !== false) {
            $params['class']        = 'SystemEvent_'. $params['type'];
            $params['dependencies'] = array($this->getActions(), new Docman_ItemFactory(), new Docman_VersionFactory());
        }
        if (strpos($params['type'], 'FULLTEXTSEARCH_TRACKER') !== false) {
            $params['class']        = 'SystemEvent_'. $params['type'];
            $params['dependencies'] = array($this->getTrackerActions());
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
        $type = 'docman';
        if (!isset($this->actions) && ($search_client = $this->getIndexClient($type))) {
            $this->actions = new FullTextSearchDocmanActions($search_client, new Docman_PermissionsItemManager());
        }
        return $this->actions;
    }

    /**
     * Get tracker actions
     *
     * @return FullTextSearchTrackerActions
     */
    private function getTrackerActions() {
        $type = self::SEARCH_TRACKER_TYPE;
        if (!isset($this->trackerActions) && ($search_client = $this->getIndexClient($type))) {
            $this->trackerActions = new FullTextSearchTrackerActions($search_client);
        }
        return $this->trackerActions;
    }

    /**
     * Event triggered when a document is updated
     *
     * @param array $params
     */
    public function plugin_docman_event_update($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA', SystemEvent::PRIORITY_MEDIUM, $params['item']);
    }

    /**
     * Event triggered when a document is created
     *
     * @param array $params
     */
    public function plugin_docman_after_new_document($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_INDEX', SystemEvent::PRIORITY_MEDIUM, $params['item'], $params['version']->getNumber());
    }

    /**
     * Event triggered when a document is deleted
     *
     * @param array $params
     */
    public function plugin_docman_event_del($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_DELETE', SystemEvent::PRIORITY_HIGH, $params['item']);
    }

    /**
     * Event triggered when the permissions on a document change
     */
    public function plugin_docman_event_perms_change($params) {
        $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS', SystemEvent::PRIORITY_HIGH, $params['item']);
    }

    /**
     * Event triggered when the permissions on a document version update
     */
    public function plugin_docman_event_new_version($params) {
        // will be done in plugin_docman_after_new_document since we
        // receive both event for a new document
        if ($params['version']->getNumber() > 1) {
            $this->createDocmanSystemEvent('FULLTEXTSEARCH_DOCMAN_UPDATE', SystemEvent::PRIORITY_MEDIUM, $params['item'], $params['version']->getNumber());
        }
    }

    /**
     * Display search form in tracker followup
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_report_followup_search($params) {
        if ($this->tracker_followup_check_preconditions($params['group_id'])) {
            $filter = '';
            $hp     = Codendi_HTMLPurifier::instance();
            $filter = $hp->purify($params['request']->getValidated('search_followups', 'string', ''));
            $params['html'] .='<span class ="lab_features">';
            $params['html'] .= '<label title="'.$GLOBALS['Language']->getText('plugin_fulltextsearch', 'search_followup_comments').'" for="tracker_report_crit_followup_search">';
            $params['html'] .= $GLOBALS['Language']->getText('plugin_fulltextsearch', 'followups_search');
            $params['html'] .= '</label><br>';
            $params['html'] .= '<input id="tracker_report_crit_followup_search" type="text" name="search_followups" value="'.$filter.'" />';
            $params['html'] .= '</span>';
        }
    }

    /**
     * Process warning display for tracker followup search
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_report_followup_warning($params) {
        if ($this->tracker_followup_check_preconditions($params['group_id'])) {
            if ($params['request']->get('search_followups')) {
                $params['html'] .= '<div id="tracker_report_selection" class="tracker_report_haschanged_and_isobsolete" style="z-index: 2;position: relative;">';
                $params['html'] .= $GLOBALS['HTML']->getimage('ic/warning.png', array('style' => 'vertical-align:top;'));
                $params['html'] .= $GLOBALS['Language']->getText('plugin_fulltextsearch', 'followup_full_text_warning_search');
                $params['html'] .= '</div>';
            }
        }
    }

    /**
     * This method check preconditions to use fulltext:
     * Elastic Search is available, the plugin is enabled for this project and the user is enabling mode Lab.
     *
     * @param Integer $group_id Project Id
     *
     * @return Boolean
     */
    private function tracker_followup_check_preconditions($group_id) {
        try {
            $index_status = $this->getAdminController()->getIndexStatus();
        } catch (ElasticSearchTransportHTTPException $e) {
            return false;
        }
        return $this->isAllowed($group_id) && $this->getCurrentUser()->useLabFeatures();
    }

    /**
     * Process search in tracker followup
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_report_followup_search_process($params) {
        if ($this->tracker_followup_check_preconditions($params['group_id'])) {
            $filter = $params['request']->get('search_followups');
            if (!empty($filter)) {
                $controller       = $this->getSearchController('tracker');
                $params['result'] = $controller->search($params['request']);
                $params['search_performed'] = true;
            }
        }
    }

    /**
     * Index added followup comment
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_followup_event_add($params) {
        $this->createTrackerSystemEvent('FULLTEXTSEARCH_TRACKER_FOLLOWUP_ADD', SystemEvent::PRIORITY_MEDIUM, $params);
    }

    /**
     * Index updated followup comment
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_followup_event_update($params) {
        $this->createTrackerSystemEvent('FULLTEXTSEARCH_TRACKER_FOLLOWUP_UPDATE', SystemEvent::PRIORITY_MEDIUM, $params);
    }

    /**
     * Create system event for docman indexing operations
     *
     * @param String      $type              Type of the event
     * @param String      $priority          Priority of the system event
     * @param Docman_Item $item              Docman item
     * @param Mixed       $additional_params Extra params
     *
     * @return Void
     */
    private function createDocmanSystemEvent($type, $priority, Docman_Item $item, $additional_params = '') {
        if ($this->isAllowed($item->getGroupId())) {

            $params = $item->getGroupId() . SystemEvent::PARAMETER_SEPARATOR . $item->getId();
            if ($additional_params) {
                $params .= SystemEvent::PARAMETER_SEPARATOR . $additional_params;
            }
            SystemEventManager::instance()->createEvent($type, $params, $priority);
        }
    }

    /**
     * Create system event for docman indexing operations
     *
     * @param String $type     Type of the event
     * @param String $priority Priority of the system event
     * @param Array  $params   Event params
     *
     * @return Void
     */
    private function createTrackerSystemEvent($type, $priority, $params) {
        if ($this->isAllowed($params['group_id'])) {
            $params = $params['group_id'].SystemEvent::PARAMETER_SEPARATOR.$params['artifact_id'].SystemEvent::PARAMETER_SEPARATOR.$params['changeset_id'].SystemEvent::PARAMETER_SEPARATOR.$params['text'];
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

    /**
     * Obtain index client
     *
     * @param String $type Type of the index
     *
     * @return ElasticSearch_IndexClientFacade
     */
    private function getIndexClient($type) {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildIndexClient($client_path, $server_host, $server_port, $server_user, $server_password, $type);
    }

    /**
     * Obtain search client
     *
     * @param String $type Type of the index
     *
     * @return ElasticSearch_SearchClientFacade
     */
    private function getSearchClient($type) {
        $factory         = $this->getClientFactory();
        $client_path     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path');
        $server_host     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host');
        $server_port     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port');
        $server_user     = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user');
        $server_password = $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password');
        return $factory->buildSearchClient($client_path, $server_host, $server_port, $server_user, $server_password, $this->getProjectManager(), $type);
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

    private function getSearchController($type = self::SEARCH_DOCMAN_TYPE) {
        return new FullTextSearch_Controller_Search($this->getRequest(), $this->getSearchClient($type));
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