<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerWikiAdmin;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;

require_once 'common/plugin/Plugin.class.php';
require_once 'autoload.php';
require_once 'constants.php';

class fulltextsearchPlugin extends Plugin {

    const SEARCH_TYPE         = 'fulltext';
    const SEARCH_DOCMAN_TYPE  = 'docman';
    const SEARCH_WIKI_TYPE    = 'wiki';
    const SEARCH_TRACKER_TYPE = 'tracker';
    const SEARCH_DEFAULT      = '';

    private $allowed_system_events = array(
        SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_EMPTY_INDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_LINK_INDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_FOLDER_INDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_REINDEX_PROJECT::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_COPY::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_DELETE::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_INDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_UPDATE::NAME,
        SystemEvent_FULLTEXTSEARCH_DOCMAN_UPDATELINK::NAME,
        SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE::NAME,
        SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX_PROJECT::NAME,
        SystemEvent_FULLTEXTSEARCH_TRACKER_ARTIFACT_DELETE::NAME,
        SystemEvent_FULLTEXTSEARCH_TRACKER_TRACKER_DELETE::NAME,
        SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_WIKI_INDEX::NAME,
        SystemEvent_FULLTEXTSEARCH_WIKI_UPDATE::NAME,
        SystemEvent_FULLTEXTSEARCH_WIKI_UPDATE_PERMISSIONS::NAME,
        SystemEvent_FULLTEXTSEARCH_WIKI_UPDATE_SERVICE_PERMISSIONS::NAME,
        SystemEvent_FULLTEXTSEARCH_WIKI_DELETE::NAME,
        SystemEvent_FULLTEXTSEARCH_WIKI_REINDEX_PROJECT::NAME,
    );

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        $this->allowed_for_project = array();
    }

    public function getHooksAndCallbacks() {
        // docman
        if ($this->isDocmanPluginActivated()) {
            $this->addHook('plugin_docman_after_new_document');
            $this->addHook('plugin_docman_event_del');
            $this->addHook('plugin_docman_event_update');
            $this->addHook('plugin_docman_event_perms_change');
            $this->addHook('plugin_docman_event_new_version');
            $this->addHook('plugin_docman_event_new_wikipage');
            $this->addHook('plugin_docman_event_wikipage_update');
            $this->addHook('plugin_docman_event_move');
            $this->addHook(PLUGIN_DOCMAN_EVENT_COPY);
            $this->addHook(PLUGIN_DOCMAN_EVENT_APPROVAL_TABLE_COMMENT);
            $this->addHook(PLUGIN_DOCMAN_EVENT_NEW_EMPTY);
            $this->addHook(PLUGIN_DOCMAN_EVENT_NEW_LINK);
            $this->addHook(PLUGIN_DOCMAN_EVENT_NEW_FOLDER);
            $this->addHook(PLUGIN_DOCMAN_EVENT_NEW_LINKVERSION);
        }
        // tracker
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TRACKER_EVENT_REPORT_DISPLAY_ADDITIONAL_CRITERIA);
            $this->addHook(TRACKER_EVENT_REPORT_PROCESS_ADDITIONAL_QUERY);
            $this->addHook(TRACKER_EVENT_ARTIFACT_POST_UPDATE);
            $this->addHook(TRACKER_EVENT_ARTIFACT_DELETE);
            $this->addHook(TRACKER_EVENT_TRACKER_DELETE);
            $this->addHook(TRACKER_EVENT_TRACKER_PERMISSIONS_CHANGE);
            $this->addHook(TRACKER_EVENT_SEMANTIC_CONTRIBUTOR_CHANGE);
            $this->addHook('tracker_followup_event_update', 'tracker_event_artifact_post_update', false);
            $this->addHook('tracker_report_followup_warning', 'tracker_report_followup_warning', false);
            $this->addHook(ArtifactCreated::NAME);
        }

        // site admin
        $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
        $this->addHook('project_is_private', 'project_is_private', false);

        // wiki
        $this->addHook('wiki_page_updated', 'wiki_page_updated', false);
        $this->addHook('wiki_page_created', 'wiki_page_created', false);
        $this->addHook('wiki_page_deleted', 'wiki_page_deleted', false);
        $this->addHook('wiki_page_permissions_updated', 'wiki_page_permissions_updated', false);
        $this->addHook('wiki_service_permissions_updated', 'wiki_service_permissions_updated', false);

        // assets
        $this->addHook('cssfile', 'cssfile', false);
        $this->addHook('javascript_file');

        // system events
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'get_system_event_class', false);
        $this->addHook(Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE);

        // Search
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::PLUGINS_POWERED_SEARCH);
        $this->addHook(Event::SEARCH_TYPE, 'search_type');
        $this->addHook(Event::FETCH_ADDITIONAL_SEARCH_TABS);
        $this->addHook(Event::REDEFINE_SEARCH_TYPE);

        //Ugroups
        $this->addHook('project_admin_ugroup_remove_user', 'project_admin_ugroup_delete_or_remove_user');
        $this->addHook('project_admin_ugroup_deletion', 'project_admin_ugroup_delete_or_remove_user');
        $this->addHook(Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_ADD, 'ugroup_manager_update_ugroup_binding_change');
        $this->addHook(Event::UGROUP_MANAGER_UPDATE_UGROUP_BINDING_REMOVE, 'ugroup_manager_update_ugroup_binding_change');
        $this->addHook('project_admin_remove_user_from_project_ugroups', 'eventChangeProjectUgroupsMembers');
        $this->addHook('project_admin_ugroup_add_user', 'eventChangeProjectUgroupsMembers');

        $this->addHook('project_admin_remove_user', 'project_admin_remove_user');

        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(UserIsNoLongerProjectAdmin::NAME);
        $this->addHook(UserIsNoLongerWikiAdmin::NAME);

        return parent::getHooksAndCallbacks();
    }

    private function isDocmanPluginActivated() {
        return defined('PLUGIN_DOCMAN_EVENT_APPROVAL_TABLE_COMMENT');
    }

    private function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/fulltextsearch') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/fulltextsearch') === 0) {
            $params['javascript_files'][] = $this->getPluginPath() . '/scripts/admin-load-project-autocompleter.js';
        }
    }

    public function layout_search_entry($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            $params['search_entries'][] = array(
                'value'    => self::SEARCH_TYPE,
                'label'    => 'Fulltext',
                'selected' => $params['type_of_search'] == self::SEARCH_TYPE,
            );
        }
    }

    public function plugins_powered_search($params) {
        if ($this->getCurrentUser()->useLabFeatures() && $params['type_of_search'] === self::SEARCH_TYPE) {
            $params['plugins_powered_search'] = true;
        }
    }

    public function fetch_additional_search_tabs($params) {
        if ($this->getCurrentUser()->useLabFeatures()) {
            $params['additional_search_tabs'][] = new Search_AdditionalSearchTabsPresenter(
                '<i class="fa fa-flask"></i> ' . $GLOBALS['Language']->getText('plugin_fulltextsearch', 'additional_search_tab'),
                '/search?type_of_search=' . self::SEARCH_TYPE,
                self::SEARCH_TYPE
            );
        }
    }

    public function search_type($params) {
        $type_of_search = $params['query']->getTypeOfSearch();

        if ($type_of_search !== self::SEARCH_TYPE) {
            return;
        }

        try {
            $search_controller = $this->getSearchController();
        } catch (ElasticSearch_ClientNotFoundException $exception) {
            $search_error_controller = $this->getSearchErrorController();
            $search_error_controller->clientNotFound($params);
            return;
        }

        $search_controller->siteSearch($params);
    }

    private function getDocmanSystemEventManager() {
        return new FullTextSearch_DocmanSystemEventManager(
            SystemEventManager::instance(),
            $this->getIndexClient(self::SEARCH_DOCMAN_TYPE),
            $this,
            $this->getFullTextSearchSystemEventQueue()->getLogger()
        );
    }

    private function getWikiSystemEventManager() {
        return new FullTextSearch_WikiSystemEventManager(
            SystemEventManager::instance(),
            $this->getIndexClient(self::SEARCH_WIKI_TYPE),
            $this,
            $this->getFullTextSearchSystemEventQueue()->getLogger()
        );
    }

    private function getTrackerSystemEventManager() {
        $form_element_factory   = Tracker_FormElementFactory::instance();
        $permissions_serializer = new Tracker_Permission_PermissionsSerializer(
            new Tracker_Permission_PermissionRetrieveAssignee(UserManager::instance())
        );

        $artifact_properties_extractor = new ElasticSearch_1_2_ArtifactPropertiesExtractor(
            $form_element_factory,
            $permissions_serializer
        );

        return new FullTextSearch_TrackerSystemEventManager(
            SystemEventManager::instance(),
            new FullTextSearchTrackerActions(
                $this->getIndexClient(self::SEARCH_TRACKER_TYPE),
                new ElasticSearch_1_2_RequestTrackerDataFactory(
                    $artifact_properties_extractor
                ),
                $this->getFullTextSearchSystemEventQueue()->getLogger()
            ),
            Tracker_ArtifactFactory::instance(),
            TrackerFactory::instance(),
            $this,
            $this->getFullTextSearchSystemEventQueue()->getLogger()
        );
    }

    /** @see Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES */
    public function system_event_get_custom_queues(array $params) {
        $params['queues'][FullTextSearchSystemEventQueue::NAME] = $this->getFullTextSearchSystemEventQueue();
    }

    private function getFullTextSearchSystemEventQueue() {
        return new FullTextSearchSystemEventQueue();
    }

    /** @see Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE */
    public function system_event_get_types_for_custom_queue(array &$params) {
        if ($params['queue'] !== FullTextSearchSystemEventQueue::NAME) {
            return;
        }

        $params['types'] = array_merge(
            $params['types'],
            $this->allowed_system_events
        );
    }

    /**
     * This callback make SystemEvent manager knows about fulltext plugin System Events
     */
    public function get_system_event_class($params) {
        $providers = array(
            $this->getDocmanSystemEventManager(),
            $this->getWikiSystemEventManager(),
            $this->getTrackerSystemEventManager(),
        );
        $i = 0;

        if (! in_array($params['type'], $this->allowed_system_events)) {
            return;
        }

        do {
            $providers[$i]->getSystemEventClass(
                $params['type'],
                $params['class'],
                $params['dependencies']
            );
            $i++;
        } while ($i < count($providers) || !$params['class']);
    }

    /**
     * Return true if given project has the right to use this plugin.
     *
     * @param string $group_id
     *
     * @return bool
     */
    public function isAllowed($group_id) {
        if(! isset($this->allowedForProject[$group_id])) {
            $this->allowed_for_project[$group_id] = PluginManager::instance()->isPluginAllowedForProject($this, $group_id);
        }
        return $this->allowed_for_project[$group_id];
    }

    public function wiki_page_updated($params) {
        if (! $this->isDocmanPluginActivated() || ! $params['referenced']) {
            $this->getWikiSystemEventManager()->queueUpdateWikiPage($params);
        }
    }

    public function wiki_page_created($params) {
        $this->getWikiSystemEventManager()->queueIndexWikiPage($params);
    }

    public function wiki_page_deleted($params) {
        $this->getWikiSystemEventManager()->queueDeleteWikiPage($params);
    }

    public function wiki_page_permissions_updated($params) {
        $this->getWikiSystemEventManager()->queueUpdateWikiPagePermissions($params);
    }

    public function wiki_service_permissions_updated($params) {
        $this->getWikiSystemEventManager()->queueUpdateWikiServicePermissions($params);
    }

    /**
     * Event triggered when a document is created
     *
     * @param array $params
     */
    public function plugin_docman_after_new_document($params) {
        $this->getDocmanSystemEventManager()->queueNewDocument($params['item'], $params['version']);
    }

    /**
     * Event triggered when a new empty document is created
     *
     * @param array $params
     */
    public function plugin_docman_event_new_empty($params) {
        $this->getDocmanSystemEventManager()->queueNewEmptyDocument($params['item']);
    }

    /**
     * Event triggered when a new link document is created
     *
     * @param array $params
     */
    public function plugin_docman_event_new_link($params) {
        $this->getDocmanSystemEventManager()->queueNewLinkDocument($params['item']);
    }

    /**
     * Event triggered when a new folder is created
     *
     * @param array $params
     */
    public function plugin_docman_event_new_folder($params) {
        $this->getDocmanSystemEventManager()->queueNewDocmanFolder($params['item']);
    }

    /**
     * Event triggered when a document is updated
     *
     * @param array $params
     */
    public function plugin_docman_event_update($params) {
        $this->getDocmanSystemEventManager()->queueUpdateMetadata($params['item']);
    }

    /**
     * Event triggered when a document is moved
     *
     * @param array $params
     */
    public function plugin_docman_event_move($params) {
        $this->getDocmanSystemEventManager()->queueUpdateDocumentPermissions($params['item']);
    }

    /**
     * Event triggered when a document is copied
     *
     * @param array $params
     */
    public function plugin_docman_event_copy($params) {
        $this->getDocmanSystemEventManager()->queueCopyDocument($params['item']);
    }

    /**
     * Event triggered when a document is deleted
     *
     * @param array $params
     */
    public function plugin_docman_event_del($params) {
        $this->getDocmanSystemEventManager()->queueDeleteDocument($params['item']);
    }

    /**
     * Event triggered when the permissions on a document change
     */
    public function plugin_docman_event_perms_change($params) {
        $this->getDocmanSystemEventManager()->queueUpdateDocumentPermissions($params['item']);
    }

    /**
     * Event triggered when the permissions on a document version update
     */
    public function plugin_docman_event_new_version($params) {
        $this->getDocmanSystemEventManager()->queueNewDocumentVersion($params['item'], $params['version']);
    }

    /**
     * Event triggered when the permissions on a document version update
     */
    public function plugin_docman_event_new_linkVersion($params) {
        $this->getDocmanSystemEventManager()->queueNewDocumentLinkVersion($params['item'], $params['version']);
    }

    /**
     * Event triggered when a wiki document is updated
     */
    public function plugin_docman_event_new_wikipage($params) {
        $this->getWikiSystemEventManager()->queueDeleteWikiPage(array(
            'group_id'  => $params['group_id'],
            'wiki_page' => $params['wiki_page']
        ));

        $this->getDocmanSystemEventManager()->queueNewWikiDocument($params['item']);
    }

    /**
     * Event triggered when a wiki document is updated
     */
    public function plugin_docman_event_wikipage_update($params) {
        $this->getDocmanSystemEventManager()->queueNewWikiDocumentVersion($params['item']);
    }

    /**
     * @see PLUGIN_DOCMAN_EVENT_APPROVAL_TABLE_COMMENT
     * @param array $params
     */
    public function plugin_docman_approval_table_comment(array $params) {
        $this->getDocmanSystemEventManager()->queueNewApprovalTableComment($params['item']);
    }

    /**
     * Index added followup comment
     *
     * @param Array $params Hook params
     * @see TRACKER_EVENT_ARTIFACT_POST_UPDATE
     * @return Void
     */
    public function tracker_event_artifact_post_update($params) {
        $this->getTrackerSystemEventManager()->queueArtifactUpdate($params['artifact']);
    }

    public function tracker_event_artifact_delete($params) {
        $this->getTrackerSystemEventManager()->queueArtifactDelete($params['artifact']);
    }

    public function tracker_event_tracker_delete($params) {
        $this->getTrackerSystemEventManager()->queueTrackerDelete($params['tracker']);
    }

    /**
     * Display search form in tracker followup
     *
     * @param Array $params Hook params
     *
     * @return Void
     */
    public function tracker_event_report_display_additional_criteria($params) {
        if (! $params['tracker']) {
            return;
        }

        if ($this->tracker_followup_check_preconditions($params['tracker']->getGroupId())) {
            $request = HTTPRequest::instance();
            $hp      = Codendi_HTMLPurifier::instance();
            $filter  = $hp->purify($request->getValidated('search_fulltext', 'string', ''));

            $additional_criteria  = '';
            $additional_criteria .= '<label title="'.$GLOBALS['Language']->getText('plugin_fulltextsearch', 'global_search_tooltip').'" for="tracker_report_crit_fulltext_search">';
            $additional_criteria .= '<i class="fa fa-flask"></i> '. $GLOBALS['Language']->getText('plugin_fulltextsearch', 'global_search_label');
            $additional_criteria .= '</label>';
            $additional_criteria .= '<input id="tracker_report_crit_fulltext_search" type="text" name="search_fulltext" value="'.$filter.'" />';

            $params['array_of_html_criteria'][] = $additional_criteria;
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
            if ($params['request']->get('search_fulltext')) {
                $params['html'] .= '<div id="tracker_report_selection" class="tracker_report_haschanged_and_isobsolete report_warning">';
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
        } catch (ElasticSearch_ClientNotFoundException $exception) {
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
    public function tracker_event_report_process_additional_query($params) {
        if (! $params['request'] || ! $params['tracker'] || ! $params['user'] || ! $params['form_element_factory']) {
            return;
        }

        $group_id = $params['tracker']->getGroupId();
        if (! $this->tracker_followup_check_preconditions($group_id)) {
            return;
        }

        $filter = $params['request']->get('search_fulltext');
        if (empty($filter)) {
            return;
        }

        $field_names = array();
        $formElementFactory = $params['form_element_factory'];
        $fields = $formElementFactory->getUsedTextFieldsUserCanRead($params['tracker'], $params['user']);
        foreach ($fields as $field) {
            $field_names[] = $field->getName();
        }

        $field_names[] = ElasticSearch_1_2_RequestTrackerDataFactory::COMMENT_FIELD_NAME;

        try {
            $controller         = $this->getSearchController('tracker');
            $params['result'][] = $controller->searchSpecial($field_names, $params['request']);
            $params['search_performed'] = true;
        } catch (ElasticSearch_ClientNotFoundException $e) {
            // do nothing
        }
    }

    /**
     * Event to display something in siteadmin interface
     *
     * @param array $params
     */
    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => 'Full Text Search',
            'href'  => $this->getPluginPath() . '/'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
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

    public function javascript_file() {
        if ($this->isAdminPage()) {
            echo '<script type="text/javascript" src="' . $this->getPluginPath() . '/scripts/admin-load-project-autocompleter.js"></script>'."\n";
        }
        if ($this->canIncludeAssets()) {
            echo '<script type="text/javascript" src="' . $this->getPluginPath() . '/scripts/full-text-search.js"></script>'."\n";
        }
    }

    private function isAdminPage() {
        return strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0;
    }

    private function canIncludeAssets() {
        return strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/search/') === 0;
    }

    /**
     * Obtain index client
     *
     * @param String $index Type of the index
     *
     * @return ElasticSearch_IndexClientFacade
     */
    private function getIndexClient($index) {
        $factory = $this->getClientFactory();
        $type    = '';

        return $factory->buildIndexClient($index, $type);
    }

    /**
     * Obtain search client
     *
     * @param String $index Type of items to search (e.g. docman, wiki, '', ...)
     *
     * @return ElasticSearch_SearchClientFacade
     */
    private function getSearchClient($index, $type = '') {
        $factory = $this->getClientFactory();

        return $factory->buildSearchClient($index, $type);
    }

    private function getSearchAdminClient() {
        $factory = $this->getClientFactory();

        return $factory->buildSearchAdminClient();
    }

    private function getClientFactory() {
        return new ElasticSearch_ClientFactory(
            $this->getElasticSearchConfig(),
            $this->getProjectManager()
        );
    }

    private function getElasticSearchConfig() {
        return new ElasticSearch_ClientConfig(
            $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_path'),
            $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_host'),
            $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_port'),
            $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_user'),
            $this->getPluginInfo()->getPropertyValueForName('fulltextsearch_password'),
            $this->getPluginInfo()->getPropertyValueForName('max_seconds_for_request')
        );
    }

    /**
     * @return FulltextsearchPluginInfo
     */
    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'FulltextsearchPluginInfo')) {
            $this->pluginInfo = new FulltextsearchPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * @throws ElasticSearch_ClientNotFoundException
     */
    private function getSearchController($index = self::SEARCH_DEFAULT, $type = '') {
        return new FullTextSearch_Controller_Search($this->getRequest(), $this->getSearchClient($index, $type));
    }

    private function getSearchErrorController() {
        return new FullTextSearch_Controller_SearchError($this->getRequest());
    }

    private function getAdminController()
    {
        return new FullTextSearch_Controller_Admin(
            $this->getRequest(),
            $this->getSearchAdminClient(),
            $this->getDocmanSystemEventManager(),
            $this->getWikiSystemEventManager(),
            $this->getTrackerSystemEventManager(),
            $this->getAdminPageRenderer()
        );
    }

    private function getAdminPageRenderer()
    {
        return new AdminPageRenderer();
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

        $project_name_from_autocomplete = $request->get('fts_project');
        $project_manager = $this->getProjectManager();
        $project = $project_manager->getProjectFromAutocompleter($project_name_from_autocomplete);

        if ($project) {
            $this->reindexAll($project->getId());

            $GLOBALS['Response']->addFeedback(
                'info',
                $GLOBALS['Language']->getText(
                    'plugin_fulltextsearch',
                    'waiting_for_reindexation',
                    array(util_unconvert_htmlspecialchars($project->getPublicName()))
                )
            );
        }

        $this->redirectToIndex();
    }

    private function redirectToIndex() {
        $this->getAdminController()->index();
    }

    public function project_is_private($params) {
        $project_id = $params['group_id'];

        $this->reindexAll($project_id);
    }

    /** @see TRACKER_EVENT_TRACKER_PERMISSIONS_CHANGE */
    public function tracker_event_tracker_permisssions_change($params) {
        $this->reindexTracker($params);
    }

    /** @see TRACKER_EVENT_SEMANTIC_CONTRIBUTOR_CHANGE */
    public function tracker_event_semantic_contributor_change($params) {
        $this->reindexTracker($params);
    }

    private function reindexTracker($params) {
        $tracker    = $params['tracker'];
        $controller = $this->getAdminController();

        $controller->reindexTracker($tracker);
    }

    private function reindexAll($project_id) {
        $controller = $this->getAdminController();

        $controller->reindexDocman($project_id);
        $controller->reindexWiki($project_id);
        $controller->reindexTrackers($project_id);
    }

    public function project_admin_ugroup_delete_or_remove_user($params) {
        $ugroup_id  = $params['ugroup_id'];
        $project_id = $params['group_id'];

        $this->reindexForServicesUsingUgroup($ugroup_id, $project_id);
    }

    public function ugroup_manager_update_ugroup_binding_change($params) {
        /*@var $ugroup ProjectUGroup  */
        $ugroup     = $params['ugroup'];
        $project_id = $ugroup->getProjectId();

        $this->reindexForServicesUsingUgroup($ugroup->getId(), $project_id);
    }

    public function userIsNoLongerProjectAdmin(UserIsNoLongerProjectAdmin $event)
    {
        $this->reindexForServicesUsingUgroup(ProjectUGroup::PROJECT_ADMIN, $event->getProject()->getID());
    }

    public function userIsNoLongerWikiAdmin(UserIsNoLongerWikiAdmin $event)
    {
        $this->reindexForServicesUsingUgroup(ProjectUGroup::WIKI_ADMIN, $event->getProject()->getID());
    }

    public function project_admin_remove_user($params) {
        $ugroup_id  = ProjectUGroup::PROJECT_MEMBERS;
        $project_id = $params['group_id'];

        $this->reindexForServicesUsingUgroup($ugroup_id, $project_id);
    }

    /**
     * Reindex everything if user is removed from project (too many edge cases to check)
     */
    public function eventChangeProjectUgroupsMembers($params) {
        $project_id = $params['group_id'];

        $this->getTrackerSystemEventManager()->queueTrackersProjectReindexation($project_id);
        $this->getDocmanSystemEventManager()->queueDocmanProjectReindexation($project_id);
        $this->getWikiSystemEventManager()->queueWikiProjectReindexation($project_id);
    }

    private function reindexForServicesUsingUgroup($ugroup_id, $project_id) {
        $this->reindexForTrackerServiceUsingUgroup($ugroup_id, $project_id);
        $this->reindexForDocmanServiceUsingUgroup($ugroup_id, $project_id);
        $this->reindexForWikiServiceUsingUgroup($ugroup_id, $project_id);
    }

    private function reindexForTrackerServiceUsingUgroup($ugroup_id, $project_id) {
        $tracker_uses = false;

        EventManager::instance()->processEvent(
            FULLTEXTSEARCH_EVENT_DOES_TRACKER_SERVICE_USE_UGROUP,
            array('is_used' => &$tracker_uses, 'ugroup_id' => $ugroup_id, 'project_id' => $project_id)
        );

        if ($tracker_uses) {
            $this->getTrackerSystemEventManager()->queueTrackersProjectReindexation($project_id);
        }
    }

    private function reindexForDocmanServiceUsingUgroup($ugroup_id, $project_id) {
        $docman_uses  = false;

        EventManager::instance()->processEvent(
            FULLTEXTSEARCH_EVENT_DOES_DOCMAN_SERVICE_USE_UGROUP,
            array('is_used' => &$docman_uses, 'ugroup_id' => $ugroup_id, 'project_id' => $project_id)
        );

        if ($docman_uses) {
            $this->getDocmanSystemEventManager()->queueDocmanProjectReindexation($project_id);
        }
    }

    private function reindexForWikiServiceUsingUgroup($ugroup_id, $project_id) {
        $wiki_perm_manager = new Wiki_PermissionsManager(
            PermissionsManager::instance(),
            ProjectManager::instance(),
            new UGroupLiteralizer()
        );

        if ($wiki_perm_manager->isUgroupUsed($ugroup_id, $project_id)) {
            $this->getWikiSystemEventManager()->queueWikiProjectReindexation($project_id);
        }
    }

    public function redefine_search_type($params) {
        $calculator = new FullTextSearch_TypeOfSearchCalculator(PluginManager::instance(), $this);

        $params['type'] = $calculator->calculate($params['user'], $params['type'], $params['service_name'], $params['project_id']);
    }

    public function trackerArtifactCreated(ArtifactCreated $event)
    {
        $this->getTrackerSystemEventManager()->queueArtifactUpdate($event->getArtifact());
    }
}
