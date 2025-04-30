<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\OAuth2Server\Administration\OAuth2AppProjectVerifier;
use Tuleap\OAuth2Server\Administration\ProjectAdmin\ListAppsController;
use Tuleap\OAuth2Server\Administration\SiteAdmin\SiteAdminListAppsController;
use Tuleap\OAuth2Server\AuthorizationServer\OAuth2ConsentRequiredResponseBuilder;
use Tuleap\OAuth2Server\AuthorizationServer\OAuth2ConsentChecker;
use Tuleap\OAuth2ServerCore\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\OAuth2ServerCore\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\LastGeneratedClientSecretStore;
use Tuleap\OAuth2Server\App\OAuth2AppRemover;
use Tuleap\OAuth2ServerCore\App\AppMatchingClientIDFilterAppTypeRetriever;
use Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationEndpointController;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\PromptParameterValuesExtractor;
use Tuleap\OAuth2ServerCore\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2ServerCore\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2ServerCore\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2ServerCore\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2Server\REST\Specification\Swagger\SwaggerJsonOAuth2SecurityDefinition;
use Tuleap\OAuth2ServerCore\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2ServerCore\Scope\ScopeExtractor;
use Tuleap\OAuth2Server\User\Account\AccountAppsController;
use Tuleap\OAuth2Server\User\AuthorizationDao;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;
use Tuleap\REST\Specification\Swagger\SwaggerJsonSecurityDefinitionsCollection;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\Account\PasswordUserPostUpdateEvent;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class oauth2_serverPlugin extends Plugin
{
    public const SERVICE_NAME_INSTRUMENTATION = 'oauth2_server';
    public const CSRF_TOKEN_APP_EDITION       = 'oauth2_server_app_edition';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-oauth2_server', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
                    dgettext('tuleap-oauth2_server', 'Delegate access to Tuleap resources')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter): void
    {
        $project_id = urlencode((string) $presenter->getProjectId());
        $html_url   = $this->getPluginPath() . "/project/$project_id/admin";
        $presenter->addDropdownItem(
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-oauth2_server', 'OAuth2 Apps'),
                $html_url
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $route_collector = $routes->getRouteCollector();
        $route_collector->addGroup(
            $this->getPluginPath(),
            function (FastRoute\RouteCollector $r): void {
                $r->get(
                    '/project/{project_id:\d+}/admin',
                    $this->getRouteHandler('routeGetProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/add-app',
                    $this->getRouteHandler('routePostProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/delete-app',
                    $this->getRouteHandler('routeDeleteProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/new-client-secret',
                    $this->getRouteHandler('routeNewClientSecretProjectAdmin')
                );
                $r->post(
                    '/project/{project_id:\d+}/admin/edit-app',
                    $this->getRouteHandler('routeEditProjectApp')
                );
                $r->get('/account/apps', $this->getRouteHandler('routeGetAccountApps'));
                $r->post('/account/apps/revoke', $this->getRouteHandler('routePostAccountAppRevoke'));
                $r->get(
                    '/admin',
                    $this->getRouteHandler('routeGetSiteAdmin')
                );
                $r->post(
                    '/admin/add-app',
                    $this->getRouteHandler('routePostSiteAdmin')
                );
                $r->post(
                    '/admin/delete-app',
                    $this->getRouteHandler('routeDeleteSiteAdmin')
                );
                $r->post(
                    '/admin/edit-app',
                    $this->getRouteHandler('routeEditSiteApp')
                );
                $r->post(
                    '/admin/new-client-secret',
                    $this->getRouteHandler('routeNewClientSecretSiteAdmin')
                );
            }
        );
        $route_collector->addGroup(
            '/oauth2',
            function (FastRoute\RouteCollector $r): void {
                $r->addRoute(
                    ['GET', 'POST'],
                    '/authorize',
                    $this->getRouteHandler('routeAuthorizationEndpoint')
                );
                $r->post(
                    '/authorize-process-consent',
                    $this->getRouteHandler('routeAuthorizationProcessConsentEndpoint')
                );
            }
        );
        $route_collector->addRoute('GET', '/.well-known/openid-configuration', $this->getRouteHandler('routeDiscovery'));
    }

    public function routeGetProjectAdmin(): DispatchableWithRequest
    {
        return ListAppsController::buildSelf();
    }

    public function routeGetSiteAdmin(): DispatchableWithRequest
    {
        return new SiteAdminListAppsController(
            new AdminPageRenderer(),
            UserManager::instance(),
            \Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenterBuilder::buildSelf(),
            new \Tuleap\Layout\IncludeViteAssets(__DIR__ . '/../frontend-assets', '/assets/oauth2_server'),
            new CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION)
        );
    }

    public function routePostProjectAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\Administration\AddAppController(
            $response_factory,
            new AppDao(),
            new SplitTokenVerificationStringHasher(),
            new LastGeneratedClientSecretStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routePostSiteAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\Administration\AddAppController(
            $response_factory,
            new AppDao(),
            new SplitTokenVerificationStringHasher(),
            new LastGeneratedClientSecretStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeDeleteProjectAdmin(): DispatchableWithRequest
    {
        $app_dao = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\DeleteAppController(
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new OAuth2AppRemover(
                $app_dao,
                new OAuth2AuthorizationCodeDAO(),
                new AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeDeleteSiteAdmin(): DispatchableWithRequest
    {
        $app_dao = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\DeleteAppController(
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new OAuth2AppRemover(
                $app_dao,
                new OAuth2AuthorizationCodeDAO(),
                new AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeNewClientSecretProjectAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\NewClientSecretController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new \Tuleap\OAuth2Server\App\ClientSecretUpdater(
                new SplitTokenVerificationStringHasher(),
                $app_dao,
                new LastGeneratedClientSecretStore(
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                    (new KeyFactory())->getEncryptionKey(),
                    $storage
                )
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeNewClientSecretSiteAdmin(): DispatchableWithRequest
    {
        $storage          =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\NewClientSecretController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            new \Tuleap\OAuth2Server\App\ClientSecretUpdater(
                new SplitTokenVerificationStringHasher(),
                $app_dao,
                new LastGeneratedClientSecretStore(
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                    (new KeyFactory())->getEncryptionKey(),
                    $storage
                )
            ),
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeEditProjectApp(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\EditAppController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            $app_dao,
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeEditSiteApp(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $app_dao          = new AppDao();
        return new \Tuleap\OAuth2Server\Administration\EditAppController(
            $response_factory,
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppProjectVerifier($app_dao),
            $app_dao,
            new \CSRFSynchronizerToken(self::CSRF_TOKEN_APP_EDITION),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Admin\RejectNonSiteAdministratorMiddleware(UserManager::instance())
        );
    }

    public function routeAuthorizationEndpoint(): DispatchableWithRequest
    {
        $response_factory     = HTTPFactoryBuilder::responseFactory();
        $stream_factory       = HTTPFactoryBuilder::streamFactory();
        $redirect_uri_builder = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
        $scope_builder        = $this->buildScopeBuilder();
        return new AuthorizationEndpointController(
            new OAuth2ConsentRequiredResponseBuilder(
                new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormRenderer(
                    $response_factory,
                    $stream_factory,
                    TemplateRendererFactory::build(),
                    new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormPresenterBuilder($redirect_uri_builder)
                ),
            ),
            \UserManager::instance(),
            new \Tuleap\OAuth2ServerCore\App\AppFactory(
                new AppMatchingClientIDFilterAppTypeRetriever(new AppDao(), AppFactory::PLUGIN_APP),
                \ProjectManager::instance()
            ),
            new ScopeExtractor($scope_builder),
            new \Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory(
                $response_factory,
                $this->buildOAuth2AuthorizationCodeCreator(),
                $redirect_uri_builder,
                new \URLRedirect(\EventManager::instance()),
                HTTPFactoryBuilder::URIFactory()
            ),
            new PKCEInformationExtractor(),
            new PromptParameterValuesExtractor(),
            new OAuth2ConsentChecker(
                new \Tuleap\OAuth2Server\User\AuthorizationComparator(
                    new \Tuleap\OAuth2Server\User\AuthorizedScopeFactory(
                        new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                        new \Tuleap\OAuth2Server\User\AuthorizationScopeDao(),
                        $scope_builder
                    )
                ),
                OAuth2OfflineAccessScope::fromItself(),
            ),
            \Tuleap\OAuth2ServerCore\OAuth2ServerRoutes::getOAuth2ServerLogger(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware()
        );
    }

    public function routeAuthorizationProcessConsentEndpoint(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointProcessConsentController(
            \UserManager::instance(),
            new \Tuleap\OAuth2ServerCore\App\AppFactory(new AppDao(), ProjectManager::instance()),
            $this->buildScopeBuilder(),
            new \Tuleap\OAuth2Server\User\AuthorizationCreator(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                new \Tuleap\OAuth2Server\User\AuthorizationScopeDao()
            ),
            new \Tuleap\OAuth2ServerCore\AuthorizationServer\AuthorizationCodeResponseFactory(
                $response_factory,
                $this->buildOAuth2AuthorizationCodeCreator(),
                new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
                new \URLRedirect(\EventManager::instance()),
                HTTPFactoryBuilder::URIFactory()
            ),
            new \CSRFSynchronizerToken(AuthorizationEndpointController::CSRF_TOKEN),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, HTTPFactoryBuilder::streamFactory()),
            new DisableCacheMiddleware()
        );
    }

    public function routeGetAccountApps(): DispatchableWithRequest
    {
        return new AccountAppsController(
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            new \Tuleap\OAuth2Server\User\Account\AppsPresenterBuilder(
                EventManager::instance(),
                new AppFactory(new AppDao(), \ProjectManager::instance()),
                new \Tuleap\OAuth2Server\User\AuthorizedScopeFactory(
                    new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                    new \Tuleap\OAuth2Server\User\AuthorizationScopeDao(),
                    $this->buildScopeBuilder()
                )
            ),
            TemplateRendererFactory::build(),
            UserManager::instance(),
            AccountAppsController::getCSRFToken(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION)
        );
    }

    public function routePostAccountAppRevoke(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new Tuleap\OAuth2Server\User\Account\AppRevocationController(
            $response_factory,
            AccountAppsController::getCSRFToken(),
            UserManager::instance(),
            new \Tuleap\OAuth2Server\User\AuthorizationRevoker(
                new OAuth2AuthorizationCodeDAO(),
                new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION)
        );
    }

    public function routeDiscovery(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        return new \Tuleap\OAuth2Server\OpenIDConnect\Discovery\DiscoveryController(
            new \Tuleap\OAuth2Server\OpenIDConnect\Discovery\ConfigurationResponseRepresentationBuilder(
                new \BaseLanguageFactory(),
                $this->buildScopeBuilder()
            ),
            new JSONResponseBuilder($response_factory, $stream_factory),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory)
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        (new \Tuleap\OAuth2Server\User\Account\AppsTabAdder())->addTabs($collection);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectOAuth2ScopeBuilder(OAuth2ScopeBuilderCollector $collector): void
    {
        $collector->addOAuth2ScopeBuilder(
            new AuthenticationScopeBuilderFromClassNames(
                OAuth2OfflineAccessScope::class,
            )
        );
    }

    private function buildScopeBuilder(): AuthenticationScopeBuilder
    {
        return AggregateAuthenticationScopeBuilder::fromBuildersList(
            CoreOAuth2ScopeBuilderFactory::buildCoreOAuth2ScopeBuilder(),
            AggregateAuthenticationScopeBuilder::fromEventDispatcher(\EventManager::instance(), new OAuth2ScopeBuilderCollector())
        );
    }

    private function buildOAuth2AuthorizationCodeCreator(): OAuth2AuthorizationCodeCreator
    {
        return new OAuth2AuthorizationCodeCreator(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new SplitTokenVerificationStringHasher(),
            new OAuth2AuthorizationCodeDAO(),
            new OAuth2ScopeSaver(new OAuth2AuthorizationCodeScopeDAO()),
            new DateInterval('PT1M'),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName('codendi_daily_start')]
    public function codendiDailyStart(): void
    {
        $current_time = (new DateTimeImmutable())->getTimestamp();
        (new OAuth2AccessTokenDAO())->deleteByExpirationDate($current_time);
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeByExpirationDate($current_time);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        if ($event->status === \Project::STATUS_DELETED) {
            (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeInNonExistingOrDeletedProject();
            (new AuthorizationDao())->deleteAuthorizationsInNonExistingOrDeletedProject();
            (new AppDao())->deleteAppsInNonExistingOrDeletedProject();
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function retrieveRESTSwaggerJsonSecurityDefinitions(SwaggerJsonSecurityDefinitionsCollection $collection): void
    {
        $collection->addSecurityDefinition(
            SwaggerJsonSecurityDefinitionsCollection::TYPE_NAME_OAUTH2,
            new SwaggerJsonOAuth2SecurityDefinition(
                $this->buildScopeBuilder(),
                new LocaleSwitcher()
            )
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function passwordUserPostUpdateEvent(PasswordUserPostUpdateEvent $password_user_post_update_event): void
    {
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeByUser($password_user_post_update_event->getUser());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
                $this->getPluginPath() . '/admin'
            )
        );
    }
}
