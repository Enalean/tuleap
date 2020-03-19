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
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\Authentication\BasicAuthLoginExtractor;
use Tuleap\Http\Server\DisableCacheMiddleware;
use Tuleap\Http\Server\RejectNonHTTPSRequestMiddleware;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenCreator;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\OAuth2Server\AccessToken\Scope\OAuth2AccessTokenScopeDAO;
use Tuleap\OAuth2Server\AccessToken\Scope\OAuth2AccessTokenScopeRetriever;
use Tuleap\OAuth2Server\AccessToken\Scope\OAuth2AccessTokenScopeSaver;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2Server\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointGetController;
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2Server\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantController;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\AuthorizationCodeGrantResponseBuilder;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeVerifier;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeRetriever;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeSaver;
use Tuleap\OAuth2Server\Grant\OAuth2ClientAuthenticationMiddleware;
use Tuleap\OAuth2Server\ProjectAdmin\ListAppsController;
use Tuleap\Project\Admin\Navigation\NavigationItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\OAuth2\AccessToken\PrefixOAuth2AccessToken;
use Tuleap\User\OAuth2\AccessToken\VerifyOAuth2AccessTokenEvent;
use Tuleap\User\OAuth2\BearerTokenHeaderParser;
use Tuleap\User\OAuth2\Scope\DemoOAuth2Scope;
use Tuleap\User\OAuth2\Scope\OAuth2ProjectReadScope;
use Tuleap\User\PasswordVerifier;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class oauth2_serverPlugin extends Plugin
{
    public const SERVICE_NAME_INSTRUMENTATION = 'oauth2_server';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-oauth2_server', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(NavigationPresenter::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);
        $this->addHook(VerifyOAuth2AccessTokenEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-oauth2_server', 'OAuth2 Server'),
                    '',
                    dgettext('tuleap-oauth2_server', 'Delegate access to Tuleap resources')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter): void
    {
        $project_id = urlencode((string) $presenter->getProjectId());
        $html_url   = $this->getPluginPath() . "/project/$project_id/admin";
        $presenter->addItem(
            new NavigationItemPresenter(
                dgettext('tuleap-oauth2_server', 'OAuth2 Apps'),
                $html_url,
                ListAppsController::PANE_SHORTNAME,
                $presenter->getCurrentPaneShortname()
            )
        );
    }

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
                $r->get('/account/apps', $this->getRouteHandler('routeGetAccountApps'));
                $r->get(
                    '/testendpoint',
                    $this->getRouteHandler('routeTestEndpoint')
                );
            }
        );
        $route_collector->addGroup(
            '/oauth2',
            function (FastRoute\RouteCollector $r): void {
                $r->get(
                    '/authorize',
                    $this->getRouteHandler('routeAuthorizationEndpointGet')
                );
                $r->post(
                    '/authorize',
                    $this->getRouteHandler('routeAuthorizationEndpointPost')
                );
                $r->post(
                    '/token',
                    $this->getRouteHandler('routeAccessTokenCreation')
                );
            }
        );
    }

    public function routeGetProjectAdmin(): DispatchableWithRequest
    {
        return ListAppsController::buildSelf();
    }

    public function routePostProjectAdmin(): DispatchableWithRequest
    {
        return \Tuleap\OAuth2Server\ProjectAdmin\AddAppController::buildSelf();
    }

    public function routeDeleteProjectAdmin(): DispatchableWithRequest
    {
        return \Tuleap\OAuth2Server\ProjectAdmin\DeleteAppController::buildSelf();
    }

    public function routeAuthorizationEndpointGet(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory = HTTPFactoryBuilder::streamFactory();
        $redirect_uri_builder = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
        $scope_builder = $this->buildScopeBuilder();
        return new AuthorizationEndpointGetController(
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormRenderer(
                $response_factory,
                $stream_factory,
                TemplateRendererFactory::build(),
                new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormPresenterBuilder($redirect_uri_builder)
            ),
            \UserManager::instance(),
            new AppFactory(new AppDao(), \ProjectManager::instance()),
            new \Tuleap\OAuth2Server\AuthorizationServer\ScopeExtractor($scope_builder),
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationCodeResponseFactory(
                $response_factory,
                $this->buildOAuth2AuthorizationCodeCreator(),
                $redirect_uri_builder,
                new \URLRedirect(\EventManager::instance())
            ),
            new \Tuleap\OAuth2Server\User\AuthorizationComparator(
                new \Tuleap\OAuth2Server\User\AuthorizedScopeFactory(
                    new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                    new \Tuleap\OAuth2Server\User\AuthorizationScopeDao(),
                    $scope_builder
                )
            ),
            new PKCEInformationExtractor(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware()
        );
    }

    public function routeAuthorizationEndpointPost(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointPostController(
            \UserManager::instance(),
            new AppFactory(new AppDao(), ProjectManager::instance()),
            $this->buildScopeBuilder(),
            new \Tuleap\OAuth2Server\User\AuthorizationCreator(
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                new \Tuleap\OAuth2Server\User\AuthorizationDao(),
                new \Tuleap\OAuth2Server\User\AuthorizationScopeDao()
            ),
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationCodeResponseFactory(
                $response_factory,
                $this->buildOAuth2AuthorizationCodeCreator(),
                new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
                new \URLRedirect(\EventManager::instance())
            ),
            new \CSRFSynchronizerToken(AuthorizationEndpointGetController::CSRF_TOKEN),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, HTTPFactoryBuilder::streamFactory()),
            new DisableCacheMiddleware()
        );
    }

    public function routeTestEndpoint(): \Tuleap\OAuth2Server\TestEndpointController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        $password_handler = \PasswordHandlerFactory::getPasswordHandler();
        return new \Tuleap\OAuth2Server\TestEndpointController(
            $response_factory,
            $stream_factory,
            UserManager::instance(),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new \Tuleap\User\OAuth2\ResourceServer\OAuth2ResourceServerMiddleware(
                $response_factory,
                new BearerTokenHeaderParser(),
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                EventManager::instance(),
                DemoOAuth2Scope::fromItself(),
                new User_LoginManager(
                    EventManager::instance(),
                    UserManager::instance(),
                    new PasswordVerifier($password_handler),
                    new User_PasswordExpirationChecker(),
                    $password_handler
                )
            )
        );
    }

    public function routeAccessTokenCreation(): AccessTokenGrantController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        $app_dao          = new AppDao();
        return new AccessTokenGrantController(
            $response_factory,
            $stream_factory,
            new AuthorizationCodeGrantResponseBuilder(
                new OAuth2AccessTokenCreator(
                    new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                    new SplitTokenVerificationStringHasher(),
                    new OAuth2AccessTokenDAO(),
                    new OAuth2AccessTokenScopeSaver(new OAuth2AccessTokenScopeDAO()),
                    new DateInterval('PT1H'),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                )
            ),
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new OAuth2AuthorizationCodeVerifier(
                new SplitTokenVerificationStringHasher(),
                UserManager::instance(),
                new OAuth2AuthorizationCodeDAO(),
                new OAuth2AuthorizationCodeScopeRetriever(new OAuth2AuthorizationCodeScopeDAO(), $this->buildScopeBuilder()),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
            new DisableCacheMiddleware(),
            new OAuth2ClientAuthenticationMiddleware(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                new OAuth2AppCredentialVerifier(
                    new AppFactory($app_dao, ProjectManager::instance()),
                    $app_dao,
                    new SplitTokenVerificationStringHasher()
                ),
                new BasicAuthLoginExtractor(),
                BackendLogger::getDefaultLogger()
            )
        );
    }

    public function routeGetAccountApps(): DispatchableWithRequest
    {
        return new \Tuleap\OAuth2Server\User\Account\AccountAppsController(
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
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
        );
    }

    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        (new \Tuleap\OAuth2Server\User\Account\AppsTabAdder())->addTabs($collection);
    }

    public function verifyAccessToken(VerifyOAuth2AccessTokenEvent $event): void
    {
        $verifier = new OAuth2AccessTokenVerifier(
            new OAuth2AccessTokenDAO(),
            new OAuth2AccessTokenScopeRetriever(
                new OAuth2AccessTokenScopeDAO(),
                $this->buildScopeBuilder()
            ),
            UserManager::instance(),
            new SplitTokenVerificationStringHasher()
        );

        $user = $verifier->getUser($event->getAccessToken(), $event->getRequiredScope());
        $event->setVerifiedUser($user);
    }

    private function buildScopeBuilder(): AuthenticationScopeBuilder
    {
        return new AuthenticationScopeBuilderFromClassNames(
            DemoOAuth2Scope::class,
            OAuth2ProjectReadScope::class
        );
    }

    private function buildOAuth2AuthorizationCodeCreator(): OAuth2AuthorizationCodeCreator
    {
        return new OAuth2AuthorizationCodeCreator(
            new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
            new SplitTokenVerificationStringHasher(),
            new OAuth2AuthorizationCodeDAO(),
            new OAuth2AuthorizationCodeScopeSaver(new OAuth2AuthorizationCodeScopeDAO()),
            new DateInterval('PT1M'),
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
        );
    }
}
