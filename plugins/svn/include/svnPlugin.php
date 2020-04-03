<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\Retriever as CVSRetriever;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\Collector as CVSCollector;
use Tuleap\Statistics\DiskUsage\ConcurrentVersionsSystem\FullHistoryDao;
use Tuleap\Error\ProjectAccessSuspendedController;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Httpd\PostRotateEvent;
use Tuleap\layout\HomePage\LastMonthStatisticsCollectorSVN;
use Tuleap\layout\HomePage\StatisticsCollectorSVN;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\Project\Event\ProjectRegistrationActivateService;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\REST\Event\ProjectGetSvn;
use Tuleap\REST\Event\ProjectOptionsSvn;
use Tuleap\Service\ServiceCreator;
use Tuleap\SVN\AccessControl\AccessControlController;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\AccessControl\AccessFileReader;
use Tuleap\SVN\AccessControl\SVNRefreshAllAccessFilesCommand;
use Tuleap\SVN\Admin\AdminController;
use Tuleap\SVN\Admin\GlobalAdminController;
use Tuleap\SVN\Admin\ImmutableTagController;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailHeaderDao;
use Tuleap\SVN\Admin\MailHeaderManager;
use Tuleap\SVN\Admin\MailNotificationDao;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Admin\RestoreController;
use Tuleap\Svn\ApacheConfGenerator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Dao;
use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\SVN\DiskUsage\DiskUsageCollector;
use Tuleap\SVN\DiskUsage\DiskUsageDao;
use Tuleap\SVN\DiskUsage\DiskUsageRetriever;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;
use Tuleap\svn\Event\UpdateProjectAccessFilesEvent;
use Tuleap\SVN\Events\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\SVN\Events\SystemEvent_SVN_DELETE_REPOSITORY;
use Tuleap\SVN\Events\SystemEvent_SVN_RESTORE_REPOSITORY;
use Tuleap\SVN\Explorer\ExplorerController;
use Tuleap\SVN\Explorer\RepositoryBuilder;
use Tuleap\SVN\Explorer\RepositoryDisplayController;
use Tuleap\SVN\Logs\DBWriter;
use Tuleap\SVN\Logs\QueryBuilder;
use Tuleap\SVN\Migration\RepositoryCopier;
use Tuleap\SVN\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\SVN\Notifications\CollectionOfUserToBeNotifiedPresenterBuilder;
use Tuleap\SVN\Notifications\NotificationListBuilder;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\SVN\Notifications\UgroupsToNotifyDao;
use Tuleap\SVN\Notifications\UgroupsToNotifyUpdater;
use Tuleap\SVN\Notifications\UsersToNotifyDao;
use Tuleap\SVN\PermissionsPerGroup\PaneCollector;
use Tuleap\SVN\PermissionsPerGroup\PermissionPerGroupRepositoryRepresentationBuilder;
use Tuleap\SVN\PermissionsPerGroup\PermissionPerGroupSVNServicePaneBuilder;
use Tuleap\SVN\PermissionsPerGroup\SVNJSONPermissionsRetriever;
use Tuleap\SVN\RedirectOldViewVCUrls;
use Tuleap\SVN\Reference\Extractor;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Repository\HookConfigChecker;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\HookConfigSanitizer;
use Tuleap\SVN\Repository\HookConfigUpdator;
use Tuleap\SVN\Repository\HookDao;
use Tuleap\SVN\Repository\ProjectHistoryFormatter;
use Tuleap\SVN\Repository\RepositoryCreator;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\Repository\RepositoryRegexpBuilder;
use Tuleap\SVN\Repository\RuleName;
use Tuleap\SVN\Service\ServiceActivator;
use Tuleap\SVN\SvnAdmin;
use Tuleap\SVN\SvnPermissionManager;
use Tuleap\SVN\SvnRouter;
use Tuleap\SVN\ViewVC\AccessHistoryDao;
use Tuleap\SVN\ViewVC\AccessHistorySaver;
use Tuleap\SVN\ViewVC\ViewVCProxy;
use Tuleap\SVN\XMLImporter;
use Tuleap\SVN\XMLSvnExporter;

/**
 * SVN plugin
 */
class SvnPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const SERVICE_SHORTNAME  = 'plugin_svn';
    public const SYSTEM_NATURE_NAME = 'svn_revision';

    /** @var Tuleap\SVN\Repository\RepositoryManager */
    private $repository_manager;

    /** @var Tuleap\SVN\AccessControl\AccessFileHistoryDao */
    private $accessfile_dao;

    /** @var Tuleap\SVN\AccessControl\AccessFileHistoryFactory */
    private $accessfile_factory;

    /** @var Tuleap\SVN\AccessControl\AccessFileHistoryCreator */
    private $accessfile_history_creator;

    /** @var Tuleap\SVN\Admin\MailNotificationManager */
    private $mail_notification_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var SvnPermissionManager */
    private $permissions_manager;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(Plugin::SCOPE_PROJECT);
        bindtextdomain('tuleap-svn', __DIR__ . '/../site-content');

        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::GET_SVN_LIST_REPOSITORIES_SQL_FRAGMENTS);
        $this->addHook(Event::UGROUP_RENAME);
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
        $this->addHook(SystemEvent_PROJECT_IS_PRIVATE::class, 'changeProjectRepositoriesAccess');
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::SVN_REPOSITORY_CREATED);
        $this->addHook(ProjectCreator::PROJECT_CREATION_REMOVE_LEGACY_SERVICES);
        $this->addHook(ExportXmlProject::NAME);
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);
        $this->addHook(CLICommandsCollector::NAME);

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

        $this->addHook(CollectRoutesEvent::NAME);
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(StatisticsCollectorSVN::NAME);
        $this->addHook(LastMonthStatisticsCollectorSVN::NAME);
        $this->addHook(\Tuleap\svn\Event\UpdateProjectAccessFilesEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public static function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger('svn_syslog');
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
        if (! isset($event->getOptions()['all']) || $event->getOptions()['all'] === false) {
            return;
        }

        $this->getSvnExporter($event->getProject())->exportToXml(
            $event->getIntoXml(),
            $event->getArchive(),
            $event->getTemporaryDumpPathOnFilesystem()
        );
    }

    private function getSvnExporter(Project $project): XMLSvnExporter
    {
        return new XMLSvnExporter(
            $this->getRepositoryManager(),
            $project,
            new SvnAdmin(new System_Command(), \SvnPlugin::getLogger(), Backend::instance(Backend::SVN)),
            new XML_SimpleXMLCDATAFactory(),
            $this->getMailNotificationManager(),
            \SvnPlugin::getLogger(),
            new AccessFileReader()
        );
    }

    public function getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'SvnPluginInfo')) {
            $this->pluginInfo = new SvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname()
    {
        return self::SERVICE_SHORTNAME;
    }

    public function getTypes()
    {
        return array(
            SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            SystemEvent_SVN_DELETE_REPOSITORY::NAME,
            SystemEvent_SVN_RESTORE_REPOSITORY::NAME
        );
    }

    public function collectCLICommands(CLICommandsCollector $commands_collector): void
    {
        $commands_collector->addCommand(
            SVNRefreshAllAccessFilesCommand::NAME,
            function (): SVNRefreshAllAccessFilesCommand {
                return new SVNRefreshAllAccessFilesCommand(
                    $this->getRepositoryManager(),
                    $this->getAccessFileHistoryFactory(),
                    $this->getAccessFileHistoryCreator()
                );
            }
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

    /** @see Event::UGROUP_RENAME */
    public function ugroupRename(array $params): void
    {
        $project         = $params['project'];

        $this->updateAllAccessFileOfProject($project, $params['new_ugroup_name'], $params['old_ugroup_name']);
    }

    /** @see SystemEvent_PROJECT_IS_PRIVATE */
    public function changeProjectRepositoriesAccess(array $params)
    {
        $project_id = $params[0];
        $project = ProjectManager::instance()->getProject($project_id);

        $this->updateAllAccessFileOfProject($project, null, null);
    }

    public function systemevent_user_rename(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $new_ugroup_name = null;
        $old_ugroup_name = null;
        $user            = $params['user'];

        $projects = $this->getProjectManager()->getAllProjectsForUserIncludingTheOnesSheDoesNotHaveAccessTo($user);

        foreach ($projects as $project) {
            $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
        }
    }

    public function updateProjectAccessFiles(UpdateProjectAccessFilesEvent $event): void
    {
        $this->updateAllAccessFileOfProject($event->getProject(), null, null);
    }

    private function updateAllAccessFileOfProject(Project $project, $new_ugroup_name, $old_ugroup_name)
    {
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

    public function get_svn_list_repositories_sql_fragments(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $dao = new Dao();
        $params['sql_fragments'][] = $dao->getListRepositoriesSqlFragment();
    }

    public function system_event_get_types_for_default_queue($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['types'][] = 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_DELETE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_RESTORE_REPOSITORY::NAME;
    }

    public function get_system_event_class($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        switch ($params['type']) {
            case 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME:
                $params['class'] = SystemEvent_SVN_CREATE_REPOSITORY::class;
                $params['dependencies'] = array(
                    $this->getAccessFileHistoryCreator(),
                    $this->getRepositoryManager(),
                    $this->getUserManager(),
                    $this->getBackendSVN(),
                    $this->getBackendSystem(),
                    $this->getCopier()
                );
                break;
            case 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_DELETE_REPOSITORY::NAME:
                $params['class'] = SystemEvent_SVN_DELETE_REPOSITORY::class;
                $params['dependencies'] = array(
                    $this->getRepositoryManager(),
                    ProjectManager::instance(),
                    $this->getApacheConfGenerator(),
                    $this->getRepositoryDeleter(),
                    new SvnAdmin(new System_Command(), \SvnPlugin::getLogger(), Backend::instance(Backend::SVN))
                );
                break;
        }
    }

    private function getApacheConfGenerator()
    {
        return ApacheConfGenerator::build();
    }

    /** @return Tuleap\SVN\Repository\RepositoryManager */
    private function getRepositoryManager()
    {
        if (empty($this->repository_manager)) {
            $this->repository_manager = new RepositoryManager(
                new Dao(),
                ProjectManager::instance(),
                new SvnAdmin(new System_Command(), \SvnPlugin::getLogger(), Backend::instance(Backend::SVN)),
                \SvnPlugin::getLogger(),
                new System_Command(),
                new Destructor(
                    new Dao(),
                    \SvnPlugin::getLogger()
                ),
                EventManager::instance(),
                Backend::instance(Backend::SVN),
                new AccessFileHistoryFactory(new AccessFileHistoryDao())
            );
        }

        return $this->repository_manager;
    }

    /** @return Tuleap\SVN\AccessControl\AccessFileHistoryDao */
    private function getAccessFileHistoryDao()
    {
        if (empty($this->accessfile_dao)) {
            $this->accessfile_dao = new AccessFileHistoryDao();
        }
        return $this->accessfile_dao;
    }

    /** @return Tuleap\SVN\AccessControl\AccessFileHistoryFactory */
    private function getAccessFileHistoryFactory()
    {
        if (empty($this->accessfile_factory)) {
            $this->accessfile_factory = new AccessFileHistoryFactory($this->getAccessFileHistoryDao());
        }
        return $this->accessfile_factory;
    }

    /** @return Tuleap\SVN\AccessControl\AccessFileHistoryCreator */
    private function getAccessFileHistoryCreator()
    {
        if (empty($this->accessfile_history_manager)) {
            $this->accessfile_history_creator = new AccessFileHistoryCreator(
                $this->getAccessFileHistoryDao(),
                $this->getAccessFileHistoryFactory(),
                $this->getProjectHistoryDao(),
                $this->getProjectHistoryFormatter(),
                $this->getBackendSVN()
            );
        }

        return $this->accessfile_history_creator;
    }

    /** @return Tuleap\SVN\Admin\MailNotificationManager */
    private function getMailNotificationManager()
    {
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
            $this->permissions_manager = new SvnPermissionManager(PermissionsManager::instance());
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

    public function cssFile($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $assets = $this->getIncludeAssets();
            echo '<link rel="stylesheet" type="text/css" href="' . $assets->getFileURL('style-fp.css') . '" />';
        }
    }

    public function javascript_file() // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        // Only show the javascript if we're actually in the svn pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $script_url = $this->getIncludeAssets()->getFileURL('svn.js');
            echo '<script type="text/javascript" src="' . $script_url . '"></script>';
        }
        if ($this->currentRequestIsForPlugin()) {
            $script_url = $this->getIncludeAssets()->getFileURL('svn-admin.js');
            echo '<script type="text/javascript" src="' . $script_url . '"></script>';
        }
    }

    public function service_classnames(array &$params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['classnames'][$this->getServiceShortname()] = \Tuleap\SVN\ServiceSvn::class;
    }

    /**
     * @param array $params
     * @see Event::IMPORT_XML_PROJECT
     */
    public function import_xml_project($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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
            $this->getCopier(),
            new \Tuleap\SVN\XMLUserChecker()
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

    public function routeSvnPlugin(): DispatchableWithRequest
    {
        $repository_manager  = $this->getRepositoryManager();
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
                \SvnPlugin::getLogger(),
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
                    EventManager::instance(),
                    new ProjectAccessSuspendedController(
                        new ThemeManager(
                            new BurningParrotCompatiblePageDetector(
                                new Tuleap\Request\CurrentPage(),
                                new \User_ForgeUserGroupPermissionsManager(
                                    new \User_ForgeUserGroupPermissionsDao()
                                )
                            )
                        )
                    )
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
                    $this->getRepositoryManager()
                )
            ),
            $this,
            ProjectManager::instance()
        );
    }

    public function redirectOldViewVcRoutes(): DispatchableWithRequest
    {
        return new RedirectOldViewVCUrls($this->getPluginPath());
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->get('/index.php{path:.*}', $this->getRouteHandler('redirectOldViewVcRoutes'));
            $r->addRoute(['GET', 'POST'], '[/{path:.*}]', $this->getRouteHandler('routeSvnPlugin'));
        });
    }

    /** @return BackendSVN */
    private function getBackendSVN()
    {
        return Backend::instance(Backend::SVN);
    }

    public function get_reference($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
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

    private function getReferenceExtractor()
    {
        return new Extractor($this->getRepositoryManager());
    }

    private function isReferenceASubversionReference($keyword)
    {
        $dao    = new ReferenceDao();
        $result = $dao->searchSystemReferenceByNatureAndKeyword($keyword, self::SYSTEM_NATURE_NAME);

        if (! $result || $result->rowCount() < 1) {
            return false;
        }

        return true;
    }

    public function svn_repository_created($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $backend           = Backend::instance();
        $svn_plugin_folder = ForgeConfig::get('sys_data_dir') . '/svn_plugin/';
        $project_id        = $params['project_id'];

        $backend->chown($svn_plugin_folder, $backend->getHTTPUser());
        $backend->chgrp($svn_plugin_folder, $backend->getHTTPUser());

        $svn_project_folder = $svn_plugin_folder . $project_id;

        $backend->chown($svn_project_folder, $backend->getHTTPUser());
        $backend->chgrp($svn_project_folder, $backend->getHTTPUser());
    }

    public function project_is_deleted($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if (! empty($params['group_id'])) {
            $project = ProjectManager::instance()->getProject($params['group_id']);
            if ($project) {
                $this->getRepositoryDeleter()->deleteProjectRepositories($project);
            }
        }
    }

    public function codendi_daily_start() // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $this->getRepositoryManager()->purgeArchivedRepositories();
    }

    public function show_pending_documents($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project_id            = $params['group_id'];
        $project               = ProjectManager::instance()->getProject($project_id);
        $archived_repositories = $this->getRepositoryManager()->getRestorableRepositoriesByProject($project);

        $restore_controller = new RestoreController($this->getRepositoryManager());
        $tab_content        = $restore_controller->displayRestorableRepositories($params['csrf_token'], $archived_repositories, $project_id);
        $params['html'][]   = $tab_content;
    }

    public function logs_daily($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['group_id']);
        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $builder = new QueryBuilder();
            $query  = $builder->buildQuery($project, $params['span'], $params['who']);

             $params['logs'][] = array(
                'sql'   => $query,
                'field' => dgettext('tuleap-svn', 'Repository name'),
                'title' => dgettext('tuleap-svn', 'SVN')
             );
        }
    }

    public function statistics_collector(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        if (! empty($params['formatter'])) {
            $statistic_dao       = new \Tuleap\SVN\Statistic\SCMUsageDao();
            $statistic_collector = new \Tuleap\SVN\Statistic\SCMUsageCollector($statistic_dao);

            echo $statistic_collector->collect($params['formatter']);
        }
    }

    public function plugin_statistics_service_usage(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $statistic_dao       = new \Tuleap\SVN\Statistic\ServiceUsageDao();
        $statistic_collector = new \Tuleap\SVN\Statistic\ServiceUsageCollector($statistic_dao);
        $statistic_collector->collect($params['csv_exporter'], $params['start_date'], $params['end_date']);
    }

    public function project_creation_remove_legacy_services($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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
    public function projectAccessChange(array $params): void
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateProjectAccess($params['project_id'], $params['old_access'], $params['access']);
    }

    /** @see Event::SITE_ACCESS_CHANGE */
    public function site_access_change(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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

    public function project_admin_remove_user(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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

    public function project_admin_ugroup_deletion($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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
    public function plugin_statistics_disk_usage_collect_project(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $start   = microtime(true);
        $project = $params['project'];

        $this->getCollector()->collectDiskUsageForProject($project, $params['collect_date']);

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
    public function plugin_statistics_disk_usage_service_label($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $params['services'][self::SERVICE_SHORTNAME] = dgettext('tuleap-svn', 'Multi SVN');
    }

    /**
     * Hook to choose the color of the plugin in the graph
     *
     * @param array $params
     */
    public function plugin_statistics_color($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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
            \SvnPlugin::getLogger()
        );
    }

    /**
     * @return DiskUsageCollector
     */
    private function getCollector()
    {
        return new DiskUsageCollector($this->getRetriever(), new Statistics_DiskUsageDao());
    }

    public function rest_resources($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $injector = new \Tuleap\SVN\REST\ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $injector = new \Tuleap\SVN\REST\ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    public function rest_project_get_svn(ProjectGetSvn $event) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $event->setPluginActivated();

        $class            = "Tuleap\\SVN\\REST\\" . $event->getVersion() . "\\ProjectResource";
        if (! class_exists($class)) {
            throw new LogicException("$class does not exist");
        }
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

    public function rest_project_options_svn(ProjectOptionsSvn $event) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
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
     * @return \Tuleap\SVN\Repository\RepositoryDeleter
     */
    private function getRepositoryDeleter()
    {
        return new \Tuleap\SVN\Repository\RepositoryDeleter(
            new System_Command(),
            $this->getProjectHistoryDao(),
            new Dao(),
            SystemEventManager::instance(),
            $this->getRepositoryManager()
        );
    }

    public function project_registration_activate_service(ProjectRegistrationActivateService $event) // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $this->getServiceActivator()->forceUsageOfService($event->getProject(), $event->getTemplate(), $event->getLegacy());
    }

    /**
     * @return ServiceActivator
     */
    private function getServiceActivator()
    {
        return new ServiceActivator(ServiceManager::instance(), new ServiceCreator(new ServiceDao()));
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

        $collector = new PaneCollector($service_pane_builder);
        $collector->collectPane($event);
    }

    /**
     * @see Event:BURNING_PARROT_GET_STYLESHEETS
     */
    public function burningParrotGetStylesheets(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/project/admin/permission_per_group') === 0) {
            $assets = $this->getIncludeAssets();
            $params['stylesheets'][] = $assets->getFileURL('style-bp.css');
        }
    }


    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event)
    {
        $event->addJavascript($this->getIncludeAssets()->getFileURL('permission-per-group.js'));
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/svn',
            '/assets/svn'
        );
    }

    public function httpdPostRotate(PostRotateEvent $event)
    {
        DBWriter::build($event->getLogger())->postrotate();
    }

    public function statisticsCollectorSVN(StatisticsCollectorSVN $collector)
    {
        $dao     = new Dao();
        $commits = $dao->countSVNCommits();
        if (! $commits) {
            return;
        }

        $collector->setSvnCommits($commits);
    }

    public function lastMonthStatisticsCollectorSVN(LastMonthStatisticsCollectorSVN $collector)
    {
        $dao     = new Dao();
        $commits = $dao->countSVNCommitBefore($collector->getTimestamp());
        if (! $commits) {
            return;
        }

        $collector->setSvnCommits($commits);
    }
}
