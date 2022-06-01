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
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\GetConfigKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\MediawikiStandalone\Configuration\GenerateLocalSettingsCommand;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsFactory;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsInstantiator;
use Tuleap\MediawikiStandalone\Configuration\LocalSettingsPersistToPHPFile;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiNewOAuth2AppBuilder;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiOAuth2AppSecretGeneratorDBStore;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiSharedSecretGeneratorForgeConfigStore;
use Tuleap\MediawikiStandalone\Configuration\MediaWikiUpdateScriptCaller;
use Tuleap\MediawikiStandalone\Configuration\MustachePHPString\PHPStringMustacheRenderer;
use Tuleap\MediawikiStandalone\Instance\InstanceManagement;
use Tuleap\MediawikiStandalone\Instance\MediawikiHTTPClientFactory;
use Tuleap\MediawikiStandalone\OAuth2\MediawikiStandaloneOAuth2ConsentChecker;
use Tuleap\MediawikiStandalone\OAuth2\RejectAuthorizationRequiringConsent;
use Tuleap\MediawikiStandalone\REST\MediawikiStandaloneResourcesInjector;
use Tuleap\MediawikiStandalone\REST\OAuth2\OAuth2MediawikiStandaloneReadScope;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\MediawikiStandalone\Service\ServiceActivationHandler;
use Tuleap\MediawikiStandalone\Service\ServiceActivationProjectServiceBeforeActivationEvent;
use Tuleap\MediawikiStandalone\Service\ServiceActivationServiceDisabledCollectorEvent;
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
use Tuleap\PluginsAdministration\LifecycleHookCommand\PluginExecuteUpdateHookEvent;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Project\Service\AddMissingService;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Templating\TemplateCache;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ProjectReadScope;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../mediawiki/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class mediawiki_standalonePlugin extends Plugin
{
    public const SERVICE_SHORTNAME   = 'plugin_mediawiki_standalone';
    private const SERVICE_URL_PREFIX = '/mediawiki/';

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
                    '',
                    dgettext('tuleap-mediawiki_standalone', 'Standalone MediaWiki instances integration with Tuleap')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['mediawiki'];
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(ProjectServiceBeforeActivation::NAME);
        $this->addHook(ServiceDisabledCollector::NAME);
        $this->addHook(AddMissingService::NAME);

        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CLICommandsCollector::NAME);
        $this->addHook(PluginExecuteUpdateHookEvent::NAME);
        $this->addHook(WorkerEvent::NAME);
        $this->addHook(GetConfigKeys::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname(): string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function postInstall(): void
    {
        parent::postInstall();
        $this->buildLocalSettingsInstantiator()->instantiateLocalSettings();
    }

    public function serviceClassnames(array &$params): void
    {
        $params['classnames'][$this->getServiceShortname()] = MediawikiStandaloneService::class;
    }

    /**
     * @param array{shortname: string, is_used: bool, group_id: int|string} $params
     */
    public function serviceIsUsed(array $params): void
    {
        if ($params['shortname'] === self::SERVICE_SHORTNAME && $params['is_used']) {
            /*(new EnqueueTask())->enqueue(
                new InstanceCreationWorkerEvent((int) $params['group_id'])
            );*/
        }
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl(self::SERVICE_URL_PREFIX . $collector->getProject()->getUnixNameLowerCase());
        }
    }

    public function projectServiceBeforeActivation(ProjectServiceBeforeActivation $event): void
    {
        (new ServiceActivationHandler())->handle(new ServiceActivationProjectServiceBeforeActivationEvent($event));
    }

    public function serviceDisabledCollector(ServiceDisabledCollector $event): void
    {
        (new ServiceActivationHandler())->handle(new ServiceActivationServiceDisabledCollectorEvent($event));
    }

    public function addMissingService(AddMissingService $event): void
    {
        if (! $this->isServiceAllowedForProject($event->project)) {
            return;
        }

        $event->addService(
            MediawikiStandaloneService::forServiceCreation(
                $event->project
            )
        );
    }

    public function workerEvent(WorkerEvent $event): void
    {
        (new InstanceManagement(
            $this->getBackendLogger(),
            new MediawikiHTTPClientFactory(),
            HTTPFactoryBuilder::requestFactory(),
            ProjectManager::instance(),
        ))->process($event);
    }

    public function getConfigKeys(GetConfigKeys $event): void
    {
        $event->addConfigClass(MediawikiHTTPClientFactory::class);
    }

    /**
     * @see         Event::REST_RESOURCES
     *
     * @psalm-param array{restler: \Luracast\Restler\Restler} $params
     */
    public function restResources(array $params): void
    {
        $injector = new MediawikiStandaloneResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $route_collector = $event->getRouteCollector();

        $route_collector->addRoute(['GET', 'POST'], '/mediawiki_standalone/oauth2_authorize', $this->getRouteHandler('routeAuthorizationEndpoint'));
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
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new OAuth2ScopeBuilderCollector())
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
                new AppMatchingClientIDFilterAppTypeRetriever(new AppDao(), self::SERVICE_SHORTNAME),
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
            new ServiceInstrumentationMiddleware(self::SERVICE_SHORTNAME),
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

    public function collectCLICommands(CLICommandsCollector $collector): void
    {
        $collector->addCommand(
            GenerateLocalSettingsCommand::NAME,
            function (): GenerateLocalSettingsCommand {
                return new GenerateLocalSettingsCommand($this->buildLocalSettingsInstantiator());
            }
        );
    }

    public function executeUpdateHook(PluginExecuteUpdateHookEvent $event): void
    {
        $logger = new BrokerLogger(
            [
                new TruncateLevelLogger($event->logger, \Psr\Log\LogLevel::INFO),
                $this->getBackendLogger(),
            ]
        );
        $logger->info('Execute MediaWiki update script');
        (new MediaWikiUpdateScriptCaller($logger))->runUpdate();
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
                new MediaWikiSharedSecretGeneratorForgeConfigStore(new ConfigDao())
            ),
            new LocalSettingsPersistToPHPFile(
                ForgeConfig::get('sys_custompluginsroot') . '/' . $this->getName(),
                new PHPStringMustacheRenderer(new TemplateCache(), __DIR__ . '/../templates/')
            ),
            $transaction_executor
        );
    }
}
