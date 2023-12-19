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
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\PendingElements\PendingDocumentsRetriever;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\BurningParrotCompatiblePageDetector;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Error\ProjectAccessSuspendedController;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Httpd\PostRotateEvent;
use Tuleap\Layout\HomePage\LastMonthStatisticsCollectorSVN;
use Tuleap\Layout\HomePage\StatisticsCollectorSVN;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\ListeningToEventName;
use Tuleap\Project\Admin\History\GetHistoryKeyLabel;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupDisplayEvent;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\Project\Event\ProjectRegistrationActivateService;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceClassnamesCollector;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\REST\Event\ProjectGetSvn;
use Tuleap\REST\Event\ProjectOptionsSvn;
use Tuleap\Service\ServiceCreator;
use Tuleap\Statistics\CSV\StatisticsServiceUsage;
use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;
use Tuleap\SVN\AccessControl\AccessControlController;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVNCore\AccessFileReader;
use Tuleap\SVN\AccessControl\SVNRefreshAllAccessFilesCommand;
use Tuleap\SVN\Admin\AdminController;
use Tuleap\SVN\Admin\GlobalAdministratorsUpdater;
use Tuleap\SVN\Admin\GlobalAdministratorsController;
use Tuleap\SVN\Admin\ImmutableTagController;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Admin\MailHeaderDao;
use Tuleap\SVN\Admin\MailHeaderManager;
use Tuleap\SVN\Admin\MailNotificationDao;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Admin\DisplayMigrateFromCoreController;
use Tuleap\SVN\Admin\RestoreController;
use Tuleap\SVN\Hooks\MissingHooksPathsFromFileSystemRetriever;
use Tuleap\SVN\Setup\SetupSVNCommand;
use Tuleap\SVNCore\AccessControl\SVNProjectAccessRouteDefinition;
use Tuleap\SVNCore\ApacheConfGenerator;
use Tuleap\SVN\Commit\FileSizeValidator;
use Tuleap\SVN\Commit\Svnlook;
use Tuleap\SVN\Dao;
use Tuleap\SVN\DiskUsage\DiskUsageCollector;
use Tuleap\SVN\DiskUsage\DiskUsageDao;
use Tuleap\SVN\DiskUsage\DiskUsageRetriever;
use Tuleap\SVNCore\Event\UpdateProjectAccessFilesEvent;
use Tuleap\SVN\Events\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\SVN\Events\SystemEvent_SVN_DELETE_REPOSITORY;
use Tuleap\SVN\Events\SystemEvent_SVN_IMPORT_CORE_REPOSITORY;
use Tuleap\SVN\Events\SystemEvent_SVN_RESTORE_REPOSITORY;
use Tuleap\SVN\Explorer\ExplorerController;
use Tuleap\SVN\Explorer\RepositoryBuilder;
use Tuleap\SVN\Explorer\RepositoryDisplayController;
use Tuleap\SVNCore\GetAllRepositories;
use Tuleap\SVN\Logs\DBWriter;
use Tuleap\SVN\Logs\QueryBuilder;
use Tuleap\SVN\Migration\BareRepositoryCreator;
use Tuleap\SVN\Migration\RepositoryCopier;
use Tuleap\SVN\Migration\SettingsRetriever;
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
use Tuleap\SVN\Repository\ApacheRepositoriesCollector;
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
use Tuleap\SVN\SiteAdmin\DisplayMaxFileSizeController;
use Tuleap\SVN\SiteAdmin\DisplayTuleapPMParamsController;
use Tuleap\SVN\SiteAdmin\UpdateMaxFileSizeController;
use Tuleap\SVN\SiteAdmin\UpdateTuleapPMParamsController;
use Tuleap\SVN\SvnAdmin;
use Tuleap\SVNCore\SvnCoreAccess;
use Tuleap\SVNCore\SvnCoreUsage;
use Tuleap\SVN\SvnPermissionManager;
use Tuleap\SVN\SvnRouter;
use Tuleap\SVN\Admin\UpdateMigrateFromCoreController;
use Tuleap\SVN\ViewVC\AccessHistoryDao;
use Tuleap\SVN\ViewVC\AccessHistorySaver;
use Tuleap\SVN\ViewVC\ViewVCProxy;
use Tuleap\SVN\XMLImporter;
use Tuleap\SVN\XMLSvnExporter;
use Tuleap\SVNCore\Cache\ParameterDao;
use Tuleap\SVNCore\Cache\ParameterRetriever;
use Tuleap\SVNCore\Cache\ParameterSaver;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class SvnPlugin extends Plugin implements PluginWithConfigKeys, PluginWithService
{
    public const SERVICE_SHORTNAME  = 'plugin_svn';
    public const SYSTEM_NATURE_NAME = ReferenceManager::REFERENCE_NATURE_SVNREVISION;

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
    }

    public static function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger('svn_syslog');
    }

    #[ListeningToEventClass]
    public function exportXmlProject(ExportXmlProject $event): void
    {
        if (! $event->shouldExportAllData()) {
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
            new SvnAdmin(new System_Command(), self::getLogger(), Backend::instance(Backend::SVN)),
            new XML_SimpleXMLCDATAFactory(),
            $this->getMailNotificationManager(),
            self::getLogger(),
            new AccessFileReader(\Tuleap\SVNCore\SvnAccessFileDefaultBlockGenerator::instance())
        );
    }

    public function getPluginInfo()
    {
        if (! is_a($this->pluginInfo, 'SvnPluginInfo')) {
            $this->pluginInfo = new SvnPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function getTypes()
    {
        return [
            SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            SystemEvent_SVN_DELETE_REPOSITORY::NAME,
            SystemEvent_SVN_RESTORE_REPOSITORY::NAME,
            SystemEvent_SVN_IMPORT_CORE_REPOSITORY::NAME,
        ];
    }

    #[ListeningToEventClass]
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
        $commands_collector->addCommand(
            SetupSVNCommand::NAME,
            static fn (): SetupSVNCommand => new SetupSVNCommand(),
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

    #[ListeningToEventName(Event::UGROUP_RENAME)]
    public function ugroupRename(array $params): void
    {
        $project = $params['project'];

        $this->updateAllAccessFileOfProject($project, $params['new_ugroup_name'], $params['old_ugroup_name']);
    }

    #[ListeningToEventName(SystemEvent_PROJECT_IS_PRIVATE::class)]
    public function changeProjectRepositoriesAccess(array $params): void
    {
        $project_id = $params[0];
        $project    = ProjectManager::instance()->getProject($project_id);

        $this->updateAllAccessFileOfProject($project, null, null);
    }

    #[ListeningToEventName('SystemEvent_USER_RENAME')]
    public function systemEventUserRename(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        $new_ugroup_name = null;
        $old_ugroup_name = null;
        $user            = $params['user'];

        $projects = $this->getProjectManager()->getAllProjectsForUserIncludingTheOnesSheDoesNotHaveAccessTo($user);

        foreach ($projects as $project) {
            $this->updateAllAccessFileOfProject($project, $new_ugroup_name, $old_ugroup_name);
        }
    }

    #[ListeningToEventClass]
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

    #[ListeningToEventClass]
    public function getAllRepositories(GetAllRepositories $get_all_repositories): void
    {
        (new ApacheRepositoriesCollector($this->getRepositoryManager()))->process($get_all_repositories);
    }

    #[ListeningToEventName(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE)]
    public function getSystemEventsDefaultTypesForQueue(array &$params): void
    {
        $params['types'][] = 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_DELETE_REPOSITORY::NAME;
        $params['types'][] = 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_RESTORE_REPOSITORY::NAME;
        $params['types'][] = SystemEvent_SVN_IMPORT_CORE_REPOSITORY::class;
    }

    #[ListeningToEventName(Event::GET_SYSTEM_EVENT_CLASS)]
    public function getSystemEventClass(array $params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName
    {
        switch ($params['type']) {
            case 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME:
                $params['class']        = SystemEvent_SVN_CREATE_REPOSITORY::class;
                $params['dependencies'] = [
                    $this->getAccessFileHistoryCreator(),
                    $this->getRepositoryManager(),
                    $this->getUserManager(),
                    $this->getBackendSVN(),
                    $this->getCopier(),
                ];
                break;
            case 'Tuleap\\SVN\\Events\\' . SystemEvent_SVN_DELETE_REPOSITORY::NAME:
                $params['class']        = SystemEvent_SVN_DELETE_REPOSITORY::class;
                $params['dependencies'] = [
                    $this->getRepositoryManager(),
                    ProjectManager::instance(),
                    $this->getApacheConfGenerator(),
                    $this->getRepositoryDeleter(),
                    new SvnAdmin(new System_Command(), self::getLogger(), Backend::instance(Backend::SVN)),
                ];
                break;
            case SystemEvent_SVN_IMPORT_CORE_REPOSITORY::class:
                $params['class']        = SystemEvent_SVN_IMPORT_CORE_REPOSITORY::class;
                $params['dependencies'] = SystemEvent_SVN_IMPORT_CORE_REPOSITORY::getDependencies(
                    ProjectManager::instance(),
                    $this->getBackendSVN(),
                    $this->getRepositoryManager(),
                    new \Tuleap\SVN\Logs\LastAccessDao(),
                );
                break;
        }
    }

    private function getApacheConfGenerator()
    {
        return ApacheConfGenerator::build();
    }

    private function getRepositoryManager(): RepositoryManager
    {
        if (empty($this->repository_manager)) {
            $this->repository_manager = new RepositoryManager(
                new Dao(),
                ProjectManager::instance(),
                new SvnAdmin(new System_Command(), self::getLogger(), Backend::instanceSVN()),
                self::getLogger(),
                new System_Command(),
                new Destructor(
                    new Dao(),
                    self::getLogger()
                ),
                EventManager::instance(),
                Backend::instanceSVN(),
                $this->getAccessFileHistoryFactory()
            );
        }

        return $this->repository_manager;
    }

    private function getAccessFileHistoryDao(): AccessFileHistoryDao
    {
        if (empty($this->accessfile_dao)) {
            $this->accessfile_dao = new AccessFileHistoryDao();
        }
        return $this->accessfile_dao;
    }

    private function getAccessFileHistoryFactory(): AccessFileHistoryFactory
    {
        if (empty($this->accessfile_factory)) {
            $this->accessfile_factory = new AccessFileHistoryFactory($this->getAccessFileHistoryDao());
        }
        return $this->accessfile_factory;
    }

    private function getAccessFileHistoryCreator(): AccessFileHistoryCreator
    {
        if (empty($this->accessfile_history_manager)) {
            $this->accessfile_history_creator = new AccessFileHistoryCreator(
                $this->getAccessFileHistoryDao(),
                $this->getAccessFileHistoryFactory(),
                $this->getProjectHistoryDao(),
                $this->getProjectHistoryFormatter(),
                \Tuleap\SVNCore\SvnAccessFileDefaultBlockGenerator::instance(),
            );
        }

        return $this->accessfile_history_creator;
    }

    private function getMailNotificationManager(): MailNotificationManager
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

    private function getMailNotificationDao(): MailNotificationDao
    {
        return new MailNotificationDao(CodendiDataAccess::instance(), new RepositoryRegexpBuilder());
    }

    private function getUGroupManager(): UGroupManager
    {
        if (empty($this->ugroup_manager)) {
            $this->ugroup_manager = new UGroupManager();
        }
        return $this->ugroup_manager;
    }

    private function getPermissionsManager(): SvnPermissionManager
    {
        if (empty($this->permissions_manager)) {
            $this->permissions_manager = new SvnPermissionManager(PermissionsManager::instance());
        }
        return $this->permissions_manager;
    }

    private function getForgeUserGroupFactory(): User_ForgeUserGroupFactory
    {
        return new User_ForgeUserGroupFactory(new UserGroupDao());
    }

    private function getProjectManager(): ProjectManager
    {
        return ProjectManager::instance();
    }

    #[ListeningToEventName('cssfile')]
    public function cssfile(): void
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $assets = $this->getIncludeAssets();
            echo '<link rel="stylesheet" type="text/css" href="' . $assets->getFileURL('style-fp.css') . '" />';
        }
    }

    #[ListeningToEventName('javascript_file')]
    public function javascriptFile(array $params): void
    {
        $layout = $params['layout'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        // Only show the javascript if we're actually in the svn pages.
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getIncludeAssets(), 'svn.js'));
        }
        if ($this->currentRequestIsForPlugin()) {
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getIncludeAssets(), 'svn-admin.js'));
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceClassnamesCollector(ServiceClassnamesCollector $event): void
    {
        $event->addService($this->getServiceShortname(), \Tuleap\SVN\ServiceSvn::class);
    }

    /**
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        // nothing to do for svn
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        // nothing to do for svn
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        // nothing to do for svn
    }

    public function addMissingService(AddMissingService $event): void
    {
        // nothing to do for svn
    }

    #[ListeningToEventName(Event::IMPORT_XML_PROJECT)]
    public function importXmlProject(array $params): void
    {
        $xml             = $params['xml_content'];
        $extraction_path = $params['extraction_path'];
        $project         = $params['project'];
        $logger          = $params['logger'];

        $user_manager = $this->getUserManager();

        $svn = new XMLImporter(
            $xml,
            $extraction_path,
            $this->getRepositoryCreator(),
            $this->getBackendSVN(),
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
                self::getLogger(),
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
                new ViewVCProxy(
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
                    ),
                    new \Tuleap\Project\ProjectAccessChecker(
                        new RestrictedUserCanAccessProjectVerifier(),
                        EventManager::instance()
                    ),
                    self::getLogger(),
                ),
                EventManager::instance(),
                UserManager::instance()
            ),
            new ImmutableTagController(
                $repository_manager,
                new Svnlook(new System_Command()),
                $this->getImmutableTagCreator(),
                $this->getImmutableTagFactory()
            ),
            new GlobalAdministratorsUpdater(
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

    public function routeSvnAdmin(): DispatchableWithRequest
    {
        return new GlobalAdministratorsController(
            ProjectManager::instance(),
            $this->getForgeUserGroupFactory(),
            $this->getPermissionsManager(),
        );
    }

    public function routeDisplayMigrateFromCore(): DispatchableWithRequest
    {
        return new DisplayMigrateFromCoreController(
            ProjectManager::instance(),
            $this->getPermissionsManager(),
            $this->getRepositoryManager()
        );
    }

    public function routeUpdateMigrateFromCore(): DispatchableWithRequest
    {
        return new UpdateMigrateFromCoreController(
            ProjectManager::instance(),
            $this->getPermissionsManager(),
            $this->getRepositoryManager(),
            new BareRepositoryCreator(
                $this->getRepositoryCreator(),
                new SettingsRetriever(
                    new SVN_Immutable_Tags_DAO(),
                    new SvnNotificationDao(),
                    new SVN_AccessFile_DAO()
                )
            )
        );
    }

    public function routeDisplaySiteAdmin(): DispatchableWithRequest
    {
        return new DisplayTuleapPMParamsController(
            new ParameterRetriever(
                new ParameterDao(),
            ),
            new AdminPageRenderer(),
        );
    }

    public function routeUpdateTuleapPMParams(): DispatchableWithRequest
    {
        return new UpdateTuleapPMParamsController(
            new ParameterSaver(
                new ParameterDao(),
                EventManager::instance()
            )
        );
    }

    public function routeDisplaySiteAdminMaxFileSize(): DispatchableWithRequest
    {
        return new DisplayMaxFileSizeController(
            new AdminPageRenderer(),
        );
    }

    public function routeUpdateSiteAdminMaxFileSize(): DispatchableWithRequest
    {
        return new UpdateMaxFileSizeController(
            new ConfigSet(
                EventManager::instance(),
                new ConfigDao()
            )
        );
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addGroup('/admin', function (RouteCollector $r) {
                $r->get('', $this->getRouteHandler('routeDisplaySiteAdmin'));
                $r->post('/cache', $this->getRouteHandler('routeUpdateTuleapPMParams'));
                $r->get('/max-file-size', $this->getRouteHandler('routeDisplaySiteAdminMaxFileSize'));
                $r->post('/max-file-size', $this->getRouteHandler('routeUpdateSiteAdminMaxFileSize'));
            });
            $r->get('/{project_name}/admin', $this->getRouteHandler('routeSvnAdmin'));
            $r->get('/{project_name}/admin-migrate', $this->getRouteHandler('routeDisplayMigrateFromCore'));
            $r->post('/{project_name}/admin-migrate', $this->getRouteHandler('routeUpdateMigrateFromCore'));
            $r->get('/index.php{path:.*}', $this->getRouteHandler('redirectOldViewVcRoutes'));
            $r->addRoute(['GET', 'POST'], '[/{path:.*}]', $this->getRouteHandler('routeSvnPlugin'));
        });
        SVNProjectAccessRouteDefinition::defineRoute($event->getRouteCollector(), '/svnplugin');
    }

    private function getBackendSVN(): BackendSVN
    {
        return Backend::instanceSVN();
    }

    #[ListeningToEventClass]
    public function getReference(GetReferenceEvent $event): void
    {
        $keyword = $event->getKeyword();

        if ($this->isReferenceASubversionReference($keyword)) {
            $project = $event->getProject();
            $value   = $event->getValue();

            $extractor = $this->getReferenceExtractor();
            $reference = $extractor->getReference($project, $keyword, $value);

            if ($reference !== null) {
                $event->setReference($reference);
            }
        }
    }

    private function getReferenceExtractor()
    {
        return new Extractor($this->getRepositoryManager());
    }

    private function isReferenceASubversionReference($keyword): bool
    {
        $dao    = new ReferenceDao();
        $result = $dao->searchSystemReferenceByNatureAndKeyword($keyword, self::SYSTEM_NATURE_NAME);

        if (! $result || $result->rowCount() < 1) {
            return false;
        }

        return true;
    }

    #[ListeningToEventName(Event::SVN_REPOSITORY_CREATED)]
    public function svnRepositoryCreated(array $params): void
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

    #[ListeningToEventClass]
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            $this->getRepositoryDeleter()->deleteProjectRepositories($event->project);
        }
    }

    #[ListeningToEventName('codendi_daily_start')]
    public function codendiDailyStart(): void
    {
        $this->getRepositoryManager()->purgeArchivedRepositories();
    }

    #[ListeningToEventClass]
    public function pendingDocumentsRetriever(PendingDocumentsRetriever $documents_retriever): void
    {
        $project               = $documents_retriever->getProject();
        $user                  = $documents_retriever->getUser();
        $archived_repositories = $this->getRepositoryManager()->getRestorableRepositoriesByProject($project, $user);

        $restore_controller = new RestoreController($this->getRepositoryManager());
        $tab_content        = $restore_controller->displayRestorableRepositories(
            $documents_retriever->getToken(),
            $archived_repositories,
            $project->getID()
        );
        $documents_retriever->addPurifiedHTML($tab_content);
    }

    #[ListeningToEventName('logs_daily')]
    public function logsDaily(array $params): void
    {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['group_id']);
        if ($project->usesService(self::SERVICE_SHORTNAME)) {
            $builder = new QueryBuilder();
            $query   = $builder->buildQuery($project, $params['span'], $params['who']);

             $params['logs'][] = [
                 'sql'   => $query,
                 'field' => dgettext('tuleap-svn', 'Repository name'),
                 'title' => dgettext('tuleap-svn', 'SVN'),
             ];
        }
    }

    #[ListeningToEventName('statistics_collector')]
    public function statisticsCollector(array $params): void
    {
        if (! empty($params['formatter'])) {
            $statistic_dao       = new \Tuleap\SVN\Statistic\SCMUsageDao();
            $statistic_collector = new \Tuleap\SVN\Statistic\SCMUsageCollector($statistic_dao);

            echo $statistic_collector->collect($params['formatter']);
        }
    }

    #[ListeningToEventClass]
    public function statisticsServiceUsage(StatisticsServiceUsage $event): void
    {
        $statistic_dao       = new \Tuleap\SVN\Statistic\ServiceUsageDao();
        $statistic_collector = new \Tuleap\SVN\Statistic\ServiceUsageCollector($statistic_dao);
        $statistic_collector->collect($event->csv_exporter, $event->start_date, $event->end_date);
    }

    #[ListeningToEventName(ProjectCreator::PROJECT_CREATION_REMOVE_LEGACY_SERVICES)]
    public function projectCreationRemoveLegacyServices(array $params): void
    {
        if (! $this->isRestricted()) {
            $this->getServiceActivator()->unuseLegacyService($params);
        }
    }

    #[ListeningToEventName('SystemEvent_PROJECT_RENAME')]
    public function systemEventProjectRename(array $params): void
    {
        $project            = $params['project'];
        $repository_manager = $this->getRepositoryManager();
        $repositories       = $repository_manager->getRepositoriesInProject($project);

        if (count($repositories) > 0) {
            $this->getBackendSVN()->setSVNApacheConfNeedUpdate();
        }
    }

    #[ListeningToEventName(Event::PROJECT_ACCESS_CHANGE)]
    public function projectAccessChange(array $params): void
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateProjectAccess($params['project_id'], $params['old_access'], $params['access']);
    }

    #[ListeningToEventName(Event::SITE_ACCESS_CHANGE)]
    public function siteAccessChange(array $params): void
    {
        $updater = $this->getUgroupToNotifyUpdater();
        $updater->updateSiteAccess($params['old_value']);
    }

    private function getUgroupToNotifyUpdater(): UgroupsToNotifyUpdater
    {
        return new UgroupsToNotifyUpdater($this->getUGroupNotifyDao());
    }

    #[ListeningToEventName('project_admin_remove_user')]
    public function projectAdminRemoveUser(array $params): void
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

    #[ListeningToEventName('project_admin_ugroup_deletion')]
    public function projectAdminUgroupDeletion(array $params): void
    {
        $project_id = $params['group_id'];
        $ugroup     = $params['ugroup'];

        $ugroups_to_notify_dao = $this->getUGroupNotifyDao();
        $ugroups_to_notify_dao->deleteByUgroupId($project_id, $ugroup->getId());
        $this->getMailNotificationDao()->deleteEmptyNotificationsInProject($project_id);
    }

    #[ListeningToEventName('plugin_statistics_disk_usage_collect_project')]
    public function pluginStatisticsDiskUsageCollectProject(array $params): void
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

    #[ListeningToEventName('plugin_statistics_disk_usage_service_label')]
    public function pluginStatisticsDiskUsageServiceLabel(array $params): void
    {
        $params['services'][self::SERVICE_SHORTNAME] = dgettext('tuleap-svn', 'Multi SVN');
    }

    #[ListeningToEventName('plugin_statistics_color')]
    public function pluginStatisticsColor(array $params): void
    {
        if ($params['service'] == self::SERVICE_SHORTNAME) {
            $params['color'] = 'forestgreen';
        }
    }

    private function getRetriever(): DiskUsageRetriever
    {
        $disk_usage_dao = new Statistics_DiskUsageDao();
        $svn_log_dao    = new SVN_LogDao();
        $svn_retriever  = new SVNRetriever($disk_usage_dao);
        $svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);

        $disk_usage_manager = new Statistics_DiskUsageManager(
            $disk_usage_dao,
            $svn_collector,
            EventManager::instance()
        );

        return new DiskUsageRetriever(
            $this->getRepositoryManager(),
            $disk_usage_manager,
            new DiskUsageDao(),
            $disk_usage_dao,
            self::getLogger()
        );
    }

    private function getCollector(): DiskUsageCollector
    {
        return new DiskUsageCollector($this->getRetriever(), new Statistics_DiskUsageDao());
    }

    #[ListeningToEventName(EVENT::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new \Tuleap\SVN\REST\ResourcesInjector();
        $injector->populate($params['restler']);
    }

    #[ListeningToEventName(EVENT::REST_PROJECT_RESOURCES)]
    public function restProjectResources(array $params): void
    {
        $injector = new \Tuleap\SVN\REST\ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    #[ListeningToEventClass]
    public function restProjectGetSvn(ProjectGetSvn $event): void
    {
        $event->setPluginActivated();

        $class = "Tuleap\\SVN\\REST\\" . $event->getVersion() . "\\ProjectResource";
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

    #[ListeningToEventClass]
    public function restProjectOptionsSvn(ProjectOptionsSvn $event): void
    {
        $event->setPluginActivated();
    }

    private function getRepositoryCreator(): RepositoryCreator
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

    private function getHookConfigSanitizer(): HookConfigSanitizer
    {
        return new HookConfigSanitizer();
    }

    private function getHookConfigRetriever(): HookConfigRetriever
    {
        return new HookConfigRetriever(new HookDao(), $this->getHookConfigSanitizer());
    }

    private function getRepositoryDeleter(): \Tuleap\SVN\Repository\RepositoryDeleter
    {
        return new \Tuleap\SVN\Repository\RepositoryDeleter(
            new System_Command(),
            $this->getProjectHistoryDao(),
            new Dao(),
            SystemEventManager::instance(),
            $this->getRepositoryManager()
        );
    }

    #[ListeningToEventClass]
    public function projectRegistrationActivateService(ProjectRegistrationActivateService $event): void
    {
        $this->getServiceActivator()->forceUsageOfService($event->getProject(), $event->getTemplate(), $event->getLegacy());
    }

    private function getServiceActivator(): ServiceActivator
    {
        return new ServiceActivator(ServiceManager::instance(), new ServiceCreator(new ServiceDao()));
    }

    private function getImmutableTagCreator(): ImmutableTagCreator
    {
        return new ImmutableTagCreator(
            new ImmutableTagDao(),
            $this->getProjectHistoryFormatter(),
            $this->getProjectHistoryDao(),
            $this->getImmutableTagFactory()
        );
    }

    private function getProjectHistoryDao(): ProjectHistoryDao
    {
        return new ProjectHistoryDao();
    }

    private function getProjectHistoryFormatter(): ProjectHistoryFormatter
    {
        return new ProjectHistoryFormatter();
    }

    private function getImmutableTagFactory(): ImmutableTagFactory
    {
        return new ImmutableTagFactory(new ImmutableTagDao());
    }

    private function getNotificationEmailsBuilder(): NotificationsEmailsBuilder
    {
        return new NotificationsEmailsBuilder();
    }

    private function getUserManager(): UserManager
    {
        return UserManager::instance();
    }

    private function getUserNotifyDao(): UsersToNotifyDao
    {
        return new UsersToNotifyDao();
    }

    private function getUGroupNotifyDao(): UgroupsToNotifyDao
    {
        return new UgroupsToNotifyDao();
    }

    #[ListeningToEventClass]
    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(NavigationDropdownQuickLinksCollector $quick_links_collector): void
    {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-svn', 'SVN'),
                GlobalAdministratorsController::getURL($project),
            )
        );
    }

    private function getSystemCommand(): System_Command
    {
        return new System_Command();
    }

    private function getCopier(): RepositoryCopier
    {
        return new RepositoryCopier($this->getSystemCommand());
    }

    #[ListeningToEventClass]
    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event): void
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

    #[ListeningToEventClass]
    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event): void
    {
        if ($this->isInSvnHomepage()) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    #[ListeningToEventName(Event::BURNING_PARROT_GET_STYLESHEETS)]
    public function burningParrotGetStylesheets(array $params): void
    {
        if (
            strpos($_SERVER['REQUEST_URI'], '/project/admin/permission_per_group') === 0
            || $this->isInSvnHomepage()
        ) {
            $assets = $this->getIncludeAssets();

            $params['stylesheets'][] = $assets->getFileURL('style-bp.css');
        }
    }

    private function isInSvnHomepage(): bool
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) !== 0) {
            return false;
        }

        parse_str($_SERVER['QUERY_STRING'], $output);
        if (count($output) !== 1) {
            return false;
        }

        return array_keys($output) === ['group_id'];
    }

    #[ListeningToEventClass]
    public function permissionPerGroupDisplayEvent(PermissionPerGroupDisplayEvent $event): void
    {
        $event->addJavascript(
            new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../scripts/permissions-per-group/frontend-assets',
                    '/assets/svn/permissions-per-group'
                ),
                'src/index.ts'
            )
        );
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(__DIR__ . '/../scripts/main/frontend-assets', '/assets/svn/main');
    }

    #[ListeningToEventClass]
    public function httpdPostRotate(PostRotateEvent $event): void
    {
        DBWriter::build($event->getLogger())->postrotate();
    }

    #[ListeningToEventClass]
    public function statisticsCollectorSVN(StatisticsCollectorSVN $collector)
    {
        $dao     = new Dao();
        $commits = $dao->countSVNCommits();
        if (! $commits) {
            return;
        }

        $collector->setSvnCommits($commits);
    }

    #[ListeningToEventClass]
    public function lastMonthStatisticsCollectorSVN(LastMonthStatisticsCollectorSVN $collector)
    {
        $dao     = new Dao();
        $commits = $dao->countSVNCommitBefore($collector->getTimestamp());
        if (! $commits) {
            return;
        }

        $collector->setSvnCommits($commits);
    }

    #[ListeningToEventClass]
    public function svnCoreUsageEvent(SvnCoreUsage $svn_core_usage): void
    {
        $this->getRepositoryManager()->svnCoreUsage($svn_core_usage);
    }

    #[ListeningToEventClass]
    public function svnCoreAccess(SvnCoreAccess $svn_core_access): void
    {
        (new \Tuleap\SVN\Repository\SvnCoreAccess(new Dao()))->process($svn_core_access);
    }

    public function getConfigKeys(ConfigClassProvider $config_keys): void
    {
        $config_keys->addConfigClass(FileSizeValidator::class);
    }

    #[ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $event): void
    {
        $event->addPluginOption(
            \Tuleap\Admin\SiteAdministrationPluginOption::build(
                dgettext('tuleap-svn', 'SVN'),
                $this->getPluginPath() . '/admin'
            )
        );
    }

    #[ListeningToEventName(Event::PROCCESS_SYSTEM_CHECK)]
    public function processSystemCheck(array $params): void
    {
        (new \Tuleap\SVN\Hooks\RestoreMissingHooks(
            new MissingHooksPathsFromFileSystemRetriever(self::getLogger(), $this->getRepositoryManager()),
            $params['logger'],
            $this->getBackendSVN(),
        ))->restoreAllMissingHooks();
    }

    #[ListeningToEventClass]
    public function getHistoryKeyLabel(GetHistoryKeyLabel $event): void
    {
        if ($event->getKey() === 'svn_core_removal') {
            $event->setLabel(dgettext('tuleap-svn', 'Subversion (legacy) service was deleted at platform upgrade'));
        }
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
    }
}
