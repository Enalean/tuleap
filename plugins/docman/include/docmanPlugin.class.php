<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once 'autoload.php';
require_once 'constants.php';

class DocmanPlugin extends Plugin {
    /**
     * Store docman root items indexed by groupId
     * 
     * @var Array;
     */
    private $rootItems = array();

    /**
     * Store controller instances
     * 
     * @var Array
     */
    private $controller = array();

    function __construct($id) {
        parent::__construct($id);

        $this->_addHook('cssfile',                           'cssFile',                           false);
        $this->_addHook('javascript_file',                   'jsFile',                            false);
        $this->_addHook('logs_daily',                        'logsDaily',                         false);
        $this->_addHook('permission_get_name',               'permission_get_name',               false);
        $this->_addHook('permission_get_object_type',        'permission_get_object_type',        false);
        $this->_addHook('permission_get_object_name',        'permission_get_object_name',        false);
        $this->_addHook('permission_get_object_fullname',    'permission_get_object_fullname',    false);
        $this->_addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change', false);
        $this->_addHook('service_public_areas',              'service_public_areas',              false);
        $this->_addHook('service_admin_pages',               'service_admin_pages',               false);
        $this->_addHook('permissions_for_ugroup',            'permissions_for_ugroup',            false);
        $this->_addHook('register_project_creation',         'installNewDocman',                  false);
        $this->_addHook('service_is_used',                   'service_is_used',                   false);
        $this->_addHook('soap',                              'soap',                              false);
        $this->_addHook('widget_instance',                   'myPageBox',                         false);
        $this->_addHook('widgets',                           'widgets',                           false);
        $this->_addHook('codendi_daily_start',               'codendiDaily',                      false);
        $this->_addHook('default_widgets_for_new_owner',     'default_widgets_for_new_owner',     false);
        $this->_addHook('wiki_page_updated',                 'wiki_page_updated',                 false);
        $this->_addHook('wiki_before_content',               'wiki_before_content',               false);
        $this->_addHook(Event::WIKI_DISPLAY_REMOVE_BUTTON,   'wiki_display_remove_button',        false);
        $this->_addHook('isWikiPageReferenced',              'isWikiPageReferenced',              false);
        $this->_addHook('isWikiPageEditable',                'isWikiPageEditable',                false);
        $this->_addHook('userCanAccessWikiDocument',         'userCanAccessWikiDocument',         false);
        $this->_addHook('getPermsLabelForWiki',              'getPermsLabelForWiki',              false);
        $this->_addHook('ajax_reference_tooltip',            'ajax_reference_tooltip',            false);
        $this->_addHook('project_export_entry',              'project_export_entry',              false);
        $this->_addHook('project_export',                    'project_export',                    false);
        $this->_addHook('SystemEvent_PROJECT_RENAME',        'renameProject',                     false);
        $this->_addHook('file_exists_in_data_dir',           'file_exists_in_data_dir',           false);
        $this->_addHook('webdav_root_for_service',           'webdav_root_for_service',           false);
        $this->addHook(Event::SERVICE_ICON);
        // Stats plugin
        $this->_addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project', false);
        $this->_addHook('plugin_statistics_disk_usage_service_label',   'plugin_statistics_disk_usage_service_label',   false);
        $this->_addHook('plugin_statistics_color',                      'plugin_statistics_color',                      false);

        $this->_addHook('show_pending_documents',             'show_pending_documents',             false);

        $this->_addHook('backend_system_purge_files',  'purgeFiles',  false);
        $this->_addHook('project_admin_remove_user', 'projectRemoveUser', false);
        $this->_addHook('project_is_private', 'projectIsPrivate', false);

        $this->_addHook('permission_request_information', 'permissionRequestInformation', false);

        $this->_addHook('fill_project_history_sub_events', 'fillProjectHistorySubEvents', false);
        $this->_addHook('project_is_deleted',              'project_is_deleted',          false);
        $this->_addHook(Event::COMBINED_SCRIPTS,           'combinedScripts',             false);
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
    }

    public function getHooksAndCallbacks() {
        if (defined('STATISTICS_BASE_DIR')) {
            $this->addHook(Statistics_Event::FREQUENCE_STAT_ENTRIES);
            $this->addHook(Statistics_Event::FREQUENCE_STAT_SAMPLE);
        }
        if (defined('FULLTEXTSEARCH_BASE_URL')) {
            $this->_addHook(FULLTEXTSEARCH_EVENT_FETCH_ALL_DOCUMENT_SEARCH_TYPES);
            $this->_addHook(FULLTEXTSEARCH_EVENT_DOES_DOCMAN_SERVICE_USE_UGROUP);
        }

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname() {
        return 'docman';
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e80c';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_ENTRIES
     */
    public function plugin_statistics_frequence_stat_entries($params) {
        $params['entries'][$this->getServiceShortname()] = 'Documents viewed';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_SAMPLE
     */
    public function plugin_statistics_frequence_stat_sample($params) {
        if ($params['character'] === $this->getServiceShortname()) {
            $params['sample'] = new Docman_Sample();
        }
    }

    function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
                case 'PLUGIN_DOCMAN_READ':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_read');
                    break;
                case 'PLUGIN_DOCMAN_WRITE':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_write');
                    break;
                case 'PLUGIN_DOCMAN_MANAGE':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_manage');
                    break;
                case 'PLUGIN_DOCMAN_ADMIN':
                    $params['name'] = $GLOBALS['Language']->getText('plugin_docman', 'permission_admin');
                    break;
                default:
                    break;
            }
        }
    }
    function permission_get_object_type($params) {
        if (!$params['object_type']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_type'] = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                }
            }
        }
    }
    function permission_get_object_name($params) {
        if (!$params['object_name']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_name'] = $item->getTitle();
                }
            }
        }
    }
    function permission_get_object_fullname($params) {
        if (!$params['object_fullname']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $type = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                    $name = $item->getTitle();
                    $params['object_fullname'] = $type .' '. $name; //TODO i18n
                }
            }
        }
    }
    function permissions_for_ugroup($params) {
        if (!$params['results']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if =& new Docman_ItemFactory();
                $item =& $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $type = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                    $params['results']  = $GLOBALS['Language']->getText('plugin_docman', 'resource_name_'.$type, array(
                            '/plugins/docman/?group_id='. $params['group_id'] .
                              '&amp;action=details&amp;section=permissions' .
                              '&amp;id='. $item->getId()
                            , $item->getTitle()
                        )
                    );
                }
            }
        }
    }
    var $_cached_permission_user_allowed_to_change;
    function permission_user_allowed_to_change($params) {
        if (!$params['allowed']) {
            if (!$this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                    $docman = $this->getHTTPController();
                    switch($params['permission_type']) {
                        case 'PLUGIN_DOCMAN_READ':
                        case 'PLUGIN_DOCMAN_WRITE':
                        case 'PLUGIN_DOCMAN_MANAGE':
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanManage($params['object_id']);
                            break;
                        default:
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanAdmin();
                            break;
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }
    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'DocmanPluginInfo')) {
            require_once('DocmanPluginInfo.class.php');
            $this->pluginInfo =& new DocmanPluginInfo($this);
        }
        return $this->pluginInfo;
    }
    
    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0 
        ) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
        }
    }
    
    function jsFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="/scripts/behaviour/behaviour.js"></script>'."\n";
            echo '<script type="text/javascript" src="/scripts/scriptaculous/scriptaculous.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/docman.js"></script>'."\n";
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/embedded_file.js"></script>'."\n";
        }
    }

    function logsDaily($params) {
        $controler = $this->getHTTPController();
        $controler->logsDaily($params);
    }
    
    function service_public_areas($params) {
        if ($params['project']->usesService($this->getServiceShortname())) {
            $params['areas'][] = '<a href="/plugins/docman/?group_id='. $params['project']->getId() .'">' .
                '<img src="'. $this->getThemePath() .'/images/ic/text.png" />&nbsp;' .
                $GLOBALS['Language']->getText('plugin_docman', 'descriptor_name') .': '.
                $GLOBALS['Language']->getText('plugin_docman', 'title') .
                '</a>';
        }
    }
    function service_admin_pages($params) {
        if ($params['project']->usesService($this->getServiceShortname())) {
            $params['admin_pages'][] = '<a href="/plugins/docman/?action=admin&amp;group_id='. $params['project']->getId() .'">' .
                $GLOBALS['Language']->getText('plugin_docman', 'service_lbl_key') .' - '. 
                $GLOBALS['Language']->getText('plugin_docman', 'admin_title') .
                '</a>';
        }
    }
    function installNewDocman($params) {
        $controler = $this->getHTTPController();
        $controler->installDocman($params['ugroupsMapping'], $params['group_id']);
    }
    function service_is_used($params) {
        if (isset($params['shortname']) && $params['shortname'] == $this->getServiceShortname()) {
            if (isset($params['is_used']) && $params['is_used']) {
                $this->installNewDocman(array('ugroupsMapping' => false));
            }
        }
    }
    function soap($arams) {
        require_once('soap.php');
    }

    function myPageBox($params) {
        switch ($params['widget']) {
            case 'plugin_docman_mydocman':
                require_once('Docman_Widget_MyDocman.class.php');
                $params['instance'] = new Docman_Widget_MyDocman($this->getPluginPath());
                break;
            case 'plugin_docman_my_embedded':
                require_once('Docman_Widget_MyEmbedded.class.php');
                $params['instance'] = new Docman_Widget_MyEmbedded($this->getPluginPath());
                break;
            case 'plugin_docman_project_embedded':
                require_once('Docman_Widget_ProjectEmbedded.class.php');
                $params['instance'] = new Docman_Widget_ProjectEmbedded($this->getPluginPath());
                break;
            case 'plugin_docman_mydocman_search':
                require_once('Docman_Widget_MyDocmanSearch.class.php');
                $params['instance'] = new Docman_Widget_MyDocmanSearch($this->getPluginPath());
                break;
            default:
                break;
        }
    }
    function widgets($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            $params['codendi_widgets'][] = 'plugin_docman_mydocman';
            $params['codendi_widgets'][] = 'plugin_docman_mydocman_search';
            $params['codendi_widgets'][] = 'plugin_docman_my_embedded';
        }
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_GROUP) {
            $params['codendi_widgets'][] = 'plugin_docman_project_embedded';
        }
    }
    function default_widgets_for_new_owner($params) {
        require_once('common/widget/WidgetLayoutManager.class.php');
        if ($params['owner_type'] == WidgetLayoutManager::OWNER_TYPE_USER) {
            $params['widgets'][] = array(
                'name' => 'plugin_docman_mydocman',
                'column' => 1,
                'rank' => 2,
            );
        }
    }
    /**
     * Hook: called by daily codendi script.
     */
    function codendiDaily() {
        $controler = $this->getHTTPController();
        $controler->notifyFuturObsoleteDocuments();
        $reminder = new Docman_ApprovalTableReminder();
        $reminder->remindApprovers();
    }

    function process() {
        $controler = $this->getHTTPController();
        $controler->process();
    }
    
    public function processSOAP($request) {
        return $this->getSOAPController($request)->process();
    }
     
    function wiki_page_updated($params) {
        require_once('Docman_WikiRequest.class.php');
        $request = new Docman_WikiRequest(array('action' => 'wiki_page_updated',
                                                'wiki_page' => $params['wiki_page'],
                                                'diff_link' => $params['diff_link'],
                                                'group_id'  => $params['group_id'],
                                                'user'      => $params['user'],
                                                'version'   => $params['version']));
        $this->getWikiController($request)->process(); 
    }

    function wiki_before_content($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'wiki_before_content';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process(); 
    }
    
    function wiki_display_remove_button($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'wiki_display_remove_button';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process(); 
    }

    function isWikiPageReferenced($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'check_whether_wiki_page_is_referenced';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process(); 
    }

    function isWikiPageEditable($params) {
        require_once('Docman_WikiRequest.class.php');
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    function userCanAccessWikiDocument($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'check_whether_user_can_access';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process(); 
    }

    function getPermsLabelForWiki($params) {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'getPermsLabelForWiki';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process(); 
    }

    function ajax_reference_tooltip($params) {
        if ($params['reference']->getServiceShortName() == 'docman') {
            $request = new Codendi_Request(array(
                'id'       => $params['val'],
                'group_id' => $params['group_id'],
                'action'   => 'ajax_reference_tooltip'
            ));
            $controler = $this->getCoreController($request);
            $controler->process();
        }
    }

    /**
     *  hook to display the link to export project data
     *  @param void
     *  @return void
     */
    function project_export_entry($params) {
        // Docman perms
        $url  = '?group_id='.$params['group_id'].'&export=plugin_docman_perms';
        $params['labels']['plugin_eac_docman']                           = $GLOBALS['Language']->getText('plugin_docman','Project_access_permission');
        $params['data_export_links']['plugin_eac_docman']                = $url.'&show=csv';
        $params['data_export_format_links']['plugin_eac_docman']         = $url.'&show=format';
        $params['history_export_links']['plugin_eac_docman']             = null;
        $params['history_export_format_links']['plugin_eac_docman']      = null;
        $params['dependencies_export_links']['plugin_eac_docman']        = null;
        $params['dependencies_export_format_links']['plugin_eac_docman'] = null;
    }

    /**
     *  hook to display the link to export project data
     *  @param void
     *  @return void
     */
    function project_export($params) {
        if($params['export'] == 'plugin_docman_perms') {
            include_once('Docman_PermissionsExport.class.php');
            $request = HTTPRequest::instance();
            $permExport = new Docman_PermissionsExport($params['project']);
            if ($request->get('show') == 'csv') {
                $permExport->toCSV();
            } else { // show = format
                $permExport->renderDefinitionFormat();
            }
            exit;
        }
    }

    /**
     * Hook called when a project is being renamed
     * @param Array $params
     * @return Boolean
     */
    function renameProject($params) {
        $docmanPath = $this->getPluginInfo()->getPropertyValueForName('docman_root').'/';
        //Is this project using docman
        if (is_dir($docmanPath.$params['project']->getUnixName())){
            $version      = new Docman_VersionFactory();

            return $version->renameProject($docmanPath, $params['project'], $params['new_name']);
        }

        return true;
    }
    
    /**
     * Hook called before renaming project to check the name validity
     * @param Array $params
     */
    function file_exists_in_data_dir($params) {
        $docmanPath = $this->getPluginInfo()->getPropertyValueForName('docman_root').'/';
        $path = $docmanPath.$params['new_name'];
        
        if (Backend::fileExists($path)) {
            $params['result']= false;
            $params['error'] = $GLOBALS['Language']->getText('plugin_docman','name_validity');
        }
    }

    /**
     * Hook to know if docman is activated for the given project
     * it returns the root item of that project
     *
     * @param Array $params
     */
    function webdav_root_for_service($params) {
        $groupId = $params['project']->getId();
        if ($params['project']->usesService('docman')) {
            if (!isset($this->rootItems[$groupId])) {
                include_once 'Docman_ItemFactory.class.php';
                $docmanItemFactory = new Docman_ItemFactory();
                $this->rootItems[$groupId] = $docmanItemFactory->getRoot($groupId);
            }
            $params['roots']['docman'] = $this->rootItems[$groupId];
        }
    }

    /**
     * Hook to collect docman disk size usage per project
     *
     * @param array $params
     */
    function plugin_statistics_disk_usage_collect_project($params) {
        $row  = $params['project_row'];
        $root = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path = $root.'/'.strtolower($row['unix_group_name']);
        $params['DiskUsageManager']->storeForGroup($row['group_id'], 'plugin_docman', $path);
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     * 
     * @param array $params
     */
    function plugin_statistics_disk_usage_service_label($params) {
        $params['services']['plugin_docman'] = 'Docman';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     * 
     * @param array $params
     */
    function plugin_statistics_color($params) {
        if ($params['service'] == 'plugin_docman') {
            $params['color'] = 'royalblue';
        }
    }

    /**
     * Hook to list pending documents and/or versions of documents in site admin page
     *
     * @param array $params
     */
    function show_pending_documents($params) {
        $request = HTTPRequest::instance();
        $limit = 25;

        //return all pending versions for given group id
        $offsetVers = $request->getValidated('offsetVers', 'uint', 0);
        if ( !$offsetVers || $offsetVers < 0 ) {
            $offsetVers = 0;
        }
        
        $linkToLogMsg = '<p>When an element is deleted, the action appears in <a href="/project/stats/source_code_access.php/?who=allusers&span=14&view=daily&group_id='.$params['group_id'].'">the access log</a>.</p>';
        
        require_once('Docman_VersionFactory.class.php');
        $version = new Docman_VersionFactory();
        $res = $version->listPendingVersions($params['group_id'], $offsetVers, $limit);
        $params['id'][] = 'version';
        $params['nom'][] = $GLOBALS['Language']->getText('plugin_docman','deleted_version');
        $html = '';
        $html .= '<div class="contenu_onglet" id="contenu_onglet_version">';
        $html .= $linkToLogMsg;
        if (isset($res) && $res) {
            $html .= $this->showPendingVersions($res['versions'], $params['group_id'], $res['nbVersions'], $offsetVers, $limit);
        } else {
            $html .= 'No restorable versions found';
        }
        $html .='</div>';
        $params['html'][]= $html;

        //return all pending items for given group id
        $offsetItem = $request->getValidated('offsetItem', 'uint', 0);
        if ( !$offsetItem || $offsetItem < 0 ) {
            $offsetItem = 0;
        }
        require_once('Docman_ItemFactory.class.php');
        $item = new Docman_ItemFactory($params['group_id']);
        $res = $item->listPendingItems($params['group_id'], $offsetItem , $limit);
        $params['id'][] = 'item';
        $params['nom'][]= $GLOBALS['Language']->getText('plugin_docman','deleted_item');
        $html = '';
        $html .= '<div class="contenu_onglet" id="contenu_onglet_item">';
        $html .= $linkToLogMsg;
        if (isset($res) && $res) {
            $html .= $this->showPendingItems($res['items'], $params['group_id'], $res['nbItems'], $offsetItem, $limit);
        } else {
            $html .= 'No restorable items found';
        }
        $html .='</div>';
        $params['html'][]= $html;
    }

    function showPendingVersions($versions, $groupId, $nbVersions, $offset, $limit) {
        $hp = Codendi_HTMLPurifier::instance();

        $html ='';
        $title =array();
        $title[] = $GLOBALS['Language']->getText('plugin_docman','item_id');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','doc_title');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','label');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','number');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','delete_date');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','purge_date');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','restore_version');

        if ($nbVersions > 0) {

            $html .= '<H3>'.$GLOBALS['Language']->getText('plugin_docman', 'deleted_version').'</H3><P>';
            $html .= html_build_list_table_top ($title);
            $i=1;

            foreach ($versions as $row) {
                $historyUrl = $this->getPluginPath().'/index.php?group_id='.$groupId.'&id='.$row['item_id'].'&action=details&section=history';
                $purgeDate = strtotime('+'.$GLOBALS['sys_file_deletion_delay'].' day', $row['date']);
                $html .= '<tr class="'. html_get_alt_row_color($i++) .'">'.
                '<td><a href="'.$historyUrl.'">'.$row['item_id'].'</a></td>'.
                '<td>'.$hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $groupId).'</td>'.
                '<td>'.$hp->purify($row['label']).'</td>'.
                '<td>'.$row['number'].'</td>'.
                '<td>'.html_time_ago($row['date']).'</td>'.
                '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $purgeDate).'</td>'.
                '<td align="center"><a href="/plugins/docman/restore_documents.php?group_id='.$groupId.'&func=confirm_restore_version&id='.$row['id'].'&item_id='.$row['item_id'].'" ><IMG SRC="'.util_get_image_theme("ic/convert.png").'" onClick="return confirm(\'Confirm restore of this version\')" BORDER=0 HEIGHT=16 WIDTH=16></a></td></tr>';
            }
            $html .= '</TABLE>'; 


            $html .= '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';

            if ($offset > 0) {
                $html .=  '<a href="?group_id='.$groupId.'&focus=version&offsetVers='.($offset -$limit).'">[ '.$GLOBALS['Language']->getText('plugin_docman', 'previous').'  ]</a>';
                $html .= '&nbsp;';
            }
            if (($offset + $limit) < $nbVersions) {
                $html .= '&nbsp;';
                $html .='<a href="?group_id='.$groupId.'&focus=version&offsetVers='.($offset+$limit).'">[ '.$GLOBALS['Language']->getText('plugin_docman', 'next').' ]</a>';
            }
            $html .='<br>'.($offset+$i-2).'/'.$nbVersions.'</br>';
            $html .= '</div>';
          
        } else {
            $html .= $GLOBALS['Response']->addFeedback('info',$GLOBALS['Language']->getText('plugin_docman', 'no_pending_versions'));
        }
        return $html;
    }

    function showPendingItems($res, $groupId, $nbItems, $offset, $limit) {
        $hp = Codendi_HTMLPurifier::instance();
        require_once('Docman_ItemFactory.class.php');
        $itemFactory = new Docman_ItemFactory($groupId);
        $uh = UserHelper::instance();

        $html ='';
        $title =array();
        $title[] = $GLOBALS['Language']->getText('plugin_docman','item_id');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','filters_item_type');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','doc_title');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','location');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','owner');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','delete_date');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','purge_date');
        $title[] = $GLOBALS['Language']->getText('plugin_docman','restore_item');


        if ($nbItems > 0) {
            $html .= '<H3>'.$GLOBALS['Language']->getText('plugin_docman', 'deleted_item').'</H3><P>';
            $html .= html_build_list_table_top ($title);
            $i=1;
            foreach ($res as $row ){
                $purgeDate = strtotime('+'.$GLOBALS['sys_file_deletion_delay'].' day', $row['date']);
                $html .='<tr class="'. html_get_alt_row_color($i++) .'">'.
                '<td>'.$row['id'].'</td>'.
                '<td>'.$itemFactory->getItemTypeAsText($row['item_type']).'</td>'.
                '<td>'.$hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $groupId).'</td>'.
                '<td>'.$hp->purify($row['location']).'</td>'.
                '<td>'.$hp->purify($uh->getDisplayNameFromUserId($row['user'])).'</td>'.
                '<td>'.html_time_ago($row['date']).'</td>'.
                '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $purgeDate).'</td>'.
                '<td align="center"><a href="/plugins/docman/restore_documents.php?group_id='.$groupId.'&func=confirm_restore_item&id='.$row['id'].'" ><IMG SRC="'.util_get_image_theme("ic/convert.png").'" onClick="return confirm(\'Confirm restore of this item\')" BORDER=0 HEIGHT=16 WIDTH=16></a></td></tr>';
            }
            $html .= '</TABLE>'; 

            $html .= '<div style="text-align:center" class="'. util_get_alt_row_color($i++) .'">';

            if ($offset > 0) {
                $html .=  '<a href="?group_id='.$groupId.'&focus=item&offsetItem='.($offset -$limit).'">[ '.$GLOBALS['Language']->getText('plugin_docman', 'previous').'  ]</a>';
                $html .= '&nbsp;';
            }
            if (($offset + $limit) < $nbItems) {
                $html .= '&nbsp;';
                $html .= '<a href="?group_id='.$groupId.'&focus=item&offsetItem='.($offset+$limit).'">[ '.$GLOBALS['Language']->getText('plugin_docman', 'next').' ]</a>';
            }
            $html .='<br>'.($offset +$i-2).'/'.$nbItems.'</br>';
            $html .= '</div>';

        } else {
            $html .= $GLOBALS['Response']->addFeedback('info',$GLOBALS['Language']->getText('plugin_docman', 'no_pending_items'));
        }
        return $html;
    }

    /**
     * Hook to purge deleted items if their agony ends today
     *
     * @param Array $params
     *
     * @return void
     */
    function purgeFiles(array $params) {
        require_once('Docman_ItemFactory.class.php');
        $itemFactory = new Docman_ItemFactory();
        $itemFactory->purgeDeletedItems($params['time']);

        require_once('Docman_VersionFactory.class.php');
        $versionFactory = new Docman_VersionFactory();
        $versionFactory->purgeDeletedVersions($params['time']);
    }

    /**
     * Function called when a project is deleted.
     * It Marks all project documents as deleted
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     *
     * @return void
     */
        function project_is_deleted($params) {
            $groupId = $params['group_id'];
            if ($groupId) {
                require_once('Docman_ItemFactory.class.php');
                $docmanItemFactory = new Docman_ItemFactory();
                $docmanItemFactory->deleteProjectTree($groupId);
            }
        }

    /**
     * Function called when a user is removed from a project 
     * If a user is removed from a private project, the
     * documents monitored by that user should be monitored no more.
     *
     * @param array $params
     *
     * @return void
     */
    function projectRemoveUser($params) {
        $groupId = $params['group_id'];
        $userId = $params['user_id'];

        $pm = ProjectManager::instance();
        $project = $pm->getProject($groupId);
        if (!$project->isPublic()) {
            require_once('Docman_ItemFactory.class.php');
            $docmanItemFactory = new Docman_ItemFactory();
            $root = $docmanItemFactory->getRoot($groupId);
            if ($root) {
                require_once('Docman_NotificationsManager.class.php');
                $notificationsManager = new Docman_NotificationsManager($groupId, null, null);
                $dar = $notificationsManager->listAllMonitoredItems($groupId, $userId);
                if($dar && !$dar->isError()) {
                    foreach ($dar as $row) {
                        $notificationsManager->remove($row['user_id'], $row['object_id'], $row['type']);
                    }
                }
            }
        }
    }

    /**
     * Function called when a project goes from public to private so
     * documents monitored by non member users should be monitored no more.
     *
     * @param array $params
     *
     * @return void
     */
    function projectIsPrivate($params) {
        $groupId = $params['group_id'];
        $private = $params['project_is_private'];

        if ($private) {
            require_once('Docman_ItemFactory.class.php');
            $docmanItemFactory = new Docman_ItemFactory();
            $root = $docmanItemFactory->getRoot($groupId);
            if ($root) {
                require_once('Docman_NotificationsManager.class.php');
                $notificationsManager = new Docman_NotificationsManager($groupId, null, null);
                $dar = $notificationsManager->listAllMonitoredItems($groupId);
                if($dar && !$dar->isError()) {
                    $userManager = UserManager::instance();
                    $user = null;
                    foreach ($dar as $row) {
                        $user = $userManager->getUserById($row['user_id']);
                        if (!$user->isMember($groupId)) {
                            $notificationsManager->remove($row['user_id'], $row['object_id'], $row['type']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Display information about admin delegation
     *
     * @return void
     */
    function permissionRequestInformation($params) {
        echo "<p><h2>".$GLOBALS['Language']->getText('plugin_docman', 'permission_requests')."</h2>".$GLOBALS['Language']->getText('plugin_docman', 'permission_requests_information')."</p>";
    }

    /**
     * Fill the list of subEvents related to docman in the project history interface
     *
     */
    function fillProjectHistorySubEvents($params) {
        array_push($params['subEvents']['event_permission'], 'perm_reset_for_document',
                                                             'perm_granted_for_document',
                                                             'perm_reset_for_folder',
                                                             'perm_granted_for_folder'
        );
    }

    protected function getWikiController($request) {
        return $this->getController('Docman_WikiController', $request);
    }

    protected function getHTTPController($request=null) {
        if ($request == null) {
            $request = HTTPRequest::instance();
        }
        return $this->getController('Docman_HTTPController', $request);
    }
    
    protected function getCoreController($request) {
        return $this->getController('Docman_CoreController', $request);
    }
    
    protected function getSOAPController($request) {
        return $this->getController('Docman_SOAPController', $request);
    }

    protected function getController($controller, $request) {
        if (!isset($this->controller[$controller])) {
            include_once $controller.'.class.php';
            $this->controller[$controller] = new $controller($this, $this->getPluginPath(), $this->getThemePath(), $request);
        } else {
            $this->controller[$controller]->setRequest($request);
        }
        return $this->controller[$controller];
    }

    /**
     * Append scripts to the combined JS file
     *
     * @param Array $params parameters of the hook
     *
     * @return Void
     */
    public function combinedScripts($params) {
        $params['scripts'] = array_merge($params['scripts'], array($this->getPluginPath().'/scripts/ApprovalTableReminder.js'));
    }

    public function fulltextsearch_event_fetch_all_document_search_types($params) {
        $params['all_document_search_types'][] = array(
            'key'     => 'docman',
            'name'    => $GLOBALS['Language']->getText('plugin_docman', 'search_type'),
            'info'    => false,
            'can_use' => true,
            'special' => false,
        );
    }

    public function fulltextsearch_event_does_docman_service_use_ugroup($params) {
        $manager   = Docman_PermissionsManager::instance($params['project_id']);
        $ugroup_id = $params['ugroup_id'];

        $params['is_used'] = $manager->isUgroupUsed($ugroup_id);
    }

    public function proccess_system_check($params) {
        $docman_system_check = new Docman_SystemCheck(
            $this,
            new Docman_SystemCheckProjectRetriever(new Docman_SystemCheckDao()),
            BackendSystem::instance(),
            $params['logger']
        );

        $docman_system_check->process();
    }
}
