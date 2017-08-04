<?php
/**
 * MediaWikiPlugin Class
 *
 * Copyright 2000-2011, Fusionforge Team
 * Copyright 2012, Franck Villaume - TrivialDev
 * Copyright (c) Enalean SAS 2014 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;

require_once 'common/plugin/Plugin.class.php';
require_once 'constants.php';
require_once 'autoload.php';

class MediaWikiPlugin extends Plugin {

    const SERVICE_SHORTNAME = 'plugin_mediawiki';

    function __construct ($id=0) {
            $this->Plugin($id) ;
            $this->name = "mediawiki" ;
            $this->text = "Mediawiki" ; // To show in the tabs, use...
            $this->addHook('cssfile');
            $this->addHook(Event::SERVICE_ICON);
            $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
            $this->addHook(Event::PROCCESS_SYSTEM_CHECK);

            $this->addHook('permission_get_name');
            $this->addHook(Event::SERVICE_IS_USED);
            $this->addHook(Event::REGISTER_PROJECT_CREATION);

            $this->addHook(Event::SERVICE_REPLACE_TEMPLATE_NAME_IN_LINK);
            $this->addHook(Event::RENAME_PROJECT, 'rename_project');
            $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass');
            $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);

            //User permissions
            $this->addHook('project_admin_remove_user');
            $this->addHook('project_admin_change_user_permissions');
            $this->addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');
            $this->addHook('project_admin_ugroup_remove_user');
            $this->addHook('project_admin_remove_user_from_project_ugroups');
            $this->addHook('project_admin_ugroup_deletion');
            $this->addHook(Event::HAS_USER_BEEN_DELEGATED_ACCESS, 'has_user_been_delegated_access');
            $this->addHook(Event::IS_SCRIPT_HANDLED_FOR_RESTRICTED);
            $this->addHook(Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED);

            // Search
            $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
            $this->addHook(Event::SEARCH_TYPES_PRESENTERS);
            $this->addHook(Event::SEARCH_TYPE);

            $this->addHook('plugin_statistics_service_usage');

            $this->addHook(Event::SERVICE_CLASSNAMES);
            $this->addHook(Event::GET_PROJECTID_FROM_URL);

            // Stats plugin
            $this->addHook('plugin_statistics_disk_usage_collect_project');
            $this->addHook('plugin_statistics_disk_usage_service_label');
            $this->addHook('plugin_statistics_color');

            // Site admin link
            $this->addHook('site_admin_option_hook', 'site_admin_option_hook', false);
            $this->addHook(BurningParrotCompatiblePageEvent::NAME);

            $this->addHook(Event::PROJECT_ACCESS_CHANGE);
            $this->addHook(Event::SITE_ACCESS_CHANGE);

            $this->addHook(Event::IMPORT_XML_PROJECT, 'importXmlProject', false);
            $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
            $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
            $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);

            /**
             * HACK
             */
            require_once MEDIAWIKI_BASE_DIR . '/../fusionforge/compat/load_compatibilities_method.php';
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e812';
    }

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/mediawiki') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/mediawiki') === 0) {
            $params['javascript_files'][] = '/scripts/tuleap/manage-allowed-projects-on-resource.js';
        }
    }

    public function loaded()
    {
            parent::loaded();
            if(is_dir("/usr/share/mediawiki")){
                forge_define_config_item('src_path','mediawiki', "/usr/share/mediawiki");
                forge_define_config_item('mwdata_path', 'mediawiki', '$core/data_path/plugins/mediawiki');
                forge_define_config_item('projects_path', 'mediawiki', '$mediawiki/mwdata_path/projects');
                forge_define_config_item('master_path', 'mediawiki', '$mediawiki/mwdata_path/master');
                forge_define_config_item('enable_uploads', 'mediawiki', false);
                forge_set_config_item_bool('enable_uploads', 'mediawiki');
            }
        }

        public function layout_search_entry($params) {
            $project = $this->getProjectFromRequest();
            if ($this->isSearchEntryAvailable($project)) {
                $params['search_entries'][] = array(
                    'value'    => $this->name,
                    'label'    => $this->text,
                    'selected' => $this->isSearchEntrySelected($params['type_of_search']),
                );
                $params['hidden_fields'][] = array(
                    'name'  => 'group_id',
                    'value' => $project->getID()
                );
            }
        }

        /**
         * @see Event::SEARCH_TYPE
         */
        public function search_type($params) {
            $query   = $params['query'];
            $project = $query->getProject();

            if ($query->getTypeOfSearch() == $this->name && $this->isSearchEntryAvailable($project)) {
                if (! $project->isError()) {
                   util_return_to($this->getMediawikiSearchURI($project, $query->getWords()));
                }
            }
        }

        /**
         * @see Event::SEARCH_TYPES_PRESENTERS
         */
        public function search_types_presenters($params) {
            if ($this->isSearchEntryAvailable($params['project'])) {
                $params['project_presenters'][] = new Search_SearchTypePresenter(
                    $this->name,
                    $this->text,
                    array(),
                    $this->getMediawikiSearchURI($params['project'], $params['words'])
                );
            }
        }

    /**
     * @see Event::PROCCESS_SYSTEM_CHECK
     */
    public function proccess_system_check($params) {
        $this->getMediawikiMLEBExtensionManager()->activateMLEBForCompatibleProjects($params['logger']);
    }

        private function getMediawikiSearchURI(Project $project, $words) {
            return $this->getPluginPath().'/wiki/'. $project->getUnixName() .'/index.php?title=Special%3ASearch&search=' . urlencode($words) . '&go=Go';
        }

        private function isSearchEntryAvailable(Project $project = null) {
            if ($project && ! $project->isError()) {
                return $project->usesService(self::SERVICE_SHORTNAME);
            }
            return false;
        }

        private function isSearchEntrySelected($type_of_search) {
            return ($type_of_search == $this->name) || $this->isMediawikiUrl();
        }

        private function isMediawikiUrl() {
            return preg_match('%'.$this->getPluginPath().'/wiki/.*%', $_SERVER['REQUEST_URI']);
        }

        /**
         *
         * @return Project | null
         */
        private function getProjectFromRequest() {
            $matches = array();
            preg_match('%'.$this->getPluginPath().'/wiki/([^/]+)/.*%', $_SERVER['REQUEST_URI'], $matches);
            if (isset($matches[1])) {
                $project = ProjectManager::instance()->getProjectByUnixName($matches[1]);

                if ($project->isError()) {
                    $project = ProjectManager::instance()->getProject($matches[1]);
                }

                if (! $project->isError()) {
                    return $project;
                }
            }
            return null;
        }

        public function cssFile($params) {
            // Only show the stylesheet if we're actually in the Mediawiki pages.
            if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
                strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0) {
                echo '<link rel="stylesheet" type="text/css" href="/plugins/mediawiki/themes/default/css/style.css" />';
            }
        }

        public function showImage(Codendi_Request $request) {
            $project = $this->getProjectFromRequest();
            $user    = $request->getCurrentUser();

            if (! $project) {
                exit;
            }

            if ((! $project->isPublic() || $user->isRestricted())
                && ! $project->userIsMember()
                && ! $user->isSuperUser()
                && ! $this->doesUserHavePermission($user)
            ) {
                exit;
            }

            preg_match('%'.$this->getPluginPath().'/wiki/[^/]+/images(.*)%', $_SERVER['REQUEST_URI'], $matches);
            $file_location = $matches[1];

            $folder_location = '';
            if (is_dir('/var/lib/tuleap/mediawiki/projects/' . $project->getUnixName())) {
                $folder_location = '/var/lib/tuleap/mediawiki/projects/' . $project->getUnixName().'/images';
            } elseif (is_dir('/var/lib/tuleap/mediawiki/projects/' . $project->getId())) {
                $folder_location = '/var/lib/tuleap/mediawiki/projects/' . $project->getId().'/images';
            } else {
                exit;
            }

            $file = $folder_location.$file_location;
            if (! file_exists($file)) {
                exit;
            }

            $size = getimagesize($file);
            $fp   = fopen($file, 'r');

            if ($size and $fp) {
                header('Content-Type: '.$size['mime']);
                header('Content-Length: '.filesize($file));

                readfile($file);
                exit;
            }
        }

        function process() {
        echo '<h1>Mediawiki</h1>';
        echo $this->getPluginInfo()->getpropVal('answer');
        }

        function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'MediaWikiPluginInfo')) {
            $this->pluginInfo = new MediaWikiPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function service_replace_template_name_in_link($params) {
        $params['link'] = preg_replace(
            '#/plugins/mediawiki/wiki/'.preg_quote($params['template']['name'], '#').'(/|$)#',
            '/plugins/mediawiki/wiki/'. $params['project']->getUnixName().'$1',
            $params['link']
        );
    }


    public function register_project_creation($params) {
        if ($this->serviceIsUsedInTemplate($params['template_id'])) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiateFromTemplate($params['ugroupsMapping']);
            }
        } else if($this->serviceIsUsedInTemplate($params['group_id'])) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    public function has_user_been_delegated_access($params) {
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {

            /**
             * Only change the access rights to the affirmative.
             * Otherwise, we could overwrite a "true" value set by another plugin.
             */
            if ($this->doesUserHavePermission($params['user'])) {
                $params['can_access'] = true;
            }
        }
    }

    private function doesUserHavePermission(PFUser $user) {
        $forge_user_manager = $this->getForgeUserGroupPermissionsManager();

        return $forge_user_manager->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );
    }

    /**
     * @return User_ForgeUserGroupPermissionsManager
     */
    private function getForgeUserGroupPermissionsManager() {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    /**
     * @see Event::IS_SCRIPT_HANDLED_FOR_RESTRICTED
     */
    public function is_script_handled_for_restricted($params) {
        $uri = $params['uri'];
        if (strpos($uri, $this->getPluginPath()) === 0) {
            $params['allow_restricted'] = true;
        }
    }

    /**
     * @see Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED
     */
    public function get_services_allowed_for_restricted($params) {
        $params['allowed_services'][] = $this->getServiceShortname();
    }

    private function serviceIsUsedInTemplate($project_id) {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($project_id);

        return $project->usesService(self::SERVICE_SHORTNAME);
    }

    public function service_is_used($params) {
        if ($params['shortname'] == 'plugin_mediawiki' && $params['is_used']) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    private function getInstantiater($group_id) {
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($group_id);

        if (! $project instanceof Project || $project->isError()) {
            return;
        }

        return new MediaWikiInstantiater(
            $project,
            $this->getMediawikiManager(),
            $this->getMediawikiLanguageManager(),
            $this->getMediawikiVersionManager(),
            $this->getMediawikiMLEBExtensionManager()
        );
    }

    private function getMediawikiLanguageManager() {
        return new MediawikiLanguageManager(new MediawikiLanguageDao());
    }

    public function plugin_statistics_service_usage($params) {
        $dao             = new MediawikiDao();
        $project_manager = ProjectManager::instance();
        $start_date      = $params['start_date'];
        $end_date        = $params['end_date'];

        $number_of_page                   = array();
        $number_of_page_between_two_dates = array();
        $number_of_page_since_a_date      = array();
        foreach($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE) as $project) {
            if ($project->usesService('plugin_mediawiki')) {
                $number_of_page[] = $dao->getMediawikiPagesNumberOfAProject($project);
                $number_of_page_between_two_dates[] = $dao->getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate($project, $start_date, $end_date);
                $number_of_page_since_a_date[] = $dao->getCreatedPagesNumberSinceStartDate($project, $start_date);
            }
        }

        $params['csv_exporter']->buildDatas($number_of_page, "Mediawiki Pages");
        $params['csv_exporter']->buildDatas($number_of_page_between_two_dates, "Modified Mediawiki pages");
        $params['csv_exporter']->buildDatas($number_of_page_since_a_date, "Number of created Mediawiki pages since start date");
    }

    public function project_admin_ugroup_deletion($params) {
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
            $dao->deleteUserGroup($project->getID(), $params['ugroup_id']);
            $dao->resetUserGroups($project);
        }
    }

    public function project_admin_remove_user($params) {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_ugroup_remove_user($params) {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_change_user_permissions($params) {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_remove_user_from_project_ugroups($params) {
        $this->updateUserGroupMapping($params);
    }

    private function updateUserGroupMapping($params) {
        $user    = $this->getUserFromParams($params);
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
            $dao->resetUserGroupsForUser($user, $project);
        }
    }

    public function systemevent_user_rename($params) {
        $user            = $params['user'];
        $projects        = ProjectManager::instance()->getAllProjectsButDeleted();
        foreach ($projects as $project) {
            if ($project->usesService(MediaWikiPlugin::SERVICE_SHORTNAME)) {
                $this->getDao()->renameUser($project, $params['old_user_name'], $user->getUnixName());
            }
        }
    }

    private function getUserFromParams($params) {
        $user_id  = $params['user_id'];

        return UserManager::instance()->getUserById($user_id);
    }

    private function getProjectFromParams($params) {
        $group_id = $params['group_id'];

        return ProjectManager::instance()->getProject($group_id);
    }

    private function getDao() {
        return new MediawikiDao();
    }

    /**
     * @return MediawikiManager
     */
    private function getMediawikiManager() {
        return new MediawikiManager($this->getDao());
    }

    public function service_classnames(array $params) {
        $params['classnames']['plugin_mediawiki'] = 'ServiceMediawiki';
    }

    public function rename_project($params) {
        $project         = $params['project'];
        $project_manager = ProjectManager::instance();
        $new_link        = '/plugins/mediawiki/wiki/'. $params['new_name'];

        if (! $project_manager->renameProjectPluginServiceLink($project->getID(), self::SERVICE_SHORTNAME, $new_link)) {
            $params['success'] = false;
            return;
        }

        $this->updateMediawikiDirectory($project);
        $this->clearMediawikiCache($project);
    }

    private function updateMediawikiDirectory(Project $project) {
        $logger         = new BackendLogger();
        $project_id_dir = forge_get_config('projects_path', 'mediawiki') . "/". $project->getID() ;

        if (is_dir($project_id_dir)) {
            return true;
        }

        $project_name_dir = forge_get_config('projects_path', 'mediawiki') . "/" . $project->getUnixName();
        if (is_dir($project_name_dir)) {
            exec("mv $project_name_dir $project_id_dir");
            return true;
        }

        $logger->error('Project Rename: Can\'t find mediawiki directory for project: '.$project->getID());
        return false;
    }

    private function clearMediawikiCache(Project $project) {
        $schema = $this->getDao()->getMediawikiDatabaseName($project, false);
        $logger = new BackendLogger();

        if ($schema) {
            $delete = $this->getDao()->clearPageCacheForSchema($schema);
            if (! $delete) {
                $logger->error('Project Clear cache: Can\'t delete mediawiki cache for schema: '.$schema);
            }
        } else  {
            $logger->error('Project Clear cache: Can\'t find mediawiki db for project: '.$project->getID());
        }
    }

    public function get_projectid_from_url($params) {
        $url = $params['url'];

        if (strpos($url,'/plugins/mediawiki/wiki/') === 0) {
            $pieces       = explode("/", $url);
            $project_name = $pieces[4];

            $dao          = $params['project_dao'];
            $dao_results  = $dao->searchByUnixGroupName($project_name);
            if ($dao_results->rowCount() < 1) {
                // project does not exist
                return false;
            }

            $project_data         = $dao_results->getRow();
            $params['project_id'] = $project_data['group_id'];
        }
    }

    public function plugin_statistics_disk_usage_collect_project($params)
    {
        $start   = microtime(true);
        $row     = $params['project_row'];
        $project = $params['project'];

        $project_for_parth = $this->getMediawikiManager()->instanceUsesProjectID($project) ?
            $row['group_id'] : $row['unix_group_name'];

        $path = $GLOBALS['sys_data_dir']. '/mediawiki/projects/'. $project_for_parth;

        $size = $params['DiskUsageManager']->getDirSize($path);

        $params['DiskUsageManager']->_getDao()->addGroup(
            $row['group_id'],
            self::SERVICE_SHORTNAME,
            $size,
            $_SERVER['REQUEST_TIME']
        );

        $end  = microtime(true);
        $time = $end - $start;

        if (! isset($params['time_to_collect'][self::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][self::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][self::SERVICE_SHORTNAME] += $time;
    }

    public function plugin_statistics_disk_usage_service_label($params) {
        $params['services'][self::SERVICE_SHORTNAME] = 'Mediawiki';
    }

    public function plugin_statistics_color($params) {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'lightsalmon';
        }
    }

    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => 'Mediawiki',
            'href'  => $this->getPluginPath() . '/forge_admin.php?action=site_index'
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath().'/forge_admin.php?action=site_index') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function system_event_get_types_for_default_queue(array &$params) {
        $params['types'] = array_merge($params['types'], array(
            SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME
        ));
    }

    public function getSystemEventClass($params) {
        switch($params['type']) {
            case SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME:
                $params['class'] = 'SystemEvent_MEDIAWIKI_SWITCH_TO_123';
                $params['dependencies'] = array(
                    $this->getMediawikiMigrator(),
                    $this->getProjectManager(),
                    $this->getMediawikiVersionManager(),
                    $this->getMediawikiMLEBExtensionManager()
                );
                break;
            default:
                break;
        }
    }

    private function getMediawikiMigrator() {
        return new Mediawiki_Migration_MediawikiMigrator();
    }

    private function getProjectManager() {
        return ProjectManager::instance();
    }

    public function permission_get_name($params) {
        if (!$params['name']) {
            switch($params['permission_type']) {
                case MediawikiManager::READ_ACCESS:
                    $params['name'] = 'Read';
                    break;
                case MediawikiManager::WRITE_ACCESS:
                    $params['name'] = 'Write';
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * @see Event::PROJECT_ACCESS_CHANGE
     */
    public function project_access_change($params) {
        $project = ProjectManager::instance()->getProject($params['project_id']);

        $this->getMediawikiManager()->updateAccessControlInProjectChangeContext(
            $project,
            $params['old_access'],
            $params['access']
        );
    }

    /**
     * @see Event::SITE_ACCESS_CHANGE
     */
    public function site_access_change($params) {
        $this->getMediawikiManager()->updateSiteAccess($params['old_value']);
    }

    private function getMediawikiVersionManager() {
        return new MediawikiVersionManager(new MediawikiVersionDao());
    }

    /**
     * @return MediawikiMLEBExtensionManager
     */
    private function getMediawikiMLEBExtensionManager() {
        return new MediawikiMLEBExtensionManager(
            $this->getMediawikiMigrator(),
            $this->getMediawikiMLEBExtensionDao(),
            $this->getProjectManager(),
            $this->getMediawikiVersionManager(),
            $this->getMediawikiLanguageManager()
        );
    }

    private function getMediawikiMLEBExtensionDao() {
        return new MediawikiMLEBExtensionDao();
    }

    /**
     *
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function importXmlProject($params)
    {
        $importer = new MediaWikiXMLImporter(
            $params['logger'],
            $this->getMediawikiManager(),
            $this->getMediawikiLanguageManager(),
            new UGroupManager(),
            EventManager::instance()
        );
        $importer->import($params['configuration'], $params['project'], UserManager::instance()->getCurrentUser(), $params['xml_content'], $params['extraction_path']);
    }

    public function get_permission_delegation($params)
    {
        $permission = new MediawikiAdminAllProjects();

        $params['plugins_permission'][MediawikiAdminAllProjects::ID] = $permission;
    }
}
