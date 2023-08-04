<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\PermissionDelegation\ForgeUserGroupDeletedEvent;
use Tuleap\admin\PermissionDelegation\PermissionDelegationsAddedToForgeUserGroupEvent;
use Tuleap\Admin\PermissionDelegation\PermissionDelegationsRemovedForForgeUserGroupEvent;
use Tuleap\Admin\PermissionDelegation\UserAddedToForgeUserGroupEvent;
use Tuleap\Admin\PermissionDelegation\UsersRemovedFromForgeUserGroupEvent;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\MediawikiStandalone\Configuration\GenerateLocalSettingsCommand;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsFactory;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsInstantiator;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsPersistToPHPFile;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsRepresentation;
use Tuleap\MediawikiStandalone\Configuration\MainpageDeployer;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiAsyncUpdateProcessor;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiCentralDatabaseParameter;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandFactory;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiManagementCommandProcessFactory;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiNewOAuth2AppBuilder;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiOAuth2AppSecretGeneratorDBStore;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiSharedSecretGeneratorForgeConfigStore;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiInstallAndUpdateScriptCaller;
use Tuleap\MediawikiStandalone\Configuration\MustachePHPString\PHPStringMustacheRenderer;
use Tuleap\MediawikiStandalone\Configuration\ProjectMediaWikiServiceDAO;
use Tuleap\MediawikiStandalone\Configuration\UpdateMediaWikiTask;
use Tuleap\MediawikiStandalone\Instance\InstanceManagement;
use Tuleap\MediawikiStandalone\Instance\LogUsersOutInstanceTask;
use Tuleap\MediawikiStandalone\Instance\MediawikiHTTPClientFactory;
use Tuleap\MediawikiStandalone\Instance\Migration\Admin\StartMigrationController;
use Tuleap\MediawikiStandalone\Instance\Migration\LegacyMediawikiLanguageDao;
use Tuleap\MediawikiStandalone\Instance\Migration\Admin\DisplayMigrationController;
use Tuleap\MediawikiStandalone\Instance\Migration\Admin\LegacyReadyToMigrateDao;
use Tuleap\MediawikiStandalone\Instance\Migration\ServiceMediawikiSwitcher;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationsDao;
use Tuleap\MediawikiStandalone\Instance\ProjectRenameHandler;
use Tuleap\MediawikiStandalone\Instance\ProjectStatusHandler;
use Tuleap\MediawikiStandalone\Instance\ProvideSiteLevelInitializationLanguageCode;
use Tuleap\MediawikiStandalone\OAuth2\MediawikiStandaloneOAuth2ConsentChecker;
use Tuleap\MediawikiStandalone\OAuth2\RejectAuthorizationRequiringConsent;
use Tuleap\MediawikiStandalone\Permissions\Admin\AdminPermissionsController;
use Tuleap\MediawikiStandalone\Permissions\Admin\AdminPermissionsPresenterBuilder;
use Tuleap\MediawikiStandalone\Permissions\Admin\AdminSavePermissionsController;
use Tuleap\MediawikiStandalone\Permissions\Admin\CSRFSynchronizerTokenProvider;
use Tuleap\MediawikiStandalone\Permissions\Admin\PermissionPerGroupServicePaneBuilder;
use Tuleap\MediawikiStandalone\Permissions\Admin\ProjectPermissionsSaver;
use Tuleap\MediawikiStandalone\Permissions\Admin\RejectNonMediawikiAdministratorMiddleware;
use Tuleap\MediawikiStandalone\Permissions\Admin\UserGroupToSaveRetriever;
use Tuleap\MediawikiStandalone\Permissions\ForgeUserGroupPermission\MediawikiAdminAllProjects;
use Tuleap\MediawikiStandalone\Permissions\MediawikiPermissionsDao;
use Tuleap\MediawikiStandalone\Permissions\PermissionsFollowingSiteAccessChangeUpdater;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\MediawikiStandalone\Permissions\RestrictedUserCanAccessMediaWikiVerifier;
use Tuleap\MediawikiStandalone\Permissions\UserPermissionsBuilder;
use Tuleap\MediawikiStandalone\REST\MediawikiStandaloneResourcesInjector;
use Tuleap\MediawikiStandalone\REST\OAuth2\OAuth2MediawikiStandaloneReadScope;
use Tuleap\MediawikiStandalone\Service\MediawikiFlavorUsageDao;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\MediawikiStandalone\Service\ServiceActivationEvent;
use Tuleap\MediawikiStandalone\Service\ServiceActivationHandler;
use Tuleap\MediawikiStandalone\Service\ServiceAvailabilityHandler;
use Tuleap\MediawikiStandalone\Service\ServiceAvailabilityProjectServiceBeforeAvailabilityEvent;
use Tuleap\MediawikiStandalone\Service\ServiceAvailabilityServiceDisabledCollectorEvent;
use Tuleap\MediawikiStandalone\Service\UnderConstructionController;
use Tuleap\MediawikiStandalone\XML\XMLMediaWikiExporter;
use Tuleap\MediawikiStandalone\XML\XMLMediaWikiImporter;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2ServerCore\App\AppFactory;
use Tuleap\OAuth2ServerCore\App\AppMatchingClientIDFilterAppTypeRetriever;
use Tuleap\OAuth2ServerCore\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PromptParameterValuesExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectEmailScope;
use Tuleap\OAuth2ServerCore\OpenIDConnect\Scope\OpenIDConnectProfileScope;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Plugin\LifecycleHookCommand\PluginExecuteUpdateHookEvent;
use Tuleap\Project\Admin\History\GetHistoryKeyLabel;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\Project\Routing\ProjectByNameRetrieverMiddleware;
use Tuleap\Project\Service\PluginAddMissingServiceTrait;
use Tuleap\Project\Service\PluginWithService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Queue\EnqueueTask;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Templating\TemplateCache;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ProjectReadScope;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class mediawiki_standalonePlugin extends Plugin implements PluginWithService, PluginWithConfigKeys
{
    use PluginAddMissingServiceTrait;

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-mediawiki_standalone', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone'),
                    dgettext('tuleap-mediawiki_standalone', 'Standalone MediaWiki instances integration with Tuleap')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getInstallRequirements(): array
    {
        return [new \Tuleap\Plugin\MandatoryAsyncWorkerSetupPluginInstallRequirement(
            new \Tuleap\Queue\WorkerAvailability()
        ),
        ];
    }

    #[ListeningToEventClass]
    public function forgeUserGroupDeletedEvent(ForgeUserGroupDeletedEvent $event): void
    {
        $this->logsForgeUserGroupMembersOutOfAllProjects($event->getUserGroup());
    }

    #[ListeningToEventClass]
    public function permissionDelegationsAddedToForgeUserGroupEvent(PermissionDelegationsAddedToForgeUserGroupEvent $event): void
    {
        foreach ($event->getPermissions() as $permission) {
            if ($permission->getId() === MediawikiAdminAllProjects::ID) {
                $this->logsForgeUserGroupMembersOutOfAllProjects($event->getUserGroup());
                break;
            }
        }
    }

    #[ListeningToEventClass]
    public function permissionDelegationsRemovedForForgeUserGroupEvent(PermissionDelegationsRemovedForForgeUserGroupEvent $event): void
    {
        foreach ($event->getPermissions() as $permission) {
            if ($permission->getId() === MediawikiAdminAllProjects::ID) {
                $this->logsForgeUserGroupMembersOutOfAllProjects($event->getUserGroup());
                break;
            }
        }
    }

    private function logsForgeUserGroupMembersOutOfAllProjects(\User_ForgeUGroup $user_group): void
    {
        $enqueue_task = new EnqueueTask();
        $users        = (new User_ForgeUserGroupUsersFactory(new User_ForgeUserGroupUsersDao()))->getAllUsersFromForgeUserGroup($user_group);
        foreach ($users as $user) {
            $enqueue_task->enqueue(
                LogUsersOutInstanceTask::logsSpecificUserOutOfAllProjects((int) $user->getId())
            );
        }
    }

    #[ListeningToEventClass]
    public function userAddedToForgeUserGroupEvent(UserAddedToForgeUserGroupEvent $event): void
    {
        (new EnqueueTask())->enqueue(
            LogUsersOutInstanceTask::logsSpecificUserOutOfAllProjects((int) $event->getUser()->getId())
        );
    }

    #[ListeningToEventClass]
    public function usersRemovedFromForgeUserGroupEvent(UsersRemovedFromForgeUserGroupEvent $event): void
    {
        $enqueue_task = new EnqueueTask();
        foreach ($event->getUsers() as $user) {
            $enqueue_task->enqueue(
                LogUsersOutInstanceTask::logsSpecificUserOutOfAllProjects((int) $user->getId())
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION)]
    public function getPermissionDelegation(array $params): void
    {
        $params['plugins_permission'][MediawikiAdminAllProjects::ID] = new MediawikiAdminAllProjects();
    }

    #[ListeningToEventClass]
    public function exportXmlProject(ExportXmlProject $event): void
    {
        if (! $this->isAllowed($event->getProject()->getID())) {
            return;
        }

        $service = $event->getProject()->getService(MediawikiStandaloneService::SERVICE_SHORTNAME);
        if (! $service instanceof MediawikiStandaloneService) {
            return;
        }

        $dao = new MediawikiPermissionsDao();
        (new XMLMediaWikiExporter(
            new WrapperLogger($event->getLogger(), 'MediaWiki Standalone'),
            new ProjectPermissionsRetriever($dao),
            new UGroupManager(),
        ))->exportToXml(
            $event->getProject(),
            $event->getIntoXml(),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::IMPORT_XML_PROJECT)]
    public function importXmlProject(array $params): void
    {
        $importer = new XMLMediaWikiImporter(
            new WrapperLogger($params['logger'], 'MediaWiki Standalone'),
            new UGroupManager(),
            new MediawikiPermissionsDao(),
        );

        $importer->import($params['project'], $params['xml_content']);
    }

    #[ListeningToEventClass]
    public function getHistoryKeyLabel(GetHistoryKeyLabel $event): void
    {
        $label = ProjectPermissionsSaver::getLabelFromKey($event->getKey());
        if ($label) {
            $event->setLabel($label);
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName('fill_project_history_sub_events')]
    public function fillProjectHistorySubEvents(array $params): void
    {
        ProjectPermissionsSaver::fillProjectHistorySubEvents($params);
    }

    public function getServiceShortname(): string
    {
        return MediawikiStandaloneService::SERVICE_SHORTNAME;
    }

    protected function getServiceClass(): string
    {
        return MediawikiStandaloneService::class;
    }

    public function postEnable(): void
    {
        parent::postEnable();
        (new EnqueueTask())->enqueue(new UpdateMediaWikiTask());
    }

    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = $this->getServiceClass();
    }

    /**
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        $service_activation_handler = new ServiceActivationHandler(new EnqueueTask(), new ProvideSiteLevelInitializationLanguageCode(), new OngoingInitializationsDao(new MediawikiFlavorUsageDao()));
        $service_activation_handler->handle(
            ServiceActivationEvent::fromServiceIsUsedEvent(
                $params,
                ProjectManager::instance()
            ),
        );
    }

    #[ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $service_activation_handler = new ServiceActivationHandler(new EnqueueTask(), new ProvideSiteLevelInitializationLanguageCode(), new OngoingInitializationsDao(new MediawikiFlavorUsageDao()));
        $service_activation_handler->handle(
            ServiceActivationEvent::fromRegisterProjectCreationEvent($event),
        );
        if ($event->shouldProjectInheritFromTemplate()) {
            (new MediawikiPermissionsDao())->duplicateProjectPermissions(
                $event->getTemplateProject(),
                $event->getJustCreatedProject(),
                $event->getMappingRegistry()->getUgroupMapping()
            );
        }
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        (new ServiceAvailabilityHandler(new MediawikiFlavorUsageDao()))->handle(
            new ServiceAvailabilityProjectServiceBeforeAvailabilityEvent($event)
        );
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        (new ServiceAvailabilityHandler(new MediawikiFlavorUsageDao()))->handle(
            new ServiceAvailabilityServiceDisabledCollectorEvent($event)
        );
    }

    /**
     * @param array{project_id: int} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::PROJECT_ACCESS_CHANGE)]
    public function projectAccessChange(array $params): void
    {
        $task = LogUsersOutInstanceTask::logsOutUserOfAProjectFromItsID(
            $params['project_id'],
            ProjectManager::instance()
        );

        if ($task !== null) {
            (new EnqueueTask())->enqueue($task);
        }
    }

    /**
     * @param array{group_id: int|string, user_id: int|string} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName('project_admin_remove_user')]
    public function projectAdminRemoveUser(array $params): void
    {
        $task = LogUsersOutInstanceTask::logsSpecificUserOutOfAProjectFromItsID(
            (int) $params['group_id'],
            ProjectManager::instance(),
            (int) $params['user_id']
        );

        if ($task !== null) {
            (new EnqueueTask())->enqueue($task);
        }
    }

    /**
     * @psalm-param array{old_user: PFUser, new_user: PFUser} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::USER_MANAGER_UPDATE_DB)]
    public function userManagerUpdateDb(array $params): void
    {
        $task = LogUsersOutInstanceTask::logsSpecificUserOutOfAllProjects(
            (int) $params['new_user']->getId(),
        );

        (new EnqueueTask())->enqueue($task);
    }

    /**
     * @param array{old_value: \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED, new_value: \ForgeAccess::ANONYMOUS|\ForgeAccess::REGULAR|\ForgeAccess::RESTRICTED} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::SITE_ACCESS_CHANGE)]
    public function siteAccessChange(array $params): void
    {
        (new \Tuleap\MediawikiStandalone\Instance\SiteAccessHandler(
            new EnqueueTask()
        ))->process();

        (new PermissionsFollowingSiteAccessChangeUpdater(new MediawikiPermissionsDao()))
            ->updatePermissionsFollowingSiteAccessChange($params['old_value']);
    }

    #[ListeningToEventClass]
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        (new ProjectStatusHandler(new EnqueueTask()))->handle($event->project, $event->status);
    }

    #[ListeningToEventClass]
    public function workerEvent(WorkerEvent $event): void
    {
        $logger           = $this->getBackendLogger();
        $flavor_usage_dao = new MediawikiFlavorUsageDao();
        (new InstanceManagement(
            $logger,
            new MediawikiHTTPClientFactory(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            ProjectManager::instance(),
            new MediaWikiCentralDatabaseParameter($this->_getPluginManager()),
            $this->getMediaWikiManagementCommandProcessFactory($logger),
            $flavor_usage_dao,
            new OngoingInitializationsDao($flavor_usage_dao),
            new ServiceMediawikiSwitcher(new ServiceDao(), $logger),
            new \Tuleap\MediawikiStandalone\Instance\Migration\PrimeLegacyMediawikiDB(),
            new LegacyMediawikiLanguageDao(),
            new ProvideSiteLevelInitializationLanguageCode(),
            new MediawikiPermissionsDao(),
        ))->process($event);
        (new MediaWikiAsyncUpdateProcessor($this->buildUpdateScriptCaller($logger)))->process($event);
    }

    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(MediawikiHTTPClientFactory::class);
        $event->addConfigClass(LocalSettingsRepresentation::class);
    }

    /**
     * @psalm-param array{group_id: string|int, new_name: string} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::PROJECT_RENAME)]
    public function projectRename(array $params): void
    {
        (new ProjectRenameHandler(
            new EnqueueTask(),
            ProjectManager::instance(),
        ))->handle((int) $params['group_id'], $params['new_name']);
    }

    /**
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $injector = new MediawikiStandaloneResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @psalm-param array{allowed_services: string[]} $params
     */
    #[\Tuleap\Plugin\ListeningToEventName(Event::GET_SERVICES_ALLOWED_FOR_RESTRICTED)]
    public function getServicesAllowedForRestricted(array &$params): void
    {
        $params['allowed_services'][] = $this->getServiceShortname();
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $route_collector = $event->getRouteCollector();

        $route_collector->addRoute(
            ['GET', 'POST'],
            '/mediawiki_standalone/oauth2_authorize',
            $this->getRouteHandler('routeAuthorizationEndpoint')
        );
        $route_collector->addRoute(
            'GET',
            '/mediawiki_standalone/admin/{' . AdminPermissionsController::PROJECT_NAME_VARIABLE_NAME . '}/permissions',
            $this->getRouteHandler('routeAdminProjectPermissions')
        );
        $route_collector->addRoute(
            'POST',
            '/mediawiki_standalone/admin/{' . AdminPermissionsController::PROJECT_NAME_VARIABLE_NAME . '}/permissions',
            $this->getRouteHandler('routeAdminSaveProjectPermissions')
        );
        $route_collector->addRoute(
            'GET',
            '/mediawiki_standalone/under-construction/{' . UnderConstructionController::PROJECT_NAME_VARIABLE_NAME . '}',
            $this->getRouteHandler('routeUnderConstruction')
        );
        $route_collector->addRoute(
            'GET',
            DisplayMigrationController::URL,
            $this->getRouteHandler('routeAdminDisplayMigrations')
        );
        $route_collector->addRoute(
            'POST',
            StartMigrationController::URL,
            $this->getRouteHandler('routeAdminStartMigrations')
        );
    }

    public function routeAdminStartMigrations(): \Tuleap\Request\DispatchableWithRequest
    {
        return new StartMigrationController(
            new \Tuleap\MediawikiStandalone\Instance\Migration\Admin\CSRFSynchronizerTokenProvider(),
            new LegacyReadyToMigrateDao(),
            $this,
            ProjectManager::instance(),
            new EnqueueTask(),
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao())
            ),
            new OngoingInitializationsDao(new MediawikiFlavorUsageDao()),
            new SapiEmitter(),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }

    public function routeAdminDisplayMigrations(): \Tuleap\Request\DispatchableWithRequest
    {
        return new DisplayMigrationController(
            new LegacyReadyToMigrateDao(),
            $this,
            new AdminPageRenderer(),
            new \Tuleap\MediawikiStandalone\Instance\Migration\Admin\CSRFSynchronizerTokenProvider(),
        );
    }

    public function routeUnderConstruction(): \Tuleap\Request\DispatchableWithRequest
    {
        return new UnderConstructionController(
            ProjectManager::instance(),
            $this,
            TemplateRendererFactory::build(),
            new OngoingInitializationsDao(new MediawikiFlavorUsageDao()),
        );
    }

    public function routeAdminSaveProjectPermissions(): \Tuleap\Request\DispatchableWithRequest
    {
        $dao = new MediawikiPermissionsDao();

        return new AdminSavePermissionsController(
            new ProjectPermissionsSaver(
                $dao,
                new ProjectHistoryDao(),
                new EnqueueTask(),
            ),
            new UserGroupToSaveRetriever(new UGroupManager()),
            new RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new FeedbackSerializer(new FeedbackDao())
            ),
            new CSRFSynchronizerTokenProvider(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(MediawikiStandaloneService::SERVICE_SHORTNAME),
            new ProjectByNameRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new RejectNonMediawikiAdministratorMiddleware(
                UserManager::instance(),
                new UserPermissionsBuilder(
                    new \User_ForgeUserGroupPermissionsManager(
                        new \User_ForgeUserGroupPermissionsDao()
                    ),
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessMediaWikiVerifier(),
                        \EventManager::instance(),
                    ),
                    new ProjectPermissionsRetriever($dao)
                ),
            )
        );
    }

    public function routeAdminProjectPermissions(): \Tuleap\Request\DispatchableWithRequest
    {
        $dao = new MediawikiPermissionsDao();

        $permissions_retriever = new ProjectPermissionsRetriever($dao);

        return new AdminPermissionsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this,
            TemplateRendererFactory::build(),
            new CSRFSynchronizerTokenProvider(),
            new AdminPermissionsPresenterBuilder(
                $permissions_retriever,
                new User_ForgeUserGroupFactory(new UserGroupDao()),
            ),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(MediawikiStandaloneService::SERVICE_SHORTNAME),
            new ProjectByNameRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new RejectNonMediawikiAdministratorMiddleware(
                UserManager::instance(),
                new UserPermissionsBuilder(
                    new \User_ForgeUserGroupPermissionsManager(
                        new \User_ForgeUserGroupPermissionsDao()
                    ),
                    new ProjectAccessChecker(
                        new RestrictedUserCanAccessMediaWikiVerifier(),
                        \EventManager::instance(),
                    ),
                    $permissions_retriever,
                ),
            )
        );
    }

    public function routeAuthorizationEndpoint(): \Tuleap\Request\DispatchableWithRequest
    {
        $response_factory           = HTTPFactoryBuilder::responseFactory();
        $stream_factory             = HTTPFactoryBuilder::streamFactory();
        $uri_factory                = HTTPFactoryBuilder::URIFactory();
        $redirect_uri_builder       = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
        $url_redirect               = new \URLRedirect(\EventManager::instance());
        $scope_builder              = AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(
                \EventManager::instance(),
                new OAuth2ScopeBuilderCollector()
            )
        );
        $authorization_code_creator = new OAuth2AuthorizationCodeCreator(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new SplitTokenVerificationStringHasher(),
            new OAuth2AuthorizationCodeDAO(),
            new OAuth2ScopeSaver(new OAuth2AuthorizationCodeScopeDAO()),
            new DateInterval('PT1M'),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );

        $logger = \Tuleap\OAuth2ServerCore\OAuth2ServerRoutes::getOAuth2ServerLogger();

        return new AuthorizationEndpointController(
            new RejectAuthorizationRequiringConsent(
                new AuthorizationCodeResponseFactory(
                    $response_factory,
                    $authorization_code_creator,
                    $redirect_uri_builder,
                    $url_redirect,
                    $uri_factory
                ),
                $logger
            ),
            \UserManager::instance(),
            new AppFactory(
                new AppMatchingClientIDFilterAppTypeRetriever(
                    new AppDao(),
                    MediawikiStandaloneService::SERVICE_SHORTNAME
                ),
                \ProjectManager::instance()
            ),
            new ScopeExtractor($scope_builder),
            new AuthorizationCodeResponseFactory(
                $response_factory,
                $authorization_code_creator,
                $redirect_uri_builder,
                $url_redirect,
                $uri_factory
            ),
            new PKCEInformationExtractor(),
            new PromptParameterValuesExtractor(),
            new MediawikiStandaloneOAuth2ConsentChecker(self::allowedOAuth2Scopes()),
            $logger,
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(MediawikiStandaloneService::SERVICE_SHORTNAME),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware()
        );
    }

    /**
     * @return non-empty-list<AuthenticationScope<\Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier>>
     */
    private static function allowedOAuth2Scopes(): array
    {
        return [
            OAuth2SignInScope::fromItself(),
            OpenIDConnectEmailScope::fromItself(),
            OpenIDConnectProfileScope::fromItself(),
            OAuth2ProjectReadScope::fromItself(),
            OAuth2MediawikiStandaloneReadScope::fromItself(),
        ];
    }

    #[ListeningToEventClass]
    public function collectOAuth2ScopeBuilder(OAuth2ScopeBuilderCollector $collector): void
    {
        $collector->addOAuth2ScopeBuilder(
            new AuthenticationScopeBuilderFromClassNames(
                OAuth2MediawikiStandaloneReadScope::class
            )
        );
    }

    #[ListeningToEventClass]
    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        $collector->addCommand(
            GenerateLocalSettingsCommand::NAME,
            function (): GenerateLocalSettingsCommand {
                return new GenerateLocalSettingsCommand($this->buildLocalSettingsInstantiator());
            }
        );
    }

    #[ListeningToEventClass]
    public function executeUpdateHook(PluginExecuteUpdateHookEvent $event): void
    {
        $logger = new BrokerLogger(
            [
                new TruncateLevelLogger($event->logger, LogLevel::INFO),
                $this->getBackendLogger(),
            ]
        );
        $logger->info('Execute MediaWiki update script');
        $this->buildUpdateScriptCaller($logger)->runInstallAndUpdate();
    }

    private function getMediaWikiManagementCommandProcessFactory(LoggerInterface $logger): MediaWikiManagementCommandFactory
    {
        return new MediaWikiManagementCommandProcessFactory($logger, $this->buildSettingDirectoryPath());
    }

    private function buildUpdateScriptCaller(LoggerInterface $logger): MediaWikiInstallAndUpdateScriptCaller
    {
        return new MediaWikiInstallAndUpdateScriptCaller(
            $this->getMediaWikiManagementCommandProcessFactory($logger),
            new MainpageDeployer($this->buildSettingDirectoryPath()),
            $this->buildLocalSettingsInstantiator(),
            new ProjectMediaWikiServiceDAO(),
            $logger
        );
    }

    private function buildLocalSettingsInstantiator(): LocalSettingsInstantiator
    {
        $transaction_executor = new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection());
        $hasher               = new SplitTokenVerificationStringHasher();

        return new LocalSettingsInstantiator(
            new LocalSettingsFactory(
                new MediaWikiOAuth2AppSecretGeneratorDBStore(
                    $transaction_executor,
                    new AppDao(),
                    new MediaWikiNewOAuth2AppBuilder($hasher),
                    $hasher,
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret())
                ),
                new MediaWikiSharedSecretGeneratorForgeConfigStore(new ConfigDao()),
                new MediaWikiCentralDatabaseParameter(PluginManager::instance()),
            ),
            new LocalSettingsPersistToPHPFile(
                $this->buildSettingDirectoryPath(),
                new PHPStringMustacheRenderer(new TemplateCache(), __DIR__ . '/../templates/')
            ),
            $transaction_executor
        );
    }

    private function buildSettingDirectoryPath(): string
    {
        return ForgeConfig::get('sys_custompluginsroot') . '/' . $this->getName();
    }

    #[ListeningToEventClass]
    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(
        NavigationDropdownQuickLinksCollector $quick_links_collector,
    ): void {
        $project = $quick_links_collector->getProject();
        $service = $project->getService(MediawikiStandaloneService::SERVICE_SHORTNAME);
        if (! $service instanceof MediawikiStandaloneService) {
            return;
        }

        if (! $this->isAllowed($project->getID())) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone'),
                AdminPermissionsController::getAdminUrl($project),
            )
        );
    }

    #[ListeningToEventClass]
    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event): void
    {
        $project = $event->getProject();
        $service = $project->getService(MediawikiStandaloneService::SERVICE_SHORTNAME);
        if (! $service instanceof MediawikiStandaloneService) {
            return;
        }

        if (! $this->isAllowed($project->getID())) {
            return;
        }

        $ugroup_manager = new UGroupManager();

        $dao                  = new MediawikiPermissionsDao();
        $service_pane_builder = new PermissionPerGroupServicePaneBuilder(
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            new ProjectPermissionsRetriever($dao),
            $ugroup_manager,
        );

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(__DIR__ . '/../templates')
            ->renderToString(
                'project-admin-permission-per-group',
                $service_pane_builder->buildPresenter($event)
            );

        $rank_in_project = $service->getRank();
        $event->addPane($admin_permission_pane, $rank_in_project);
    }

    #[ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $event): void
    {
        $event->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki Standalone'),
                DisplayMigrationController::URL,
            )
        );
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event): void
    {
    }
}
