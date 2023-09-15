<?php
/**
 * Copyright (c) Enalean SAS 2014 - Present. All Rights Reserved.
 * Copyright 2000-2011, Fusionforge Team
 * Copyright 2012, Franck Villaume - TrivialDev
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

use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB;
use Tuleap\MediawikiStandalone\Permissions\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use Tuleap\Mediawiki\Maintenance\CleanUnused;
use Tuleap\Mediawiki\Maintenance\CleanUnusedDao;
use Tuleap\Mediawiki\MediawikiDataDir;
use Tuleap\Mediawiki\MediawikiMaintenanceWrapper;
use Tuleap\Mediawiki\Migration\MoveToCentralDbDao;
use Tuleap\Mediawiki\PermissionsPerGroup\PermissionPerGroupPaneBuilder;
use Tuleap\Mediawiki\XMLMediaWikiExporter;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\ProjectUGroup\UserAndProjectUGroupRelationshipEvent;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserBecomesWikiAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerForumAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsAdministrator;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerNewsWriter;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerProjectAdmin;
use Tuleap\Project\Admin\ProjectUGroup\UserIsNoLongerWikiAdmin;
use Tuleap\Project\DelegatedUserAccessForProject;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\CollectServicesAllowedForRestrictedEvent;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;
use Tuleap\Statistics\CSV\StatisticsServiceUsage;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../mediawiki_standalone/vendor/autoload.php';

class MediaWikiPlugin extends Plugin implements PluginWithService //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const SERVICE_SHORTNAME = 'plugin_mediawiki';

    public function __construct($id = 0)
    {
        parent::__construct($id);
        $this->setName("mediawiki");
        $this->addHook('cssfile');

        $this->addHook('permission_get_name');
        $this->addHook(RegisterProjectCreationEvent::NAME);

        $this->addHook(Event::RENAME_PROJECT, 'rename_project');
        $this->addHook(ProjectStatusUpdate::NAME);

        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS, 'getSystemEventClass');
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);

        //User permissions
        $this->addHook('project_admin_remove_user');
        $this->addHook('project_admin_change_user_permissions');
        $this->addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');
        $this->addHook('project_admin_ugroup_remove_user');
        $this->addHook('project_admin_remove_user_from_project_ugroups');
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook(DelegatedUserAccessForProject::NAME);
        $this->addHook(RestrictedUsersAreHandledByPluginEvent::NAME);
        $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);

        // Search
        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::SEARCH_TYPES_PRESENTERS);
        $this->addHook(Event::SEARCH_TYPE);

        $this->addHook(Event::GET_PROJECTID_FROM_URL);

        // Stats plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project');
        $this->addHook('plugin_statistics_disk_usage_service_label');
        $this->addHook('plugin_statistics_color');

        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);

        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook(Event::IMPORT_XML_PROJECT, 'importXmlProject');
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(UserBecomesProjectAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerProjectAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesWikiAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerWikiAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesForumAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerForumAdmin::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesNewsWriter::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerNewsWriter::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserBecomesNewsAdministrator::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(UserIsNoLongerNewsAdministrator::NAME, 'updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent');
        $this->addHook(ExportXmlProject::NAME);

        $this->addHook(PermissionPerGroupPaneCollector::NAME);

        /**
         * HACK
         */
        require_once MEDIAWIKI_BASE_DIR . '/../fusionforge/compat/load_compatibilities_method.php';

        bindtextdomain('tuleap-mediawiki', __DIR__ . '/../site-content');
    }

    public function getPermissionDelegation(array $params): void
    {
        $params['plugins_permission'][MediawikiAdminAllProjects::ID] = new MediawikiAdminAllProjects();
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
        if (! $event->shouldExportAllData()) {
            return;
        }

        $this->getMediaWikiExporter($event->getProject()->getID())->exportToXml(
            $event->getIntoXml(),
            $event->getArchive(),
            'export_mw_' . $event->getProject()->getID() . time() . '.xml',
            $event->getTemporaryDumpPathOnFilesystem()
        );
    }

    private function getMediaWikiExporter($group_id)
    {
        $sys_command = new System_Command();
        return new XMLMediaWikiExporter(
            ProjectManager::instance()->getProject($group_id),
            new MediawikiManager(new MediawikiDao()),
            new UGroupManager(),
            ProjectXMLExporter::getLogger(),
            new MediawikiMaintenanceWrapper($sys_command),
            new MediawikiLanguageManager(new MediawikiLanguageDao()),
            new MediawikiDataDir()
        );
    }

    public function layout_search_entry($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $this->getProjectFromRequest();
        if ($this->isSearchEntryAvailable($project)) {
            $params['search_entries'][] = [
                'value'    => $this->getName(),
                'selected' => $this->isSearchEntrySelected($params['type_of_search']),
            ];
            $params['hidden_fields'][]  = [
                'name'  => 'group_id',
                'value' => $project->getID(),
            ];
        }
    }

        /**
         * @see Event::SEARCH_TYPE
         */
    public function search_type($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $query   = $params['query'];
        $project = $query->getProject();

        if ($query->getTypeOfSearch() == $this->getName() && $this->isSearchEntryAvailable($project)) {
            if (! $project->isError()) {
                util_return_to($this->getMediawikiSearchURI($project, $query->getWords()));
            }
        }
    }

        /**
         * @see Event::SEARCH_TYPES_PRESENTERS
         */
    public function search_types_presenters($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($this->isSearchEntryAvailable($params['project'])) {
            $params['project_presenters'][] = new Search_SearchTypePresenter(
                $this->getName(),
                "Mediawiki",
                [],
                $this->getMediawikiSearchURI($params['project'], $params['words'])
            );
        }
    }

    private function getMediawikiSearchURI(Project $project, $words)
    {
        return $this->getPluginPath() . '/wiki/' . $project->getUnixName() . '/index.php?title=Special%3ASearch&search=' . urlencode($words) . '&go=Go';
    }

    private function isSearchEntryAvailable(?Project $project = null)
    {
        if ($project && ! $project->isError()) {
            return $project->usesService(self::SERVICE_SHORTNAME);
        }
        return false;
    }

    private function isSearchEntrySelected($type_of_search)
    {
        return ($type_of_search == $this->getName()) || $this->isMediawikiUrl();
    }

    private function isMediawikiUrl()
    {
        return preg_match('%' . $this->getPluginPath() . '/wiki/.*%', $_SERVER['REQUEST_URI']);
    }

        /**
         *
         * @return Project | null
         */
    private function getProjectFromRequest()
    {
        $matches = [];
        preg_match('%' . $this->getPluginPath() . '/wiki/([^/]+)/.*%', $_SERVER['REQUEST_URI'], $matches);
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

    public function cssFile($params): void
    {
        // Only show the stylesheet if we're actually in the Mediawiki pages.
        if (
            strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('style.css') . '" />';
        }
    }

    /**
     * @psalm-mutation-free
     */
    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/mediawiki'
        );
    }

    public function showImage(Codendi_Request $request)
    {
        $project = $this->getProjectFromRequest();
        $user    = $request->getCurrentUser();

        if (! $project) {
            exit;
        }

        $service = $project->getService(self::SERVICE_SHORTNAME);
        if (! $service) {
            exit;
        }

        if (
            (! $project->isPublic() || $user->isRestricted())
            && ! $project->userIsMember()
            && ! $user->isSuperUser()
            && ! $this->doesUserHavePermission($user)
            && ! $this->getMediawikiManager()->userCanRead($user, $project)
        ) {
            exit;
        }

        preg_match('%' . $this->getPluginPath() . '/wiki/[^/]+/images(.*)%', $_SERVER['REQUEST_URI'], $matches);
        $file_location = $matches[1];

        $folder_location = '';
        if (is_dir('/var/lib/tuleap/mediawiki/projects/' . $project->getUnixName())) {
            $folder_location = '/var/lib/tuleap/mediawiki/projects/' . $project->getUnixName() . '/images';
        } elseif (is_dir('/var/lib/tuleap/mediawiki/projects/' . $project->getId())) {
            $folder_location = '/var/lib/tuleap/mediawiki/projects/' . (int) $project->getId() . '/images';
        } else {
            exit;
        }

        $file = $folder_location . $file_location;
        if (! file_exists($file)) {
            exit;
        }

        $size = getimagesize($file);
        $fp   = fopen($file, 'r');

        if ($size and $fp) {
            header('Content-Type: ' . $size['mime']);
            header('Content-Length: ' . filesize($file));

            readfile($file);
            exit;
        }
    }

    public function process()
    {
        echo '<h1>Mediawiki</h1>';
        echo $this->getPluginInfo()->getpropVal('answer');
    }

    public function &getPluginInfo()
    {
        if (! is_a($this->pluginInfo, 'MediaWikiPluginInfo')) {
            $this->pluginInfo = new MediaWikiPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $just_created_project = $event->getJustCreatedProject();
        if (
            ! $event->shouldProjectInheritFromTemplate()
            && ! $just_created_project->usesService(self::SERVICE_SHORTNAME)
        ) {
            return;
        }

        if ($event->getTemplateProject()->usesService(self::SERVICE_SHORTNAME)) {
            $mediawiki_instantiater = $this->getInstantiater((int) $just_created_project->getID());
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiateFromTemplate($event->getMappingRegistry()->getUgroupMapping());
            }
        } elseif ($just_created_project->usesService(self::SERVICE_SHORTNAME)) {
            $mediawiki_instantiater = $this->getInstantiater((int) $just_created_project->getID());
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    public function has_user_been_delegated_access(DelegatedUserAccessForProject $event): void//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (
            isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0 &&
                $this->doesUserHavePermission($event->getUser())
        ) {
            $event->enableAccessToProjectToTheUser();
        }
    }

    private function doesUserHavePermission(PFUser $user)
    {
        $forge_user_manager = $this->getForgeUserGroupPermissionsManager();

        return $forge_user_manager->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );
    }

    /**
     * @return User_ForgeUserGroupPermissionsManager
     */
    private function getForgeUserGroupPermissionsManager()
    {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    public function restrictedUsersAreHandledByPluginEvent(RestrictedUsersAreHandledByPluginEvent $event)
    {
        if (strpos($event->getUri(), $this->getPluginPath()) === 0) {
            $event->setPluginHandleRestricted();
        }
    }

    #[ListeningToEventClass]
    public function handleServiceAllowedForRestricted(CollectServicesAllowedForRestrictedEvent $event): void
    {
        $event->addServiceShortname($this->getServiceShortname());
    }

    private function getInstantiater($group_id)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($group_id);

        if (! $project instanceof Project || $project->isError()) {
            return;
        }

        return new MediaWikiInstantiater(
            $project,
            $this->getMediawikiManager(),
            $this->getMediawikiLanguageManager(),
            $this->getMediawikiVersionManager(),
        );
    }

    private function getMediawikiLanguageManager()
    {
        return new MediawikiLanguageManager(new MediawikiLanguageDao());
    }

    #[ListeningToEventClass]
    public function statisticsServiceUsage(StatisticsServiceUsage $event): void
    {
        $dao             = $this->getDao();
        $project_manager = ProjectManager::instance();
        $start_date      = $event->start_date;
        $end_date        = $event->end_date;

        $number_of_page                   = [];
        $number_of_page_between_two_dates = [];
        $number_of_page_since_a_date      = [];
        foreach ($project_manager->getProjectsByStatus(Project::STATUS_ACTIVE) as $project) {
            if ($project->usesService('plugin_mediawiki') && $dao->hasDatabase($project)) {
                $number_of_page[]                   = $dao->getMediawikiPagesNumberOfAProject($project);
                $number_of_page_between_two_dates[] = $dao->getModifiedMediawikiPagesNumberOfAProjectBetweenStartDateAndEndDate($project, $start_date, $end_date);
                $number_of_page_since_a_date[]      = $dao->getCreatedPagesNumberSinceStartDate($project, $start_date);
            }
        }

        $event->csv_exporter->buildDatas($number_of_page, "Mediawiki Pages");
        $event->csv_exporter->buildDatas($number_of_page_between_two_dates, "Modified Mediawiki pages");
        $event->csv_exporter->buildDatas($number_of_page_since_a_date, "Number of created Mediawiki pages since start date");
    }

    public function project_admin_ugroup_deletion($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $dao->deleteUserGroup($project->getID(), $params['ugroup_id']);
            $dao->resetUserGroups($project);
        }
    }

    public function project_admin_remove_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_ugroup_remove_user($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->updateUserGroupMapping($params);
    }

    public function updateUserGroupMappingFromUserAndProjectUGroupRelationshipEvent(UserAndProjectUGroupRelationshipEvent $event)
    {
        $this->updateUserGroupMapping(
            [
                'user_id'  => $event->getUser()->getId(),
                'group_id' => $event->getProject()->getID(),
            ]
        );
    }

    public function project_admin_change_user_permissions($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->updateUserGroupMapping($params);
    }

    public function project_admin_remove_user_from_project_ugroups($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->updateUserGroupMapping($params);
    }

    private function updateUserGroupMapping($params)
    {
        $user    = $this->getUserFromParams($params);
        $project = $this->getProjectFromParams($params);
        $dao     = $this->getDao();

        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $dao->resetUserGroupsForUser($user, $project);
        }
    }

    public function systemevent_user_rename($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $user     = $params['user'];
        $projects = ProjectManager::instance()->getAllProjectsButDeleted();
        foreach ($projects as $project) {
            if ($project->usesService(self::SERVICE_SHORTNAME) && $this->getDao()->hasDatabase($project)) {
                $this->getDao()->renameUser($project, $params['old_user_name'], $user->getUserName());
            }
        }
    }

    private function getUserFromParams($params)
    {
        $user_id = $params['user_id'];

        return UserManager::instance()->getUserById($user_id);
    }

    private function getProjectFromParams($params)
    {
        $group_id = $params['group_id'];

        return ProjectManager::instance()->getProject($group_id);
    }

    private function getDao()
    {
        return new MediawikiDao($this->getCentralDatabaseNameProperty());
    }

    private function getCentralDatabaseNameProperty()
    {
        return trim($this->getPluginInfo()->getPropVal('central_database'));
    }

    /**
     * @return MediawikiManager
     */
    public function getMediawikiManager()
    {
        return new MediawikiManager($this->getDao());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
    {
        $event->addService($this->getServiceShortname(), ServiceMediawiki::class);
    }

    /**
     * @see Event::SERVICE_IS_USED
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        if ($params['shortname'] == 'plugin_mediawiki' && $params['is_used']) {
            $mediawiki_instantiater = $this->getInstantiater($params['group_id']);
            if ($mediawiki_instantiater) {
                $mediawiki_instantiater->instantiate();
            }
        }
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for mediawiki
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for mediawiki
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for mediawiki
    }

    public function rename_project($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $params['project'];

        $this->updateMediawikiDirectory($project);
        $this->clearMediawikiCache($project);
    }

    private function updateMediawikiDirectory(Project $project)
    {
        $logger         = BackendLogger::getDefaultLogger();
        $project_id_dir = forge_get_config('projects_path', 'mediawiki') . "/" . $project->getID();

        if (is_dir($project_id_dir)) {
            return true;
        }

        $project_name_dir = forge_get_config('projects_path', 'mediawiki') . "/" . $project->getUnixName();
        if (is_dir($project_name_dir)) {
            exec("mv " . escapeshellarg($project_name_dir) . " " . escapeshellarg($project_id_dir));
            return true;
        }

        $logger->error('Project Rename: Can\'t find mediawiki directory for project: ' . $project->getID());
        return false;
    }

    private function clearMediawikiCache(Project $project)
    {
        $logger = $this->getBackendLogger();

        $delete = $this->getDao()->clearPageCacheForProject($project);
        if (! $delete) {
            $logger->error('Project Clear cache: Can\'t delete mediawiki cache for schema: ' . $project->getID());
        }
    }

    public function get_projectid_from_url($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $url = $params['url'];

        if (strpos($url, '/plugins/mediawiki/wiki/') === 0) {
            $pieces       = explode("/", $url);
            $project_name = $pieces[4];

            $dao         = $params['project_dao'];
            $dao_results = $dao->searchByUnixGroupName($project_name);
            if ($dao_results->rowCount() < 1) {
                // project does not exist
                return false;
            }

            $project_data         = $dao_results->getRow();
            $params['project_id'] = $project_data['group_id'];
        }
    }

    public function plugin_statistics_disk_usage_collect_project($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $start   = microtime(true);
        $row     = $params['project_row'];
        $project = $params['project'];

        $project_for_parth = $this->getMediawikiManager()->instanceUsesProjectID($project) ?
            $row['group_id'] : $row['unix_group_name'];

        $path = ForgeConfig::get('sys_data_dir') . '/mediawiki/projects/' . $project_for_parth;

        $size = $params['DiskUsageManager']->getDirSize($path);

        $params['DiskUsageManager']->_getDao()->addGroup(
            $row['group_id'],
            self::SERVICE_SHORTNAME,
            $size,
            $params['collect_date']->getTimestamp()
        );

        $end  = microtime(true);
        $time = $end - $start;

        if (! isset($params['time_to_collect'][self::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][self::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][self::SERVICE_SHORTNAME] += $time;
    }

    public function plugin_statistics_disk_usage_service_label($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['services'][self::SERVICE_SHORTNAME] = 'Mediawiki';
    }

    public function plugin_statistics_color($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'lightsalmon';
        }
    }

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                'Mediawiki',
                $this->getPluginPath() . '/forge_admin.php?action=site_index'
            )
        );
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() . '/forge_admin.php?action=site_index') === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function system_event_get_types_for_default_queue(array &$params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['types'] = array_merge($params['types'], [
            SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME,
            SystemEvent_MEDIAWIKI_TO_CENTRAL_DB::NAME,
        ]);
    }

    public function getSystemEventClass($params)
    {
        switch ($params['type']) {
            case SystemEvent_MEDIAWIKI_SWITCH_TO_123::NAME:
                $params['class']        = 'SystemEvent_MEDIAWIKI_SWITCH_TO_123';
                $params['dependencies'] = [
                    $this->getMediawikiMigrator(),
                    $this->getProjectManager(),
                    $this->getMediawikiVersionManager(),
                    new MediawikiSiteAdminResourceRestrictor(
                        new MediawikiSiteAdminResourceRestrictorDao(),
                        $this->getProjectManager()
                    ),
                ];
                break;
            case SystemEvent_MEDIAWIKI_TO_CENTRAL_DB::NAME:
                $params['class']        = 'Tuleap\Mediawiki\Events\SystemEvent_MEDIAWIKI_TO_CENTRAL_DB';
                $params['dependencies'] = [
                    new MoveToCentralDbDao($this->getCentralDatabaseNameProperty()),
                ];
                break;

            default:
                break;
        }
    }

    private function getMediawikiMigrator()
    {
        return new Mediawiki_Migration_MediawikiMigrator();
    }

    private function getProjectManager()
    {
        return ProjectManager::instance();
    }

    public function permission_get_name($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! $params['name']) {
            switch ($params['permission_type']) {
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
    public function projectAccessChange($params): void
    {
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
    public function site_access_change($params)//phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->getMediawikiManager()->updateSiteAccess($params['old_value']);
    }

    private function getMediawikiVersionManager()
    {
        return new MediawikiVersionManager(new MediawikiVersionDao());
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

    public function getCleanUnused(\Psr\Log\LoggerInterface $logger)
    {
        return new CleanUnused(
            $logger,
            new CleanUnusedDao(
                $logger,
                $this->getCentralDatabaseNameProperty()
            ),
            ProjectManager::instance(),
            Backend::instance('System'),
            $this->getDao(),
            new MediawikiDataDir()
        );
    }

    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            $clean_unused = $this->getCleanUnused($this->getBackendLogger());
            $clean_unused->purgeProject($event->project->getID());
        }
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(NavigationDropdownQuickLinksCollector $quick_links_collector)
    {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-mediawiki', 'Mediawiki'),
                $this->getPluginPath() . '/forge_admin.php?' . http_build_query(
                    [
                        'group_id' => $project->getID(),
                        'pane'     => 'permissions',
                    ]
                )
            )
        );
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager = new UGroupManager();

        $builder   = new PermissionPerGroupPaneBuilder(
            $this->getMediawikiManager(),
            $ugroup_manager,
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            new MediawikiUserGroupsMapper($this->getDao(), new User_ForgeUserGroupPermissionsDao())
        );
        $presenter = $builder->buildPresenter($event);

        $templates_dir = ForgeConfig::get('tuleap_dir') . '/plugins/mediawiki/templates/';
        $content       = TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $presenter);

        $project = $event->getProject();
        $service = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $rank_in_project = $service->getRank();
            $event->addPane($content, $rank_in_project);
        }
    }

    public function serviceEnableForXmlImportRetriever(\Tuleap\Project\XML\ServiceEnableForXmlImportRetriever $event): void
    {
    }
}
