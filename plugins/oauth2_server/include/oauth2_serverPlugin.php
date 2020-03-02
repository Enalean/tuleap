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
use Tuleap\OAuth2Server\AccessToken\Scope\OAuth2AccessTokenScopeSaver;
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2Server\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2Server\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2Server\Grant\AuthCodeGrantController;
use Tuleap\OAuth2Server\Grant\AuthorizationCodeGrantResponseBuilder;
use Tuleap\OAuth2Server\Grant\OAuth2ClientAuthenticationMiddleware;
use Tuleap\OAuth2Server\ProjectAdmin\ListAppsController;
use Tuleap\Project\Admin\Navigation\NavigationItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenDAO;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenVerifier;
use Tuleap\User\OAuth2\AccessToken\PrefixOAuth2AccessToken;
use Tuleap\User\OAuth2\AccessToken\Scope\OAuth2AccessTokenScopeDAO;
use Tuleap\User\OAuth2\AccessToken\Scope\OAuth2AccessTokenScopeRetriever;
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
        $project_id = urlencode((string)$presenter->getProjectId());
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
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        return new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointGetController(
            $response_factory,
            new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormRenderer(
                $response_factory,
                $stream_factory,
                TemplateRendererFactory::build(),
                new \Tuleap\OAuth2Server\AuthorizationServer\AuthorizationFormPresenterBuilder()
            ),
            \UserManager::instance(),
            new AppFactory(new AppDao(), \ProjectManager::instance()),
            new \URLRedirect(\EventManager::instance()),
            new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory()),
            new \Tuleap\OAuth2Server\AuthorizationServer\ScopeExtractor(
                new AuthenticationScopeBuilderFromClassNames(DemoOAuth2Scope::class, OAuth2ProjectReadScope::class)
            ),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new RejectNonHTTPSRequestMiddleware($response_factory, $stream_factory),
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
                new OAuth2AccessTokenVerifier(
                    new OAuth2AccessTokenDAO(),
                    new OAuth2AccessTokenScopeRetriever(
                        new OAuth2AccessTokenScopeDAO(),
                        new AuthenticationScopeBuilderFromClassNames(
                            DemoOAuth2Scope::class
                        )
                    ),
                    UserManager::instance(),
                    new SplitTokenVerificationStringHasher()
                ),
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

    public function routeAccessTokenCreation(): AuthCodeGrantController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        $app_dao          = new AppDao();
        return new AuthCodeGrantController(
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
            UserManager::instance(),
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
}
