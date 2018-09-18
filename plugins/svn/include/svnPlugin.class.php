<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

require_once 'constants.php';

use Tuleap\CVS\DiskUsage\Collector as CVSCollector;
use Tuleap\CVS\DiskUsage\FullHistoryDao;
use Tuleap\CVS\DiskUsage\Retriever as CVSRetriever;
use Tuleap\Httpd\PostRotateEvent;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\project\Event\ProjectRegistrationActivateService;
use Tuleap\REST\Event\ProjectGetSvn;
use Tuleap\REST\Event\ProjectOptionsSvn;
use Tuleap\Service\ServiceCreator;
use Tuleap\Svn\AccessControl\AccessControlController;
use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\AccessControl\AccessFileHistoryDao;
use Tuleap\Svn\AccessControl\AccessFileHistoryFactory;
use Tuleap\Svn\Admin\AdminController;
use Tuleap\Svn\Admin\Destructor;
use Tuleap\Svn\Admin\GlobalAdminController;
use Tuleap\Svn\Admin\ImmutableTagController;
use Tuleap\Svn\Admin\ImmutableTagCreator;
use Tuleap\Svn\Admin\ImmutableTagDao;
use Tuleap\Svn\Admin\ImmutableTagFactory;
use Tuleap\Svn\Admin\MailHeaderDao;
use Tuleap\Svn\Admin\MailHeaderManager;
use Tuleap\Svn\Admin\MailNotificationDao;
use Tuleap\Svn\Admin\MailNotificationManager;
use Tuleap\Svn\Admin\RestoreController;
use Tuleap\Svn\ApacheConfGenerator;
use Tuleap\Svn\Commit\Svnlook;
use Tuleap\Svn\Dao;
use Tuleap\SVN\DiskUsage\Collector as SVNCollector;
use Tuleap\Svn\DiskUsage\DiskUsageCollector;
use Tuleap\Svn\DiskUsage\DiskUsageDao;
use Tuleap\Svn\DiskUsage\DiskUsageRetriever;
use Tuleap\SVN\DiskUsage\Retriever as SVNRetriever;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_DELETE_REPOSITORY;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_RESTORE_REPOSITORY;
use Tuleap\Svn\Explorer\ExplorerController;
use Tuleap\Svn\Explorer\RepositoryBuilder;
use Tuleap\Svn\Explorer\RepositoryDisplayController;
use Tuleap\Svn\Logs\DBWriter;
use Tuleap\Svn\Logs\QueryBuilder;
use Tuleap\Svn\Migration\RepositoryCopier;
use Tuleap\SVN\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Svn\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\Svn\Notifications\NotificationListBuilder;
use Tuleap\Svn\Notifications\NotificationsEmailsBuilder;
use Tuleap\Svn\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\Svn\Notifications\UgroupsToNotifyDao;
use Tuleap\Svn\Notifications\UgroupsToNotifyUpdater;
use Tuleap\Svn\Notifications\UsersToNotifyDao;
use Tuleap\Svn\PermissionsPerGroup\PaneCollector;
use Tuleap\Svn\PermissionsPerGroup\PermissionPerGroupRepositoryRepresentationBuilder;
use Tuleap\Svn\PermissionsPerGroup\PermissionPerGroupSVNServicePaneBuilder;
use Tuleap\Svn\PermissionsPerGroup\SVNJSONPermissionsRetriever;
use Tuleap\Svn\Reference\Extractor;
use Tuleap\Svn\Repository\HookConfigChecker;
use Tuleap\Svn\Repository\HookConfigRetriever;
use Tuleap\Svn\Repository\HookConfigSanitizer;
use Tuleap\Svn\Repository\HookConfigUpdator;
use Tuleap\Svn\Repository\HookDao;
use Tuleap\Svn\Repository\ProjectHistoryFormatter;
use Tuleap\Svn\Repository\RepositoryCreator;
use Tuleap\Svn\Repository\RepositoryManager;
use Tuleap\Svn\Repository\RepositoryRegexpBuilder;
use Tuleap\Svn\Repository\RuleName;
use Tuleap\Svn\Service\ServiceActivator;
use Tuleap\Svn\SvnAdmin;
use Tuleap\Svn\SvnLogger;
use Tuleap\Svn\SvnPermissionManager;
use Tuleap\Svn\SvnRouter;
use Tuleap\Svn\ViewVC\AccessHistoryDao;
use Tuleap\Svn\ViewVC\AccessHistorySaver;
use Tuleap\Svn\ViewVC\ViewVCProxy;
use Tuleap\Svn\XMLImporter;
use Tuleap\Svn\XMLSvnExporter;

/**
 * SVN plugin
 */
class SvnPlugin extends Plugin
{
    const SERVICE_SHORTNAME  = 'plugin_svn';
    const SYSTEM_NATURE_NAME = 'svn_revision';

    /** @var Tuleap\Svn\Repository\RepositoryManager */
    private $repository_manager;

    /** @var Tuleap\Svn\AccessControl\AccessFileHistoryDao */
    private $accessfile_dao;

    /** @var Tuleap\Svn\AccessControl\AccessFileHistoryFactory */
    private $accessfile_factory;

    /** @var Tuleap\Svn\AccessControl\AccessFileHistoryCreator */
    private $accessfile_history_creator;

    /** @var Tuleap\Svn\Admin\MailNotificationManager */
    private $mail_notification_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        bindtextdomain('tuleap-svn', __DIR__.'/../site-content');

        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::GET_SVN_LIST_REPOSITORIES_SQL_FRAGMENTS);
        $this->addHook(Event::UGROUP_MODIFY);
        $this->addHook(Event::MEMBERSHIP_CREATE);
        $this->addHook(Event::MEMBERSHIP_DELETE);
        $this->addHook(Event::IMPORT_XML_PROJECT);
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook('codendi_daily_start');
        $this->addHook('show_pending_documents');
        $this->addHook('project_is_deleted');
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook('project_admin_remove_user');
        $this->addHook('logs_daily');
        $this->addHook('statistics_collector');
        $this->addHook('plugin_statistics_service_usage');
        $this->addHook('SystemEvent_PROJECT_RENAME', 'systemEventProjectRename');
        $this->addHook('plugin_statistics_disk_usage_collect_project');
        $this->addHook('plugin_statistics_disk_usage_service_label');
        $this->addHook('plugin_statistics_color');
        $this->addHook('SystemEvent_USER_RENAME', 'systemevent_user_rename');
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::SVN_REPOSITORY_CREATED);
        $this->addHook(ProjectCreator::PROJECT_CREATION_REMOVE_LEGACY_SERVICES);
        $this->addHook(Event::EXPORT_XML_PROJECT);
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);

        $this->addHook(EVENT::REST_RESOURCES);
        $this->addHook(EVENT::REST_PROJECT_RESOURCES);
        $this->addHook(ProjectGetSvn::NAME);
        $this->addHook(ProjectOptionsSvn::NAME);
        $this->addHook(ProjectRegistrationActivateService::NAME);
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(PermissionPerGroupDisplayEvent::NAME);

        $this->addHook(PostRotateEvent::NAME);
    }

    public function export_xml_project($params)
    {
        if (! isset($params['options']['all']) || $params['options']['all'] === false) {
            return;
        }

        $this->getSvnExporter($params['project'])->exportToXml(
            $params['into_xml'],
            $params['archive'],
            $params['temporary_dump_path_on_filesystem']
        );
    }

    private function getSvnExporter(Project $project)
    {
        return new XMLSvnExporter(
            $this->getRepositoryManager(),
            $project,
            new SvnAdmin(new System_Command(), new SvnLogger(), Backend::instance(Backend::SVN)),
            new XML_SimpleXMLCDATAFactory(),
            $this->getMailNotificationManager(),
            new System_Command(),
            new SvnLogger()
        );
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'SvnPluginInfo')) {
            $this->pluginInfo = new SvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return self::SERVICE_SHORTNAME;
    }

    public function getTypes() {
        return array(
            SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            SystemEvent_SVN_DELETE_REPOSITORY::NAME,
            SystemEvent_SVN_RESTORE_REPOSITORY::NAME
        );
    }

    /**
     * Returns the configuration defined for given variable name
     *
     * @param String $key
     *
     * @return Mixed
     */
    public function getConfigurationParameter($key)
    {
        return $this->getPluginInfo()->getPropertyValueForName($key);
    }

    /** @see Event::UGROUP_MODIFY */
    public function ugroup_modify(array $params) {
        $project         = $params['project'];

        $this->updateAllAccessFileOfProject($project, $params['new_ugroup_name'], $params['old_ugroup_name']);
    }

    /** @see Event::MEMBERSHIP_CREATE */
    public function membership_create(array $params) {
        $project         = $params['project'];
        $new_ugroup_name = null;
        $old_ugroup_name = null;

        $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
    }

    /** @see Event::MEMBERSHIP_DELETE */
    public function membership_delete(array $params) {
        $project         = $params['project'];
        $new_ugroup_name = null;
        $old_ugroup_name = null;

        $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
    }

    public function systemevent_user_rename(array $params)
    {
        $new_ugroup_name = null;
        $old_ugroup_name = null;
        $user            = $params['user'];

        $projects = $this->getProjectManager()->getAllProjectsForUser($user);

        foreach ($projects as $project) {
            $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
        }
    }

    private function updateAllAccessFileOfProject(Project $project, $new_ugroup_name, $old_ugroup_name) {
        $list_repositories = $this->getRepositoryManager()->getRepositoriesInProject($project);
        foreach ($list_repositories as $repository) {
            $this->getBackendSVN()->updateSVNAccessForRepository(
                $project,
                $repository->getSystemPath(),
                $new_ugroup_name,
                $old_ugroup_name,
                $repository->getFullName()
            );
        }
    }

    public function get_svn_list_repositories_sql_fragments(array $params) {
        $dao = new Dao();
        $params['sql_fragments'][] = $dao->getListRepositoriesSqlFragment();
    }

    public function system_event_get_types_for_default_queue($params) {
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_CREATE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_DELETE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\Svn\\EventRepository\\'.SystemEvent_SVN_RESTORE_REPOSITORY::NAME;
    }

    public function get_system_event_class($params)
    {
        switch ($params['type']) {
            case 'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_CREATE_REPOSITORY':
                $params['class'] = 'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_CREATE_REPOSITORY';
                $params['dependencies'] = array(
                    $this->getAccessFileHistoryCreator(),
                    $this->getRepositoryManager(),
                    $this->getUserManager(),
                    $this->getBackendSVN(),
                    $this->getBackendSystem(),
                    $this->getCopier()
                );
                break;
            case 'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_DELETE_REPOSITORY':
                $params['class'] = 'Tuleap\\Svn\\EventRepository\\SystemEvent_SVN_DELETE_REPOSITORY';
                $params['dependencies'] = array(
                    $this->getRepositoryManager(),
                    ProjectManager::instance(),
                    $this->getApacheConfGenerator(),
                    $this->getRepositoryDeleter(),
                    new SvnAdmin(new System_Command(), new SvnLogger(), Backend::instance(Backend::SVN))
                );
                break;
        }
    }

    private function getApacheConfGenerator()
    {
        return ApacheConfGenerator::build();
    }

    /** @return Tuleap\Svn\Repository\RepositoryManager */
    private function getRepositoryManager()
    {
        if (empty($this->repository_manager)) {
            $this->repository_manager = new RepositoryManager(
                new Dao(),
                ProjectManager::instance(),
                new SvnAdmin(new System_Command(), new SvnLogger(), Backend::instance(Backend::SVN)),
                new SvnLogger(),
                new System_Command(),
                new Destructor(
                    new Dao(),
                    new SvnLogger()
                ),
                EventManager::instance(),
                Backend::instance(Backend::SVN),
                new AccessFileHistoryFactory(new AccessFileHistoryDao())
            );
        }

        return $this->repository_manager;
    }

    /** @return Tuleap\Svn\AccessControl\AccessFileHistoryDao */
    private function getAccessFileHistoryDao(){
        if(empty($this->accessfile_dao)){
            $this->accessfile_dao = new AccessFileHistoryDao();
        }
        return $this->accessfile_dao;
    }

    /** @return Tuleap\Svn\AccessControl\AccessFileHistoryFactory */
    private function getAccessFileHistoryFactory(){
        if(empty($this->accessfile_factory)){
            $this->accessfile_factory = new AccessFileHistoryFactory($this->getAccessFileHistoryDao());
        }
        return $this->accessfile_factory;
    }

    /** @return Tuleap\Svn\AccessControl\AccessFileHistoryCreator */
    private function getAccessFileHistoryCreator()
    {
        if (empty($this->accessfile_history_manager)) {
            $this->accessfile_history_creator = new AccessFileHistoryCreator(
                $this->getAccessFileHistoryDao(),
                $this->getAccessFileHistoryFactory(),
                $this->getProjectHistoryDao(),
                $this->getProjectHistoryFormatter()
            );
        }

        return $this->accessfile_history_creator;
    }

    /** @return Tuleap\Svn\Admin\MailNotificationManager */
    private function getMailNotificationManager() {
        if (empty($this->mail_notification_manager)) {
            $this->mail_notification_manager = new MailNotificationManager(
                $this->getMailNotificationDao(),
                $this->getUserNotifyDao(),
                $this->getUGroupNotifyDao(),
                $this->getProjectHistoryDao(),
                $this->getNotificationEmailsBuilder(),
                $this->getUGroupManager()
            );
        }
        return $this->mail_notification_manager;
    }

    /**
     * @return MailNotificationDao
     */
    private function getMailNotificationDao()
    {
        return new MailNotificationDao(CodendiDataAccess::instance(), new RepositoryRegexpBuilder());
    }

    /**
     * @return UGroupManager
     */
    private function getUGroupManager()
    {
        if (empty($this->ugroup_manager)) {
            $this->ugroup_manager = new UGroupManager();
        }
        return $this->ugroup_manager;
    }

    /**
     * @return SvnPermissionManager
     */
    private function getPermissionsManager()
    {
        if (empty($this->permissions_manager)) {
            $this->permissions_manager = new SvnPermissionManager($this->getForgeUserGroupFactory(), PermissionsManager::instance());
        }
        return $this->permissions_manager;
    }

    private function getForgeUserGroupFactory()
    {
        return new User_ForgeUserGroupFactory(new UserGroupDao());
    }

    /**
     * @return ProjectManager
     */
    private function getProjectManager()
    {
        return ProjectManager::instance();

    }

    public function process(HTTPRequest $request)
    {
        $project = $request->getProject();
        if (! $project->getID()) {
            $project = $this->getProjectFromViewVcURL($request);
        }

        if (! $project) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'include_group', 'g_not_found'
                )
            );

            $this->redirectToHomepage();
        } elseif ($project->isDeleted()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'include_exit', 'project_status_' . $project->getStatus()
                )
            );

            $this->redirectToHomepage();
        }

        $project_id = $project->getId();
        $request->set('group_id', $project_id);

        if (! PluginManager::instance()->isPluginAllowedForProject($this, $project_id)) {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText(
                    'plugin_svn_manage_repository', 'plugin_not_activated'
                 )
            );
            $GLOBALS['Response']->redirect('/projects/' . $project->getUnixNameMixedCase() . '/');
        } else {
            $this->getRouter()->route($request);
        }
    }

    private function redirectToHomepage()
    {
        $GLOBALS['Response']->redirect('/');
    }

    private function getProjectFromViewVcURL(HTTPRequest $request)
    {
        $svn_root          = $request->get('root');
        $project_shortname = substr($svn_root, 0, strpos($svn_root, '/'));
        $project = ProjectManager::instance()->getProjectByCaseInsensitiveUnixName($project_shortname);

        return $project;
    }

    public function cssFile($params) {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />';
        }
    }

    public function javascript_file() {
        // Only show the javascript if we're actually in the svn pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            echo '<script type="text/javascript" src="'.$this->getPluginPath().'/scripts/svn.js"></script>';
        }
        if ($this->currentRequestIsForPlugin() || $this->currentRequestIsForDashboards()) {
            echo $this->getMinifiedAssetHTML().PHP_EOL;
        }
        $GLOBALS['Response']->includeFooterJavascriptFile('/scripts/tuleap/user-and-ugroup-autocompleter.js');
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e804';
    }

    public function service_classnames(array $params) {
        $params['classnames'][$this->getServiceShortname()] = 'Tuleap\Svn\ServiceSvn';
    }

    /**
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function import_xml_project($params)
    {
        $xml             = $params['xml_content'];
        $extraction_path = $params['extraction_path'];
        $project         = $params['project'];
        $logger          = $params['logger'];

        $user_manager    = $this->getUserManager();

        $svn = new XMLImporter(
            $xml,
            $extraction_path,
            $this->getRepositoryCreator(),
            $this->getBackendSVN(),
            $this->getBackendSystem(),
            $this->getAccessFileHistoryCreator(),
            $this->getRepositoryManager(),
            $user_manager,
            $this->getNotificationEmailsBuilder(),
            $this->getCopier()
        );
        $svn->import(
            $params['configuration'],
            $logger,
            $project,
            $this->getAccessFileHistoryCreator(),
            $this->getMailNotificationManager(),
            new RuleName($project, new Dao()),
            $user_manager->getCurrentUser()
        );
    }

    private function getRouter()
    {
        $repository_manager  = $this->getRepositoryManager();
        $ugroup_manager      = $this->getUGroupManager();
        $permissions_manager = $this->getPermissionsManager();

        $history_dao         = $this->getProjectHistoryDao();
        $hook_config_updator = new HookConfigUpdator(
            new HookDao(),
            $history_dao,
            new HookConfigChecker($this->getHookConfigRetriever()),
            $this->getHookConfigSanitizer(),
            $this->getProjectHistoryFormatter()
        );

        return new SvnRouter(
            $repository_manager,
            $ugroup_manager,
            $permissions_manager,
            new AccessControlController(
                $repository_manager,
                $this->getAccessFileHistoryFactory(),
                $this->getAccessFileHistoryCreator()
            ),
            new AdminController(
                new MailHeaderManager(new MailHeaderDao()),
                $repository_manager,
                $this->getMailNotificationManager(),
                new SvnLogger(),
                new NotificationListBuilder(
                    new UGroupDao(),
                    new CollectionOfUserToBeNotifiedPresenterBuilder($this->getUserNotifyDao()),
                    new CollectionOfUgroupToBeNotifiedPresenterBuilder($this->getUGroupNotifyDao())
                ),
                $this->getNotificationEmailsBuilder(),
                $this->getUserManager(),
                new UGroupManager(),
                $hook_config_updator,
                $this->getHookConfigRetriever(),
                $this->getRepositoryDeleter()
            ),
            new ExplorerController(
                $repository_manager,
                $permissions_manager,
                new RepositoryBuilder(),
                $this->getRepositoryCreator()
            ),
            new RepositoryDisplayController(
                $repository_manager,
                $permissions_manager,
                new ViewVCProxy(
                    $repository_manager,
                    ProjectManager::instance(),
                    new AccessHistorySaver(new AccessHistoryDao()),
                    EventManager::instance()
                ),
                EventManager::instance()
            ),
            new ImmutableTagController(
                $repository_manager,
                new Svnlook(new System_Command()),
                $this->getImmutableTagCreator(),
                $this->getImmutableTagFactory()
            ),
            new GlobalAdminController(
                $this->getForgeUserGroupFactory(),
                $permissions_manager
            ),
            new RestoreController($this->getRepositoryManager()),
            new SVNJSONPermissionsRetriever(
                new PermissionPerGroupRepositoryRepresentationBuilder(
                    $this->getRepositoryManager(),
                    $this->getUGroupManager()
                )
            )
        );
    }

    /** @return BackendSVN */
    private function getBackendSVN() {
        return Backend::instance(Backend::SVN);
    }

    public function get_reference($params) {
        $keyword = $params['keyword'];

        if ($this->isReferenceASubversionReference($keyword)) {
            $project = $params['project'];
            $value   = $params['value'];

            $extractor = $this->getReferenceExtractor();
            $reference = $extractor->getReference($project, $keyword, $value);

            if ($reference) {
                $params['reference'] = $reference;
            }
        }

    }

    private function getReferenceExtractor() {
        return new Extractor($this->getRepositoryManager());
    }

    private function isReferenceASubversionReference($keyword) {
        $dao    = new ReferenceDao();
        $result = $dao->searchSystemReferenceByNatureAndKeyword($keyword, self::SYSTEM_NATURE_NAME);

        if (! $result || $result->rowCount() < 1) {
            return false;
        }

        return true;
    }

    public function svn_repository_created($params)
    {
        $backend           = Backend::instance();
        $svn_plugin_folder = ForgeConfig::get('sys_data_dir') .'/svn_plugin/';
        $project_id        = $params['project_id'];

        $backend->chown($svn_plugin_folder, $backend->getHTTPUser());
        $backend->chgrp($svn_plugin_folder, $backend->getHTTPUser());

        $svn_project_folder = $svn_plugin_folder . $project_id;

        $backend->chown($svn_project_folder, $backend->getHTTPUser());
        $backend->chgrp($svn_project_folder, $backend->getHTTPUser());
    }

    public function project_is_deleted($params)
    {
        if (! empty($params['group_id'])) {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            if ($project) {
                $this->getRepositoryDeleter()->deleteProjectRepositories($project);
            }
        }
    }

    public function codendi_daily_start()
    {
        $this->getRepositoryManager()->purgeArchivedRepositories();
    }

    public function show_pending_documents($params)
    {
        $project_id            = $params['group_id'];
        $project               = ProjectManager::instance()->getProject($project_id);
        $archived_repositories = $this->getRepositoryManager()->getRestorableRepositoriesByProject($project);

        $restore_controller = new RestoreController($this->getRepositoryManager());
        $tab_content        = $restore_controller->displayRestorableRepositories($params['csrf_token'], $archived_repositories, $project_id);
        $params['html'][]   = $tab_content;
    }

    public function logs_daily($params)
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['group_id']);
        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $builder = new QueryBuilder();
            $query  = $builder->buildQuery($project, $params['span'], $params['who']);

             $params['logs'][] = array(
                'sql'   => $query,
                'field' => $GLOBALS['Language']->getText('plugin_svn', 'logsdaily_field'),
                'title' => $GLOBALS['Language']->getText('plugin_svn', 'logsdaily_title')
            );
        }
    }

    public function statistics_collector(array $params)
    {
        if (! empty($params['formatter']))
        {
            $statistic_dao       = new \Tuleap\Svn\Statistic\SCMUsageDao();
            $statistic_collector = new \Tuleap\Svn\Statistic\SCMUsageCollector($statistic_dao);

            echo $statistic_collector->collect($params['formatter']);
        }
    }

    public function plugin_statistics_service_usage(array $params)
    {
        $statistic_dao       = new \Tuleap\Svn\Statistic\ServiceUsageDao();
        $statistic_collector = new \Tuleap\Svn\Statistic\ServiceUsageCollector($statistic_dao);
        $statistic_collector->collect($params['csv_exporter'], $params['start_date'], $params['end_date']);
    }

    public function project_creation_remove_legacy_services($params)
    {
        if (! $this->isRestricted()) {
            $this->getServiceActivator()->unuseLegacyService($params);
        }
    }

    public function systemEventProjectRename(array $params)
    {
        $project            = $params['project'];
        $repository_manager = $this->getRepositoryManager();
        $repositories       = $repository_manager->getRepositoriesInProject($project);

        if (count($repositories) > 0) {
            $this->getBackendSVN()->setSVNApacheConfNeedUpdate();
        }
    }

    /** @see Event::PROJECT_ACCESS_CHANGE */
    public function project_access_change(array $params)
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateProjectAccess($params['project_id'], $params['old_access'], $params['access']);
    }

    /** @see Event::SITE_ACCESS_CHANGE */
    public function site_access_change(array $params)
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateSiteAccess($params['old_value']);
    }

    /**
     * @return UgroupsToNotifyUpdater
     */
    private function getUgroupToNotifyUpdater()
    {
        return new UgroupsToNotifyUpdater($this->getUGroupNotifyDao());
    }

    public function project_admin_remove_user(array $params)
    {
        $project_id = $params['group_id'];
        $user_id    = $params['user_id'];

        $project = ProjectManager::instance()->getProject($project_id);
        $user    = $this->getUserManager()->getUserById($user_id);

        $notifications_for_project_member_cleaner = new NotificationsForProjectMemberCleaner(
            $this->getUserNotifyDao(),
            $this->getMailNotificationDao()
        );
        $notifications_for_project_member_cleaner->cleanNotificationsAfterUserRemoval($project, $user);
    }

    public function project_admin_ugroup_deletion($params)
    {
        $project_id = $params['group_id'];
        $ugroup     = $params['ugroup'];

        $ugroups_to_notify_dao = $this->getUGroupNotifyDao();
        $ugroups_to_notify_dao->deleteByUgroupId($project_id, $ugroup->getId());
        $this->getMailNotificationDao()->deleteEmptyNotificationsInProject($project_id);
    }

    /**
     * @param array $params
     */
    public function plugin_statistics_disk_usage_collect_project(array $params)
    {
        $start   = microtime(true);
        $project = $params['project'];

        $this->getCollector()->collectDiskUsageForProject($project);

        $end  = microtime(true);
        $time = $end - $start;

        if (! isset($params['time_to_collect'][self::SERVICE_SHORTNAME])) {
            $params['time_to_collect'][self::SERVICE_SHORTNAME] = 0;
        }

        $params['time_to_collect'][self::SERVICE_SHORTNAME] += $time;
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_service_label($params)
    {
        $params['services'][self::SERVICE_SHORTNAME] = dgettext('tuleap-svn', 'Multi SVN');
    }

    /**
     * Hook to choose the color of the plugin in the graph
     *
     * @param array $params
     */
    public function plugin_statistics_color($params)
    {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'forestgreen';
        }
    }

    /**
     * @return DiskUsageRetriever
     */
    private function getRetriever()
    {

        $disk_usage_dao  = new Statistics_DiskUsageDao();
        $svn_log_dao     = new SVN_LogDao();
        $svn_retriever   = new SVNRetriever($disk_usage_dao);
        $svn_collector   = new SVNCollector($svn_log_dao, $svn_retriever);
        $cvs_history_dao = new FullHistoryDao();
        $cvs_retriever   = new CVSRetriever($disk_usage_dao);
        $cvs_collector   = new CVSCollector($cvs_history_dao, $cvs_retriever);

        $disk_usage_manager = new Statistics_DiskUsageManager(
            $disk_usage_dao,
            $svn_collector,
            $cvs_collector,
            EventManager::instance()
        );

        return new DiskUsageRetriever(
            $this->getRepositoryManager(),
            $disk_usage_manager,
            new DiskUsageDao(),
            new Statistics_DiskUsageDao(),
            new SvnLogger()
        );
    }

    /**
     * @return DiskUsageCollector
     */
    private function getCollector()
    {
        return new DiskUsageCollector($this->getRetriever(), new Statistics_DiskUsageDao());
    }

    public function rest_resources($params)
    {
        $injector = new \Tuleap\SVN\REST\ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params)
    {
        $injector = new \Tuleap\SVN\REST\ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    public function rest_project_get_svn(ProjectGetSvn $event) {
        $event->setPluginActivated();

        $class            = "Tuleap\\SVN\\REST\\".$event->getVersion()."\\ProjectResource";
        $project_resource = new $class($this->getRepositoryManager());
        $project          = $event->getProject();

        $collection = $project_resource->getRepositoryCollection(
            $project,
            $event->getFilter(),
            $event->getLimit(),
            $event->getOffset()
        );

        $event->addRepositoriesRepresentations($collection->getRepositoriesRepresentations());
        $event->addTotalRepositories($collection->getTotalSize());
    }

    public function rest_project_options_svn(ProjectOptionsSvn $event)
    {
        $event->setPluginActivated();
    }

    /**
     * @return RepositoryCreator
     */
    private function getRepositoryCreator()
    {
        return new RepositoryCreator(
            new Dao(),
            SystemEventManager::instance(),
            $this->getProjectHistoryDao(),
            $this->getPermissionsManager(),
            new HookConfigUpdator(
                new HookDao(),
                $this->getProjectHistoryDao(),
                new HookConfigChecker($this->getHookConfigRetriever()),
                $this->getHookConfigSanitizer(),
                $this->getProjectHistoryFormatter()
            ),
            $this->getProjectHistoryFormatter(),
            $this->getImmutableTagCreator(),
            $this->getAccessFileHistoryCreator(),
            $this->getMailNotificationManager()
        );
    }

    /**
     * @return HookConfigSanitizer
     */
    private function getHookConfigSanitizer()
    {
        return new HookConfigSanitizer();
    }

    /**
     * @return HookConfigRetriever
     */
    private function getHookConfigRetriever()
    {
        return new HookConfigRetriever(new HookDao(), $this->getHookConfigSanitizer());
    }

    /**
     * @return \Tuleap\Svn\Repository\RepositoryDeleter
     */
    private function getRepositoryDeleter()
    {
        return new \Tuleap\Svn\Repository\RepositoryDeleter(
            new System_Command(),
            $this->getProjectHistoryDao(),
            new Dao(),
            SystemEventManager::instance(),
            $this->getRepositoryManager()
        );
    }

    public function project_registration_activate_service(ProjectRegistrationActivateService $event)
    {
        $this->getServiceActivator()->forceUsageOfService($event->getProject(), $event->getTemplate(), $event->getLegacy());
    }

    /**
     * @return ServiceActivator
     */
    private function getServiceActivator()
    {
        return new ServiceActivator(ServiceManager::instance(), new ServiceCreator());
    }

    /**
     * @return ImmutableTagCreator
     */
    private function getImmutableTagCreator()
    {
        return new ImmutableTagCreator(
            new ImmutableTagDao(),
            $this->getProjectHistoryFormatter(),
            $this->getProjectHistoryDao(),
            $this->getImmutableTagFactory()
        );
    }

    /**
     * @return Backend
     */
    private function getBackendSystem()
    {
        return Backend::instance('System');
    }

    /**
     * @return ProjectHistoryDao
     */
    private function getProjectHistoryDao()
    {
        return new ProjectHistoryDao();
    }

    /**
     * @return ProjectHistoryFormatter
     */
    private function getProjectHistoryFormatter()
    {
        return new ProjectHistoryFormatter();
    }

    /**
     * @return ImmutableTagFactory
     */
    private function getImmutableTagFactory()
    {
        return new ImmutableTagFactory(new ImmutableTagDao());
    }

    /**
     * @return NotificationsEmailsBuilder
     */
    private function getNotificationEmailsBuilder()
    {
        return new NotificationsEmailsBuilder();
    }

    /**
     * @return UserManager
     */
    private function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * @return UsersToNotifyDao
     */
    private function getUserNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    /**
     * @return UgroupsToNotifyDao
     */
    private function getUGroupNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(NavigationDropdownQuickLinksCollector $quick_links_collector)
    {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-svn', 'SVN'),
                $this->getPluginPath() . '/?' . http_build_query(
                    array(
                        'group_id' => $project->getID(),
                        'action'   => 'admin-groups'
                    )
                )
            )
        );
    }

    /**
     * @return System_Command
     */
    private function getSystemCommand()
    {
        return new System_Command();
    }

    /**
     * @return RepositoryCopier
     */
    private function getCopier()
    {
        return new RepositoryCopier($this->getSystemCommand());
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager = new UGroupManager();

        $service_pane_builder = new PermissionPerGroupSVNServicePaneBuilder(
            new PermissionPerGroupUGroupRetriever(PermissionsManager::instance()),
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            $ugroup_manager
        );

        $collector = new PaneCollector($this->getUGroupManager(), $service_pane_builder);
        $collector->collectPane($event);
    }

    /**
     * @see Event:BURNING_PARROT_GET_STYLESHEETS
     */
    public function burningParrotGetStylesheets(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/project/admin/permission_per_group') === 0) {
            $theme_include_assets = new IncludeAssets(
                SVN_BASE_DIR . '/../www/themes/BurningParrot/assets',
                $this->getThemePath() . '/assets'
            );
            $variant = $params['variant'];
            $params['stylesheets'][] = $theme_include_assets->getFileURL('style-' . $variant->getName() . '.css');
        }
    }


    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event)
    {
        $include_assets = new IncludeAssets(
            SVN_BASE_DIR . '/../www/assets',
            $this->getPluginPath() . '/assets'
        );

        $event->addJavascript($include_assets->getFileURL('permission-per-group.js'));
    }

    public function httpdPostRotate(PostRotateEvent $event)
    {
        DBWriter::build($event->getLogger())->postrotate();
    }
}
