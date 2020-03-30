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
use Tuleap\Authentication\Scope\AggregateAuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilder;
use Tuleap\Authentication\Scope\AuthenticationScopeBuilderFromClassNames;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\KeyFactory;
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
use Tuleap\OAuth2Server\App\AppDao;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\LastCreatedOAuth2AppStore;
use Tuleap\OAuth2Server\App\OAuth2AppCredentialVerifier;
use Tuleap\OAuth2Server\App\OAuth2AppRemover;
use Tuleap\OAuth2Server\App\PrefixOAuth2ClientSecret;
use Tuleap\OAuth2Server\AuthorizationServer\AuthorizationEndpointGetController;
use Tuleap\OAuth2Server\AuthorizationServer\PKCE\PKCEInformationExtractor;
use Tuleap\OAuth2Server\AuthorizationServer\RedirectURIBuilder;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantController;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantErrorResponseBuilder;
use Tuleap\OAuth2Server\Grant\AccessTokenGrantRepresentationBuilder;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeCreator;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeDAO;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeVerifier;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2GrantAccessTokenFromAuthorizationCode;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\PKCECodeVerifier;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PrefixOAuth2AuthCode;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\Scope\OAuth2AuthorizationCodeScopeDAO;
use Tuleap\OAuth2Server\Grant\OAuth2ClientAuthenticationMiddleware;
use Tuleap\OAuth2Server\Grant\RefreshToken\OAuth2GrantAccessTokenFromRefreshToken;
use Tuleap\OAuth2Server\OpenIDConnect\Scope\OAuth2SignInScope;
use Tuleap\OAuth2Server\ProjectAdmin\ListAppsController;
use Tuleap\OAuth2Server\RefreshToken\OAuth2OfflineAccessScope;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenCreator;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenDAO;
use Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenVerifier;
use Tuleap\OAuth2Server\RefreshToken\PrefixOAuth2RefreshToken;
use Tuleap\OAuth2Server\RefreshToken\Scope\OAuth2RefreshTokenScopeDAO;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeRetriever;
use Tuleap\OAuth2Server\Scope\OAuth2ScopeSaver;
use Tuleap\OAuth2Server\Scope\ScopeExtractor;
use Tuleap\OAuth2Server\User\Account\AccountAppsController;
use Tuleap\OAuth2Server\User\AuthorizationDao;
use Tuleap\Project\Admin\Navigation\NavigationItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;
use Tuleap\User\Account\AccountTabPresenterCollection;
use Tuleap\User\OAuth2\AccessToken\PrefixOAuth2AccessToken;
use Tuleap\User\OAuth2\AccessToken\VerifyOAuth2AccessTokenEvent;
use Tuleap\User\OAuth2\Scope\CoreOAuth2ScopeBuilderFactory;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeBuilderCollector;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class oauth2_serverPlugin extends Plugin
{
    public const SERVICE_NAME_INSTRUMENTATION = 'oauth2_server';

    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-oauth2_server', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(NavigationPresenter::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(AccountTabPresenterCollection::NAME);
        $this->addHook(VerifyOAuth2AccessTokenEvent::NAME);
        $this->addHook(OAuth2ScopeBuilderCollector::NAME);
        $this->addHook('codendi_daily_start', 'dailyCleanup');
        $this->addHook('project_is_deleted', 'projectIsDeleted');

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
                $r->post('/account/apps/revoke', $this->getRouteHandler('routePostAccountAppRevoke'));
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
                $r->post('/token', $this->getRouteHandler('routeAccessTokenCreation'));
                $r->post('/token/revoke', $this->getRouteHandler('routeTokenRevocation'));
            }
        );
    }

    public function routeGetProjectAdmin(): DispatchableWithRequest
    {
        return ListAppsController::buildSelf();
    }

    public function routePostProjectAdmin(): DispatchableWithRequest
    {
        $storage =& $_SESSION ?? [];
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new \Tuleap\OAuth2Server\ProjectAdmin\AddAppController(
            $response_factory,
            new AppDao(),
            new SplitTokenVerificationStringHasher(),
            new LastCreatedOAuth2AppStore(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2ClientSecret()),
                (new KeyFactory())->getEncryptionKey(),
                $storage
            ),
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                $response_factory,
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new \CSRFSynchronizerToken(ListAppsController::CSRF_TOKEN),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
    }

    public function routeDeleteProjectAdmin(): DispatchableWithRequest
    {
        return new \Tuleap\OAuth2Server\ProjectAdmin\DeleteAppController(
            new \Tuleap\Http\Response\RedirectWithFeedbackFactory(
                HTTPFactoryBuilder::responseFactory(),
                new \Tuleap\Layout\Feedback\FeedbackSerializer(new FeedbackDao())
            ),
            new OAuth2AppRemover(
                new AppDao(),
                new OAuth2AuthorizationCodeDAO(),
                new AuthorizationDao(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new \CSRFSynchronizerToken(ListAppsController::CSRF_TOKEN),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION),
            new \Tuleap\Project\Routing\ProjectRetrieverMiddleware(ProjectRetriever::buildSelf()),
            new \Tuleap\Project\Admin\Routing\RejectNonProjectAdministratorMiddleware(
                UserManager::instance(),
                new ProjectAdministratorChecker()
            )
        );
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
            new ScopeExtractor($scope_builder),
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

    public function routeAccessTokenCreation(): AccessTokenGrantController
    {
        $response_factory                          = HTTPFactoryBuilder::responseFactory();
        $stream_factory                            = HTTPFactoryBuilder::streamFactory();
        $app_dao                                   = new AppDao();
        $access_token_grant_error_response_builder = new AccessTokenGrantErrorResponseBuilder(
            $response_factory,
            $stream_factory
        );
        $access_token_grant_representation_builder = new AccessTokenGrantRepresentationBuilder(
            new OAuth2AccessTokenCreator(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                new SplitTokenVerificationStringHasher(),
                new OAuth2AccessTokenDAO(),
                new OAuth2ScopeSaver(new OAuth2AccessTokenScopeDAO()),
                new DateInterval('PT1H'),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            ),
            new OAuth2RefreshTokenCreator(
                OAuth2OfflineAccessScope::fromItself(),
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                new SplitTokenVerificationStringHasher(),
                new OAuth2RefreshTokenDAO(),
                new OAuth2ScopeSaver(new OAuth2RefreshTokenScopeDAO()),
                new DateInterval('PT6H'),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            )
        );
        return new AccessTokenGrantController(
            $access_token_grant_error_response_builder,
            new OAuth2GrantAccessTokenFromAuthorizationCode(
                $response_factory,
                $stream_factory,
                $access_token_grant_error_response_builder,
                $access_token_grant_representation_builder,
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AuthCode()),
                new OAuth2AuthorizationCodeVerifier(
                    new SplitTokenVerificationStringHasher(),
                    UserManager::instance(),
                    new OAuth2AuthorizationCodeDAO(),
                    new OAuth2ScopeRetriever(new OAuth2AuthorizationCodeScopeDAO(), $this->buildScopeBuilder()),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                new PKCECodeVerifier()
            ),
            new OAuth2GrantAccessTokenFromRefreshToken(
                $response_factory,
                $stream_factory,
                $access_token_grant_error_response_builder,
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                new OAuth2RefreshTokenVerifier(
                    new SplitTokenVerificationStringHasher(),
                    new OAuth2RefreshTokenDAO(),
                    new OAuth2ScopeRetriever(new OAuth2RefreshTokenScopeDAO(), $this->buildScopeBuilder()),
                    new OAuth2AuthorizationCodeRevoker(new OAuth2AuthorizationCodeDAO()),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                $access_token_grant_representation_builder,
                new ScopeExtractor($this->buildScopeBuilder())
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

    public function routeTokenRevocation(): DispatchableWithRequest
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        $stream_factory   = HTTPFactoryBuilder::streamFactory();
        $app_dao          = new AppDao();
        $authorization_code_revoker = new OAuth2AuthorizationCodeRevoker(
            new OAuth2AuthorizationCodeDAO()
        );
        $split_token_hasher = new SplitTokenVerificationStringHasher();
        return new \Tuleap\OAuth2Server\Grant\TokenRevocationController(
            $response_factory,
            $stream_factory,
            new \Tuleap\OAuth2Server\RefreshToken\OAuth2RefreshTokenRevoker(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2RefreshToken()),
                $authorization_code_revoker,
                new OAuth2RefreshTokenDAO(),
                $split_token_hasher
            ),
            new \Tuleap\OAuth2Server\AccessToken\OAuth2AccessTokenRevoker(
                new PrefixedSplitTokenSerializer(new PrefixOAuth2AccessToken()),
                $authorization_code_revoker,
                new OAuth2AccessTokenDAO(),
                $split_token_hasher
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

    public function accountTabPresenterCollection(AccountTabPresenterCollection $collection): void
    {
        (new \Tuleap\OAuth2Server\User\Account\AppsTabAdder())->addTabs($collection);
    }

    public function verifyAccessToken(VerifyOAuth2AccessTokenEvent $event): void
    {
        $verifier = new OAuth2AccessTokenVerifier(
            new OAuth2AccessTokenDAO(),
            new OAuth2ScopeRetriever(
                new OAuth2AccessTokenScopeDAO(),
                $this->buildScopeBuilder()
            ),
            UserManager::instance(),
            new SplitTokenVerificationStringHasher()
        );

        $user = $verifier->getUser($event->getAccessToken(), $event->getRequiredScope());
        $event->setVerifiedUser($user);
    }

    public function collectOAuth2ScopeBuilder(OAuth2ScopeBuilderCollector $collector): void
    {
        $collector->addOAuth2ScopeBuilder(
            new AuthenticationScopeBuilderFromClassNames(
                OAuth2OfflineAccessScope::class,
                OAuth2SignInScope::class,
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

    public function dailyCleanup(): void
    {
        $current_time = (new DateTimeImmutable())->getTimestamp();
        (new OAuth2AccessTokenDAO())->deleteByExpirationDate($current_time);
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeByExpirationDate($current_time);
    }

    public function projectIsDeleted(): void
    {
        (new OAuth2AuthorizationCodeDAO())->deleteAuthorizationCodeInNonExistingOrDeletedProject();
        (new AuthorizationDao())->deleteAuthorizationsInNonExistingOrDeletedProject();
        (new AppDao())->deleteAppsInNonExistingOrDeletedProject();
    }
}
